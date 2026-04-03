<?php

namespace App\Message;

final readonly class ProcessImageUpload
{
    public function __construct(
        public int $userId,
        public string $filePath,
    ) {
    }
}
