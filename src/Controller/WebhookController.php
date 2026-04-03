<?php

namespace App\Controller;

use App\Message\HandleWebhookPayload;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook')]
class WebhookController extends AbstractController
{
    #[Route('', name: 'webhook_index')]
    public function index(): Response
    {
        return $this->render('webhook/index.html.twig');
    }

    #[Route('/test', name: 'webhook_test', methods: ['POST'])]
    public function test(Request $request, LoggerInterface $logger): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? $request->request->all();

        $logger->info('Webhook received', [
            'type' => 'test',
            'payload' => $payload,
            'headers' => [
                'content-type' => $request->headers->get('content-type'),
                'user-agent' => $request->headers->get('user-agent'),
            ],
        ]);

        return new JsonResponse([
            'status' => 'received',
            'type' => 'test',
            'payload' => $payload,
            'received_at' => (new \DateTimeImmutable())->format('c'),
        ]);
    }

    #[Route('/stripe', name: 'webhook_stripe', methods: ['POST'])]
    public function stripe(
        Request $request,
        LoggerInterface $logger,
        MessageBusInterface $bus,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!$payload || !isset($payload['type'])) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        $logger->info('Stripe webhook received', [
            'type' => $payload['type'],
            'id' => $payload['id'] ?? null,
        ]);

        $bus->dispatch(new HandleWebhookPayload(
            type: $payload['type'],
            payload: $payload,
        ));

        return new JsonResponse([
            'status' => 'accepted',
            'type' => $payload['type'],
        ]);
    }

    #[Route('/send-test', name: 'webhook_send_test', methods: ['POST'])]
    public function sendTest(Request $request, HttpClientInterface $httpClient): Response
    {
        $tunnelUrl = $request->request->get('tunnel_url', '');

        if (!$tunnelUrl) {
            $this->addFlash('error', 'Tunnel URL is required. Start tunnel first: make tunnel');
            return $this->redirectToRoute('webhook_index');
        }

        try {
            $response = $httpClient->request('POST', $tunnelUrl . '/webhook/test', [
                'json' => [
                    'event' => 'test.webhook',
                    'data' => ['message' => 'Hello from tunnel roundtrip!'],
                    'timestamp' => (new \DateTimeImmutable())->format('c'),
                ],
                'verify_peer' => false, // Local mkcert certs
                'timeout' => 10,
            ]);

            $this->addFlash('success', sprintf(
                'Webhook roundtrip OK! Status: %d, Response: %s',
                $response->getStatusCode(),
                $response->getContent(false),
            ));
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Error: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('webhook_index');
    }
}
