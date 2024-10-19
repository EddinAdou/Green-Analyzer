<?php

namespace App\Service;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class SiteAnalyzer
{
    private $client;

    public function __construct()
    {
        // Initialize the Guzzle client for HTTP requests
        $this->client = new Client();
    }

    public function analyser(string $url): array
    {
        try {
            // Fetch the page with Guzzle
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();

            // Use DomCrawler to analyze the HTML content
            $crawler = new Crawler($html);

            // Extract CSS, JS, and image files
            $cssFiles = $crawler->filter('link[rel="stylesheet"]')->each(fn ($node) => $node->attr('href'));
            $jsFiles = $crawler->filter('script[src]')->each(fn ($node) => $node->attr('src'));
            $images = $crawler->filter('img[src]')->each(fn ($node) => $node->attr('src'));

            // Calculate the total weight of the files
            $poidsTotal = count($cssFiles) * 0.1 + count($jsFiles) * 0.2 + count($images) * 0.5;

            // Total number of HTTP requests
            $nbRequetes = count($cssFiles) + count($jsFiles) + count($images);

            // Calculate the complexity of the DOM
            $domElements = $crawler->filter('*')->count();

            // Calculate quantiles for each criterion
            $quantileDOM = $this->calculerQuantileDOM($domElements);
            $quantileHttp = $this->calculerQuantileHttp($nbRequetes);
            $quantileData = $this->calculerQuantileData($poidsTotal);

            // Calculate the EcoIndex with weighting
            $ecoIndex = $this->calculerEcoIndex($quantileDOM, $quantileHttp, $quantileData);

            // Determine the score, note, and appreciation based on the EcoIndex
            $noteData = $this->determinerNoteEtAppreciation($ecoIndex);

            return array_merge([
                'poidsTotal' => $poidsTotal,
                'nbRequetes' => $nbRequetes,
                'domElements' => $domElements,
                'ecoIndex' => $ecoIndex,
                'score' => $ecoIndex // Add the score key
            ], $noteData);

        } catch (\Exception $e) {
            // In case of error, return an error message
            return [
                'error' => 'An error occurred during the analysis.',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function calculerQuantileDOM(int $domElements): float
    {
        // Normalize the number of DOM elements relative to an estimated maximum
        $domMax = 1500; // Example upper bound
        return min(1, $domElements / $domMax);
    }

    private function calculerQuantileHttp(int $nbRequetes): float
    {
        // Normalize the number of HTTP requests relative to an estimated maximum
        $requeteMax = 100; // Example upper bound
        return min(1, $nbRequetes / $requeteMax);
    }

    private function calculerQuantileData(float $poidsTotal): float
    {
        // Normalize the weight of the data relative to an estimated maximum
        $poidsMax = 5.0; // 5 MB as an example upper bound
        return min(1, $poidsTotal / $poidsMax);
    }

    private function calculerEcoIndex(float $quantileDOM, float $quantileHttp, float $quantileData): float
    {
        // Formula for the weighted EcoIndex
        return 100 - (1/6) * (3 * $quantileDOM + 2 * $quantileHttp + 1 * $quantileData);
    }

    private function determinerNoteEtAppreciation(float $ecoIndex): array
    {
        if ($ecoIndex >= 90) {
            return ['note' => 'A', 'appreciation' => 'Excellent'];
        } elseif ($ecoIndex >= 80) {
            return ['note' => 'B', 'appreciation' => 'Very good'];
        } elseif ($ecoIndex >= 70) {
            return ['note' => 'C', 'appreciation' => 'Good'];
        } elseif ($ecoIndex >= 60) {
            return ['note' => 'D', 'appreciation' => 'Average'];
        } elseif ($ecoIndex >= 50) {
            return ['note' => 'E', 'appreciation' => 'Passable'];
        } elseif ($ecoIndex >= 40) {
            return ['note' => 'F', 'appreciation' => 'Insufficient'];
        } else {
            return ['note' => 'G', 'appreciation' => 'Very baddd'];
        }
    }
}