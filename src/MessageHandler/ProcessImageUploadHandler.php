<?php

namespace App\MessageHandler;

use App\Message\ProcessImageUpload;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessImageUploadHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessImageUpload $message): void
    {
        $this->logger->info('Processing image upload', [
            'userId' => $message->userId,
            'filePath' => $message->filePath,
        ]);

        // Simulate image processing (resize, etc.)
        usleep(1_000_000);

        $this->logger->info('Image processing complete', [
            'userId' => $message->userId,
        ]);
    }
}
