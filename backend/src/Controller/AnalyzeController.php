<?php

namespace App\Controller;

use App\Service\SiteAnalyzer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Analyze;

class AnalyzeController extends AbstractController
{
    private SiteAnalyzer $siteAnalyzer;
    private EntityManagerInterface $entityManager;

    public function __construct(SiteAnalyzer $siteAnalyzer, EntityManagerInterface $entityManager)
    {
        $this->siteAnalyzer = $siteAnalyzer;
        $this->entityManager = $entityManager;
    }
    #[Route('/api/analyse', name: 'api_analyse', methods: ['GET', 'POST'])]
    public function analyse(Request $request, LoggerInterface $logger): JsonResponse
    {
        $logger->info('Début de l\'analyse');

        $data = json_decode($request->getContent(), true);
        $url = $data['url'] ?? null;

        if (!$url) {
            $logger->info('URL manquante');
            return new JsonResponse(['error' => 'URL manquante'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $logger->info('URL analysée: ' . $url);

        $result = $this->siteAnalyzer->analyser($url);

        if (isset($result['error'])) {
            $logger->error('Erreur lors de l\'analyse: ' . $result['error']);
            return new JsonResponse($result, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $logger->info('Analyse réussie avec score: ' . $result['score']);

        // Créer une nouvelle entité Analyse
        $analyse = new Analyze();
        $analyse->setScore($result['score']);
        $analyse->setNote($result['note']);
        $analyse->setAppreciation($result['appreciation']);

        // Persister et sauvegarder
        $this->entityManager->persist($analyse);
        $this->entityManager->flush();

        $logger->info('Données sauvegardées en base');

        return new JsonResponse($result);
    }

}