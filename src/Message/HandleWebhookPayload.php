<?php

namespace App\Message;

final readonly class HandleWebhookPayload
{
    public function __construct(
        public string $type,
        public array $payload,
    ) {
    }
}
