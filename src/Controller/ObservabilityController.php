<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/observability')]
class ObservabilityController extends AbstractController
{
    #[Route('', name: 'observability_index')]
    public function index(): Response
    {
        return $this->render('observability/index.html.twig');
    }

    #[Route('/trace', name: 'observability_trace', methods: ['POST'])]
    public function trace(LoggerInterface $logger): Response
    {
        $logger->info('Manual trace generated from ObservabilityController', [
            'action' => 'trace_demo',
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ]);

        $logger->warning('This is a warning log for demo purposes');
        $logger->error('This is an error log for demo purposes');

        $this->addFlash('success', 'Trace + logs generated. Check Grafana for details.');
        return $this->redirectToRoute('observability_index');
    }
}
