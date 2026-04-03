<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/cache')]
class CacheController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route('', name: 'cache_index')]
    public function index(): Response
    {
        return $this->render('cache/index.html.twig');
    }

    #[Route('/set', name: 'cache_set', methods: ['POST'])]
    public function set(Request $request): Response
    {
        $key = $request->request->get('key', 'test');
        $value = $request->request->get('value', 'hello');
        $ttl = (int) $request->request->get('ttl', 60);

        $this->cache->delete($key);
        $result = $this->cache->get($key, function (ItemInterface $item) use ($value, $ttl) {
            $item->expiresAfter($ttl);
            return $value;
        });

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse(['status' => 'set', 'key' => $key, 'value' => $result, 'ttl' => $ttl]);
        }

        $this->addFlash('success', sprintf('Set "%s" = "%s" (TTL: %ds)', $key, $value, $ttl));
        return $this->redirectToRoute('cache_index');
    }

    #[Route('/get', name: 'cache_get', methods: ['POST'])]
    public function get(Request $request): Response
    {
        $key = $request->request->get('key', 'test');

        $value = $this->cache->get($key, function () {
            return null;
        });

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse(['key' => $key, 'value' => $value, 'found' => $value !== null]);
        }

        if ($value !== null) {
            $this->addFlash('success', sprintf('"%s" = "%s"', $key, $value));
        } else {
            $this->addFlash('error', sprintf('Key "%s" not found', $key));
        }

        return $this->redirectToRoute('cache_index');
    }

    #[Route('/delete', name: 'cache_delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        $key = $request->request->get('key', 'test');
        $this->cache->delete($key);

        $this->addFlash('success', sprintf('Deleted "%s"', $key));
        return $this->redirectToRoute('cache_index');
    }

    #[Route('/benchmark', name: 'cache_benchmark', methods: ['POST'])]
    public function benchmark(): Response
    {
        $iterations = 1000;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->cache->delete("bench_$i");
            $this->cache->get("bench_$i", function (ItemInterface $item) use ($i) {
                $item->expiresAfter(60);
                return "value_$i";
            });
        }

        $writeTime = (microtime(true) - $start) * 1000;

        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->cache->get("bench_$i", fn () => null);
        }
        $readTime = (microtime(true) - $start) * 1000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->cache->delete("bench_$i");
        }

        $result = sprintf(
            '%d ops: write %.1fms (%.0f ops/s), read %.1fms (%.0f ops/s)',
            $iterations,
            $writeTime, $iterations / ($writeTime / 1000),
            $readTime, $iterations / ($readTime / 1000),
        );

        $this->addFlash('success', $result);
        return $this->redirectToRoute('cache_index');
    }
}
