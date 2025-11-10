<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Datasource;

class IngestController extends AbstractController{

    private $entityManager;
    private $httpClient;
    
    public function __construct(EntityManagerInterface $em, HttpClientInterface $httpClient)
    {
        $this->entityManager = $em;
        $this->httpClient = $httpClient;
    }


    #[Route('/ingest/json', name: 'app_ingest_json', methods: ['POST'])]
    public function ingestData(Request $request): Response
    {
        $ingestKey = $request->headers->get('X-Ingest-Key');
        $datasource = $this->entityManager->getRepository(Datasource::class)->findOneBy(['ingestKey' => $ingestKey]);
        if(null === $datasource) {
            return new JsonResponse(['error' => 'Invalid Ingest Key'], Response::HTTP_UNAUTHORIZED);
        }

        if($datasource->getValidFrom() !== null && $datasource->getValidFrom() > new \DateTime()) {
            return new JsonResponse(['error' => 'Ingest Key not yet valid'], Response::HTTP_UNAUTHORIZED);
        }

        if ($datasource->getValidTo() !== null && $datasource->getValidTo() > new \DateTime()) {
            return new JsonResponse(['error' => 'Ingest Key expired'], Response::HTTP_UNAUTHORIZED);
        }

        if (strlen($request->getContent()) > 128 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Request body too large'], Response::HTTP_BAD_REQUEST);
        }

        $inputData = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE || (!is_array($inputData) && !is_object($inputData))) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        } 

        if(!isset($inputData[0]) || !is_array($inputData[0])) {
            $inputData = [$inputData];
        }

        foreach ($inputData as $k=>$v) {
            $inputData[$k]['target_index'] = $datasource->getTargetIndex();
        }

        if ($_ENV['FLUENTBIT_SERVER_URL'] === null || $_ENV['FLUENTBIT_SERVER_URL'] === '') {
            return new JsonResponse(['error' => 'Fluentbit server endpoint not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = $this->httpClient->request('POST', $_ENV['FLUENTBIT_SERVER_URL'], ['json' => $inputData]);
        return new Response($response->getContent(), $response->getStatusCode());
    }
}