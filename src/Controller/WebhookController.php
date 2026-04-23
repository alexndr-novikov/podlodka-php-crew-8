<?php

namespace App\Controller;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook')]
class WebhookController extends AbstractController
{
    private const FEED_KEY = 'webhook_feed';
    private const MAX_ITEMS = 50;

    public function __construct(
        private readonly CacheItemPoolInterface $cachePool,
    ) {
    }

    #[Route('', name: 'webhook_index')]
    public function index(): Response
    {
        return $this->render('webhook/index.html.twig');
    }

    #[Route('/test', name: 'webhook_test', methods: ['POST'])]
    public function test(Request $request): JsonResponse
    {
        $content = $request->getContent();
        $payload = $content ? (json_decode($content, true) ?: ['raw' => $content]) : $request->request->all();

        $entry = [
            'id' => bin2hex(random_bytes(4)),
            'payload' => $payload,
            'ip' => $request->getClientIp(),
            'ua' => mb_substr((string) $request->headers->get('user-agent', ''), 0, 80),
            'time' => (new \DateTimeImmutable())->format('H:i:s'),
        ];

        $item = $this->cachePool->getItem(self::FEED_KEY);
        $feed = $item->isHit() ? $item->get() : [];
        array_unshift($feed, $entry);
        $feed = \array_slice($feed, 0, self::MAX_ITEMS);
        $item->set($feed)->expiresAfter(3600);
        $this->cachePool->save($item);

        return new JsonResponse(['status' => 'ok', 'id' => $entry['id']]);
    }

    #[Route('/feed', name: 'webhook_feed', methods: ['GET'])]
    public function feed(): JsonResponse
    {
        $item = $this->cachePool->getItem(self::FEED_KEY);
        $feed = $item->isHit() ? $item->get() : [];

        return new JsonResponse($feed);
    }

    #[Route('/clear', name: 'webhook_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cachePool->deleteItem(self::FEED_KEY);
        return $this->redirectToRoute('webhook_index');
    }
}
