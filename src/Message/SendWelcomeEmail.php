<?php

namespace App\Message;

final readonly class SendWelcomeEmail
{
    public function __construct(
        public int $userId,
    ) {
    }
}
