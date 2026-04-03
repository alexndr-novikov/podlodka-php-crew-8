# Isolated Endpoints — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create 5 isolated demo endpoints (Mail, Storage, Search, Cache, Observability), each with a Twig page + form + API JSON response.

**Architecture:** Each controller handles both HTML (Twig) and JSON responses. Templates extend base.html.twig. Each page has a form, shows results, and links to the corresponding service UI.

**Tech Stack:** Symfony Mailer, Flysystem, Meilisearch PHP SDK, Symfony Cache (Redis/Valkey adapter), Symfony HttpClient

---

### Task 1: MailController

**Files:**
- Create: `src/Controller/MailController.php`
- Create: `templates/mail/index.html.twig`

- [ ] **Step 1: Create MailController**

Create `src/Controller/MailController.php`:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mail')]
class MailController extends AbstractController
{
    #[Route('', name: 'mail_index')]
    public function index(): Response
    {
        return $this->render('mail/index.html.twig');
    }

    #[Route('/send', name: 'mail_send', methods: ['POST'])]
    public function send(Request $request, MailerInterface $mailer): Response
    {
        $to = $request->request->get('to', 'test@example.com');
        $subject = $request->request->get('subject', 'Test from Docker Workshop');
        $body = $request->request->get('body', 'Hello from Symfony + Mailpit!');

        $email = (new Email())
            ->from('workshop@example.com')
            ->to($to)
            ->subject($subject)
            ->html(sprintf('<h1>%s</h1><p>%s</p>', htmlspecialchars($subject), nl2br(htmlspecialchars($body))));

        $mailer->send($email);

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse(['status' => 'sent', 'to' => $to, 'subject' => $subject]);
        }

        $this->addFlash('success', sprintf('Email sent to %s', $to));
        return $this->redirectToRoute('mail_index');
    }
}
```

- [ ] **Step 2: Create template**

Create `templates/mail/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Email — Docker Workshop{% endblock %}

{% block body %}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">📧 Email (Mailpit)</h1>
        <p class="mt-1 text-gray-500">Отправка email через Symfony Mailer → Mailpit SMTP</p>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Отправить email</h2>
            <form method="post" action="{{ path('mail_send') }}" class="space-y-4">
                <div>
                    <label for="to" class="block text-sm font-medium text-gray-700">To</label>
                    <input type="email" name="to" id="to" value="test@example.com"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                </div>
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                    <input type="text" name="subject" id="subject" value="Test from Docker Workshop"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                </div>
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700">Body</label>
                    <textarea name="body" id="body" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">Hello from Symfony + Mailpit!</textarea>
                </div>
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Отправить
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Сервис</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">SMTP</dt>
                    <dd class="text-gray-900">mailpit:1025</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Symfony DSN</dt>
                    <dd class="text-gray-900 font-mono text-xs">smtp://mailpit:1025</dd>
                </div>
            </dl>
            <a href="https://mailpit.workshop.localhost:8443" target="_blank"
               class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-500">
                Открыть Mailpit UI ↗
            </a>
        </div>
    </div>
{% endblock %}
```

- [ ] **Step 3: Commit**

```bash
git add src/Controller/MailController.php templates/mail/
git commit -m "Add MailController: send email form + Mailpit integration"
```

---

### Task 2: StorageController

**Files:**
- Create: `src/Controller/StorageController.php`
- Create: `templates/storage/index.html.twig`

- [ ] **Step 1: Create StorageController**

Create `src/Controller/StorageController.php`:

```php
<?php

namespace App\Controller;

use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/storage')]
class StorageController extends AbstractController
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
    ) {
    }

    #[Route('', name: 'storage_index')]
    public function index(): Response
    {
        $files = [];
        foreach ($this->defaultStorage->listContents('/', false) as $item) {
            $files[] = [
                'path' => $item->path(),
                'type' => $item->isFile() ? 'file' : 'dir',
                'size' => $item->isFile() ? $this->defaultStorage->fileSize($item->path()) : null,
            ];
        }

        return $this->render('storage/index.html.twig', ['files' => $files]);
    }

    #[Route('/upload', name: 'storage_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $file = $request->files->get('file');
        if (!$file) {
            $this->addFlash('error', 'No file uploaded');
            return $this->redirectToRoute('storage_index');
        }

        $path = 'uploads/' . uniqid() . '_' . $file->getClientOriginalName();
        $this->defaultStorage->write($path, $file->getContent());

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse(['status' => 'uploaded', 'path' => $path]);
        }

        $this->addFlash('success', sprintf('File uploaded: %s', $path));
        return $this->redirectToRoute('storage_index');
    }

    #[Route('/delete/{path}', name: 'storage_delete', methods: ['POST'], requirements: ['path' => '.+'])]
    public function delete(string $path): Response
    {
        $this->defaultStorage->delete($path);
        $this->addFlash('success', sprintf('Deleted: %s', $path));
        return $this->redirectToRoute('storage_index');
    }
}
```

- [ ] **Step 2: Create template**

Create `templates/storage/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Storage (S3) — Docker Workshop{% endblock %}

{% block body %}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">📦 Storage (S3)</h1>
        <p class="mt-1 text-gray-500">Upload файлов в LocalStack S3 через Flysystem</p>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Upload файла</h2>
            <form method="post" action="{{ path('storage_upload') }}" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">Файл</label>
                    <input type="file" name="file" id="file"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Upload
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Файлы в bucket</h2>
            {% if files is empty %}
                <p class="text-sm text-gray-400">Bucket пуст</p>
            {% else %}
                <ul class="space-y-2">
                    {% for file in files %}
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 font-mono text-xs truncate">{{ file.path }}</span>
                            <div class="flex items-center gap-2">
                                {% if file.size is not null %}
                                    <span class="text-gray-400">{{ (file.size / 1024)|round(1) }} KB</span>
                                {% endif %}
                                <form method="post" action="{{ path('storage_delete', {path: file.path}) }}" class="inline">
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs">✕</button>
                                </form>
                            </div>
                        </li>
                    {% endfor %}
                </ul>
            {% endif %}
        </div>
    </div>
{% endblock %}
```

- [ ] **Step 3: Commit**

```bash
git add src/Controller/StorageController.php templates/storage/
git commit -m "Add StorageController: file upload/list/delete via LocalStack S3"
```

---

### Task 3: SearchController

**Files:**
- Create: `src/Controller/SearchController.php`
- Create: `templates/search/index.html.twig`
- Create: `src/Service/MeilisearchService.php`

- [ ] **Step 1: Install Meilisearch PHP SDK**

```bash
docker compose exec app composer require meilisearch/meilisearch-php --no-interaction
```

- [ ] **Step 2: Create MeilisearchService**

Create `src/Service/MeilisearchService.php`:

```php
<?php

namespace App\Service;

use Meilisearch\Client;

class MeilisearchService
{
    private Client $client;

    public function __construct(string $meiliUrl, string $meiliMasterKey)
    {
        $this->client = new Client($meiliUrl, $meiliMasterKey);
    }

    public function getIndex(string $name = 'users'): \Meilisearch\Endpoints\Indexes
    {
        $this->client->createIndex($name, ['primaryKey' => 'id']);
        return $this->client->index($name);
    }

    public function search(string $query, string $index = 'users'): array
    {
        return $this->getIndex($index)->search($query)->toArray();
    }

    public function addDocuments(array $documents, string $index = 'users'): void
    {
        $this->getIndex($index)->addDocuments($documents);
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
```

- [ ] **Step 3: Register service**

Add to `config/services.yaml` under `services:`:

```yaml
    App\Service\MeilisearchService:
        arguments:
            $meiliUrl: '%env(MEILI_URL)%'
            $meiliMasterKey: '%env(MEILI_MASTER_KEY)%'
```

- [ ] **Step 4: Create SearchController**

Create `src/Controller/SearchController.php`:

```php
<?php

namespace App\Controller;

use App\Service\MeilisearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/search')]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly MeilisearchService $meilisearch,
    ) {
    }

    #[Route('', name: 'search_index')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $results = [];

        if ($query !== '') {
            $results = $this->meilisearch->search($query);
        }

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse($results);
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    #[Route('/add', name: 'search_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $name = $request->request->get('name', '');
        $email = $request->request->get('email', '');

        if ($name && $email) {
            $this->meilisearch->addDocuments([
                ['id' => uniqid(), 'name' => $name, 'email' => $email],
            ]);
            $this->addFlash('success', sprintf('Added: %s', $name));
        }

        return $this->redirectToRoute('search_index');
    }
}
```

- [ ] **Step 5: Create template**

Create `templates/search/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Search — Docker Workshop{% endblock %}

{% block body %}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">🔍 Search (Meilisearch)</h1>
        <p class="mt-1 text-gray-500">Полнотекстовый поиск с typo-tolerance</p>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold mb-4">Поиск</h2>
                <form method="get" action="{{ path('search_index') }}">
                    <div class="flex gap-2">
                        <input type="text" name="q" value="{{ query }}" placeholder="Введите запрос..."
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                        <button type="submit"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Найти
                        </button>
                    </div>
                </form>

                {% if results.hits is defined and results.hits is not empty %}
                    <ul class="mt-4 space-y-2">
                        {% for hit in results.hits %}
                            <li class="rounded-md bg-gray-50 p-3 text-sm">
                                <span class="font-medium">{{ hit.name ?? hit.id }}</span>
                                {% if hit.email is defined %}
                                    <span class="text-gray-400 ml-2">{{ hit.email }}</span>
                                {% endif %}
                            </li>
                        {% endfor %}
                    </ul>
                    <p class="mt-2 text-xs text-gray-400">{{ results.estimatedTotalHits ?? 0 }} results in {{ results.processingTimeMs ?? 0 }}ms</p>
                {% elseif query %}
                    <p class="mt-4 text-sm text-gray-400">Ничего не найдено</p>
                {% endif %}
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold mb-4">Добавить документ</h2>
                <form method="post" action="{{ path('search_add') }}" class="space-y-3">
                    <input type="text" name="name" placeholder="Имя" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                    <input type="email" name="email" placeholder="Email" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                    <button type="submit"
                            class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        Добавить
                    </button>
                </form>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm h-fit">
            <h2 class="text-lg font-semibold mb-4">Сервис</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">URL</dt>
                    <dd class="text-gray-900 font-mono text-xs">meilisearch:7700</dd>
                </div>
            </dl>
            <a href="https://search.workshop.localhost:8443" target="_blank"
               class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-500">
                Открыть Meilisearch ↗
            </a>
        </div>
    </div>
{% endblock %}
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add SearchController: Meilisearch search + add documents"
```

---

### Task 4: CacheController

**Files:**
- Create: `src/Controller/CacheController.php`
- Create: `templates/cache/index.html.twig`

- [ ] **Step 1: Create CacheController**

Create `src/Controller/CacheController.php`:

```php
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

        // Cleanup
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
```

- [ ] **Step 2: Create template**

Create `templates/cache/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Cache — Docker Workshop{% endblock %}

{% block body %}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">⚡ Cache (Valkey)</h1>
        <p class="mt-1 text-gray-500">Операции с Valkey (Redis-compatible key-value store)</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Set</h2>
            <form method="post" action="{{ path('cache_set') }}" class="space-y-3">
                <input type="text" name="key" placeholder="Key" value="test"
                       class="block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                <input type="text" name="value" placeholder="Value" value="hello"
                       class="block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                <input type="number" name="ttl" placeholder="TTL (seconds)" value="60"
                       class="block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Set</button>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Get / Delete</h2>
            <form method="post" action="{{ path('cache_get') }}" class="space-y-3">
                <input type="text" name="key" placeholder="Key" value="test"
                       class="block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                <div class="flex gap-2">
                    <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Get</button>
                    <button type="submit" formaction="{{ path('cache_delete') }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Delete</button>
                </div>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Benchmark</h2>
            <p class="text-sm text-gray-500 mb-4">1000 write + 1000 read операций</p>
            <form method="post" action="{{ path('cache_benchmark') }}">
                <button type="submit" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">Run Benchmark</button>
            </form>
        </div>
    </div>
{% endblock %}
```

- [ ] **Step 3: Configure Valkey as cache adapter**

Ensure `config/packages/cache.yaml` uses Redis adapter. Add/update:

```yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(VALKEY_URL)%'
```

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "Add CacheController: Valkey set/get/delete/benchmark"
```

---

### Task 5: ObservabilityController

**Files:**
- Create: `src/Controller/ObservabilityController.php`
- Create: `templates/observability/index.html.twig`

- [ ] **Step 1: Create ObservabilityController**

Create `src/Controller/ObservabilityController.php`:

```php
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
```

- [ ] **Step 2: Create template**

Create `templates/observability/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Observability — Docker Workshop{% endblock %}

{% block body %}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">📊 Observability</h1>
        <p class="mt-1 text-gray-500">Трейсы, логи, метрики через OpenTelemetry → Grafana LGTM</p>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Генерация трейса</h2>
            <p class="text-sm text-gray-500 mb-4">Создаёт трейс и несколько логов разного уровня (info, warning, error)</p>
            <form method="post" action="{{ path('observability_trace') }}">
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Generate Trace + Logs
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Сервисы</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">OTel Collector</dt>
                    <dd class="text-gray-900 font-mono text-xs">otel-lgtm:4318</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Grafana</dt>
                    <dd class="text-gray-900 font-mono text-xs">otel-lgtm:3000</dd>
                </div>
            </dl>
            <div class="mt-4 space-y-2">
                <a href="https://grafana.workshop.localhost:8443" target="_blank"
                   class="block text-sm text-indigo-600 hover:text-indigo-500">
                    Открыть Grafana ↗
                </a>
                <p class="text-xs text-gray-400">OTel PHP SDK пока не подключён (grpc/protobuf отключены в Dockerfile). Логи доступны через Monolog.</p>
            </div>
        </div>
    </div>
{% endblock %}
```

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Add ObservabilityController: trace/log generation + Grafana links"
```

---

### Task 6: Update Dashboard links

**Files:**
- Modify: `src/Controller/DashboardController.php`
- Modify: `templates/dashboard/index.html.twig`

- [ ] **Step 1: Update dashboard template to use route links**

Now that routes exist, update `templates/dashboard/index.html.twig` — change the `<span>` to `<a>`:

Replace:
```twig
<span class="text-sm font-medium text-indigo-600">
    Открыть →
</span>
```

With:
```twig
<a href="{{ path(demo.route) }}"
   class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
    Открыть →
</a>
```

Note: routes `workflow_index`, `webhook_index`, `onboarding_index` don't exist yet. Use a try/catch approach in the template or leave as `<span>` for those. Simplest: only link routes that exist, otherwise show disabled span.

Replace the whole link block with:
```twig
{% if demo.route starts with 'mail_' or demo.route starts with 'storage_' or demo.route starts with 'search_' or demo.route starts with 'cache_' or demo.route starts with 'observability_' %}
    <a href="{{ path(demo.route) }}"
       class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        Открыть →
    </a>
{% else %}
    <span class="text-sm font-medium text-gray-300 cursor-not-allowed">
        Скоро →
    </span>
{% endif %}
```

- [ ] **Step 2: Commit**

```bash
git add -A
git commit -m "Update Dashboard: link to implemented endpoints, disable upcoming ones"
```

---

## Summary

| Task | What | Depends on |
|------|------|-----------|
| 1 | MailController + template | — |
| 2 | StorageController + template | — |
| 3 | SearchController + MeilisearchService + template | — |
| 4 | CacheController + template + Valkey cache config | — |
| 5 | ObservabilityController + template | — |
| 6 | Update Dashboard links | 1-5 |
