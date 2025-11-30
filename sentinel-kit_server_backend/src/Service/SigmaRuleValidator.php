<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class SigmaRuleValidator
{
    public function validateSigmaRuleContent(string $ruleContent): array
    {
        $requiredFields = ['title', 'description', 'detection' => ['condition']];
        $missingFields = [];
        
        try {
            $yamlData = Yaml::parse($ruleContent);
        } catch (ParseException $e) {
            return ['error' => 'YAML parsing error: ' . $e->getMessage()];
        }

        if (!$yamlData) {
            return ['error' => 'Empty or invalid YAML content'];
        }

        $missingFields = [];
        foreach ($requiredFields as $field => $subFields) {
            if (is_array($subFields)) {
                if (!isset($yamlData[$field])) {
                    $missingFields[] = $field;
                } else {
                    foreach ($subFields as $subField) {
                        if (!isset($yamlData[$field][$subField])) {
                            $missingFields[] = $field . '.' . $subField;
                        }
                    }
                }
            } else {
                if (!isset($yamlData[$subFields])) {
                    $missingFields[] = $subFields;
                }
            }
        }

        if (!isset($yamlData['level']) || !in_array($yamlData['level'], ['informational', 'low', 'medium', 'high', 'critical'])) {
            $yamlData['level'] = 'informational';
        }
    
        return ['missingFields' => $missingFields, 'yamlData' => $yamlData];
    }
}