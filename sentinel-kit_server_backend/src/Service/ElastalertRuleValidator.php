<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use App\Entity\SigmaRuleVersion;

class ElastalertRuleValidator
{
    
    private function enhanceSigmaRule(array $yamlData): array
    {
        if (!isset($yamlData['logsource'])) {
            $yamlData['logsource'] = [
                'category' => 'logs',
                'product' => '*'
            ];
        }
        
        if (is_array($yamlData['logsource'])) {
            if (!isset($yamlData['logsource']['category'])) {
                $yamlData['logsource']['category'] = 'logs';
            }
            if (!isset($yamlData['logsource']['product'])) {
                $yamlData['logsource']['product'] = '*';
            }
        }
        
        if (isset($yamlData['detection']) && is_array($yamlData['detection'])) {
            $yamlData['detection'] = $this->fixDetectionModifiers($yamlData['detection']);
        }
        
        return $yamlData;
    }

    private function fixDetectionModifiers(array $detection): array
    {
        foreach ($detection as $key => &$value) {
            if (is_array($value)) {
                $value = $this->fixDetectionModifiers($value);
                
                $newValue = [];
                foreach ($value as $fieldKey => $fieldValue) {
                    if (is_string($fieldKey) && strpos($fieldKey, '|') !== false) {
                        $parts = explode('|', $fieldKey, 2);
                        $baseField = $parts[0];
                        $modifier = $parts[1];
                        
                        switch ($modifier) {
                            case 'startswith':
                                if (is_array($fieldValue)) {
                                    $newValue[$baseField] = $fieldValue;
                                } else {
                                    $newValue[$baseField] = $fieldValue . '*';
                                }
                                break;
                            case 'endswith':
                                if (is_array($fieldValue)) {
                                    $newValue[$baseField] = $fieldValue;
                                } else {
                                    $newValue[$baseField] = '*' . $fieldValue;
                                }
                                break;
                            case 'contains':
                                if (is_array($fieldValue)) {
                                    $newValue[$baseField] = $fieldValue;
                                } else {
                                    $newValue[$baseField] = '*' . $fieldValue . '*';
                                }
                                break;
                            case 'all':
                            case 'any':
                                $newValue[$baseField] = $fieldValue;
                                break;
                            default:
                                $newValue[$baseField] = $fieldValue;
                                break;
                        }
                    } else {
                        $newValue[$fieldKey] = $fieldValue;
                    }
                }
                $value = $newValue;
            }
        }
        
        return $detection;
    }

    public function createElastalertRule(SigmaRuleVersion $rule): array
    {
        $ruleContent = $rule->getContent();
        try {
            $yamlData = Yaml::parse($ruleContent);
        } catch (ParseException $e) {
            return ['error' => 'YAML parsing error: ' . $e->getMessage()];
        }

        if (!$yamlData) {
            return ['error' => 'Empty or invalid YAML content'];
        }
        
        $yamlData = $this->enhanceSigmaRule($yamlData);
        $enhancedContent = Yaml::dump($yamlData);
        
        $ruleFilePath = '/tmp/' . $rule->getHash() . '.yml';
        try{
            file_put_contents($ruleFilePath, $enhancedContent);
        } catch (\Exception $e) {
            return ['error' => 'Failed to write rule file: ' . $e->getMessage()];
        }

        $elastalertRulePath = '/detection-rules/elastalert/' . $rule->getHash() . '.yml';
        $command = sprintf('sigma convert %s -o %s -t elastalert --without-pipeline 2>&1', escapeshellarg($ruleFilePath), escapeshellarg($elastalertRulePath));
        exec($command, $output, $returnVar);
        unlink($ruleFilePath);

        if ($returnVar !== 0) {
            return ['error' => 'Failed to convert rule to Elastalert format: ' . implode("\n", $output)];
        }

        try {
            $elastalertContent = file_get_contents($elastalertRulePath);
            $elastalertYaml = Yaml::parse($elastalertContent);
            $elastalertYaml = array_merge(['import' => '/app/defaults.yml'], $elastalertYaml);

            file_put_contents($elastalertRulePath, Yaml::dump($elastalertYaml, 4, 2));
        } catch (\Exception $e) {
            return ['error' => 'Failed to enhance ElastAlert rule: ' . $e->getMessage()];
        }

        return ['success' => true, 'filePath' => $elastalertRulePath];
    }

    public function removeElastalertRule(SigmaRuleVersion $rule): void
    {
        $ruleFilePath = '/detection-rules/elastalert/' . $rule->getHash() . '.yml';
        if (file_exists($ruleFilePath)) {
            unlink($ruleFilePath);
        }
    }
}