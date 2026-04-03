<?php

namespace App\MessageHandler;

use App\Message\HandleWebhookPayload;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class HandleWebhookPayloadHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(HandleWebhookPayload $message): void
    {
        $this->logger->info('Processing webhook payload', [
            'type' => $message->type,
            'payload_keys' => array_keys($message->payload),
        ]);

        // Simulate processing
        usleep(500_000);

        $this->logger->info('Webhook payload processed', [
            'type' => $message->type,
        ]);
    }
}
