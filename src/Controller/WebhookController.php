<?php

namespace App\Controller;

use App\Message\HandleWebhookPayload;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function sendTest(Request $request): Response
    {
        $tunnelUrl = $request->request->get('tunnel_url', '');

        if (!$tunnelUrl) {
            $this->addFlash('error', 'Tunnel URL is required. Start tunnel first: make tunnel');
            return $this->redirectToRoute('webhook_index');
        }

        try {
            $ch = curl_init($tunnelUrl . '/webhook/test');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'event' => 'test.webhook',
                    'data' => ['message' => 'Hello from tunnel roundtrip!'],
                    'timestamp' => (new \DateTimeImmutable())->format('c'),
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->addFlash('error', sprintf('Tunnel request failed: %s', $error));
            } else {
                $this->addFlash('success', sprintf('Webhook roundtrip OK! Status: %d, Response: %s', $httpCode, $response));
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Error: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('webhook_index');
    }
}
