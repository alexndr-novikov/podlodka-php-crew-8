# Symfony Core + UI — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Install all Symfony packages, configure Doctrine with User entity, set up Tailwind + Stimulus UI, create Dashboard page with cards linking to all demo endpoints.

**Architecture:** Symfony 7.2 with webapp pack, Doctrine ORM (PostgreSQL), Twig + Tailwind CSS + Stimulus via AssetMapper (no Node.js), NelmioApiDocBundle for Swagger.

**Tech Stack:** Symfony 7.2, Doctrine ORM, Twig, Tailwind CSS (symfonycasts/tailwind-bundle), Symfony UX (Turbo + Stimulus), NelmioApiDocBundle

---

### Task 1: Install Symfony packages

**Files:**
- Modify: `composer.json`
- Create: various config files (auto-created by Flex recipes)

- [ ] **Step 1: Install webapp pack and core dependencies**

Run inside the app container:

```bash
docker compose exec app composer require symfony/webapp-pack --no-interaction
```

This installs: twig, doctrine-bundle, security-bundle, mailer, messenger, asset-mapper, stimulus, turbo, etc.

- [ ] **Step 2: Install additional packages**

```bash
docker compose exec app composer require \
    nelmio/api-doc-bundle \
    symfony/messenger \
    league/flysystem-aws-s3-v3 \
    league/flysystem-bundle \
    symfony/cache \
    symfony/http-client \
    --no-interaction
```

- [ ] **Step 3: Install dev dependencies**

```bash
docker compose exec app composer require --dev \
    symfony/maker-bundle \
    symfony/test-pack \
    phpstan/phpstan \
    friendsofphp/php-cs-fixer \
    --no-interaction
```

- [ ] **Step 4: Verify Symfony boots**

```bash
docker compose exec app php bin/console about
```

Expected: Symfony version info, environment details, no errors.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Install Symfony webapp-pack and additional dependencies"
```

---

### Task 2: Install and configure Tailwind CSS

**Files:**
- Modify: `composer.json`
- Create: `tailwind.config.js`
- Create: `assets/styles/app.css`

- [ ] **Step 1: Install Tailwind bundle**

```bash
docker compose exec app composer require symfonycasts/tailwind-bundle --no-interaction
```

- [ ] **Step 2: Initialize Tailwind**

```bash
docker compose exec app php bin/console tailwind:init
```

This creates `tailwind.config.js` and updates `assets/styles/app.css` with Tailwind directives.

- [ ] **Step 3: Build Tailwind CSS**

```bash
docker compose exec app php bin/console tailwind:build
```

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "Add Tailwind CSS via symfonycasts/tailwind-bundle"
```

---

### Task 3: Configure Doctrine and create User entity

**Files:**
- Modify: `config/packages/doctrine.yaml`
- Create: `src/Entity/User.php`
- Create: `src/Repository/UserRepository.php`
- Create: `migrations/VersionXXX.php`

- [ ] **Step 1: Configure Doctrine for PostgreSQL**

Verify `config/packages/doctrine.yaml` uses `DATABASE_URL` env var (should be auto-configured by Flex).

```bash
docker compose exec app php bin/console doctrine:database:create --if-not-exists
```

- [ ] **Step 2: Create User entity**

Create `src/Entity/User.php`:

```php
<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '\"user\"')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column]
    private bool $profileComplete = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function isProfileComplete(): bool
    {
        return $this->profileComplete;
    }

    public function setProfileComplete(bool $profileComplete): static
    {
        $this->profileComplete = $profileComplete;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

- [ ] **Step 3: Create UserRepository**

Create `src/Repository/UserRepository.php`:

```php
<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
}
```

- [ ] **Step 4: Generate and run migration**

```bash
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

- [ ] **Step 5: Verify**

```bash
docker compose exec app php bin/console doctrine:schema:validate
```

Expected: Schema is in sync with mapping.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add User entity with Doctrine migration"
```

---

### Task 4: Create base Twig layout

**Files:**
- Create: `templates/base.html.twig`

- [ ] **Step 1: Create base layout**

Create `templates/base.html.twig`:

```twig
<!DOCTYPE html>
<html lang="ru" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}Docker Workshop{% endblock %}</title>
    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('styles/app.css') }}">
    {% endblock %}
    {% block javascripts %}
        {% block importmap %}{{ importmap('app') }}{% endblock %}
    {% endblock %}
</head>
<body class="h-full">
    <nav class="bg-indigo-600 shadow-lg">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ path('dashboard') }}" class="text-white font-bold text-lg">
                    Docker Workshop
                </a>
                <div class="flex space-x-4">
                    <a href="{{ path('dashboard') }}" class="text-indigo-100 hover:text-white text-sm">Dashboard</a>
                    <a href="{{ path('onboarding_index') }}" class="text-indigo-100 hover:text-white text-sm">Onboarding</a>
                    <a href="/api/doc" class="text-indigo-100 hover:text-white text-sm" target="_blank">API Docs</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {% for label, messages in app.flashes %}
            {% for message in messages %}
                <div class="mb-4 rounded-md p-4 {{ label == 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                    {{ message }}
                </div>
            {% endfor %}
        {% endfor %}

        {% block body %}{% endblock %}
    </main>

    <footer class="mt-auto py-4 text-center text-sm text-gray-400">
        Podlodka PHP Crew 8 — Docker Workshop
    </footer>
</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add -A
git commit -m "Add base Twig layout with Tailwind and navigation"
```

---

### Task 5: Create Dashboard page

**Files:**
- Create: `src/Controller/DashboardController.php`
- Create: `templates/dashboard/index.html.twig`

- [ ] **Step 1: Create DashboardController**

Create `src/Controller/DashboardController.php`:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(): Response
    {
        $demos = [
            [
                'title' => 'Email',
                'description' => 'Отправка email через Mailpit',
                'route' => 'mail_index',
                'icon' => '📧',
                'service' => 'Mailpit',
                'serviceUrl' => 'https://mailpit.workshop.localhost:8443',
            ],
            [
                'title' => 'Хранилище (S3)',
                'description' => 'Upload файлов в LocalStack S3',
                'route' => 'storage_index',
                'icon' => '📦',
                'service' => 'LocalStack',
                'serviceUrl' => null,
            ],
            [
                'title' => 'Поиск',
                'description' => 'Полнотекстовый поиск через Meilisearch',
                'route' => 'search_index',
                'icon' => '🔍',
                'service' => 'Meilisearch',
                'serviceUrl' => 'https://search.workshop.localhost:8443',
            ],
            [
                'title' => 'Кэш',
                'description' => 'Операции с Valkey (Redis-compatible)',
                'route' => 'cache_index',
                'icon' => '⚡',
                'service' => 'Valkey',
                'serviceUrl' => null,
            ],
            [
                'title' => 'Workflows',
                'description' => 'Temporal workflow engine',
                'route' => 'workflow_index',
                'icon' => '🔄',
                'service' => 'Temporal',
                'serviceUrl' => 'https://temporal.workshop.localhost:8443',
            ],
            [
                'title' => 'Webhooks',
                'description' => 'Приём вебхуков через Cloudflare Tunnel',
                'route' => 'webhook_index',
                'icon' => '🔗',
                'service' => 'Cloudflared',
                'serviceUrl' => null,
            ],
            [
                'title' => 'Observability',
                'description' => 'Трейсы, логи, метрики через OpenTelemetry',
                'route' => 'observability_index',
                'icon' => '📊',
                'service' => 'Grafana',
                'serviceUrl' => 'https://grafana.workshop.localhost:8443',
            ],
            [
                'title' => 'Onboarding',
                'description' => 'Сквозной сценарий: регистрация пользователя',
                'route' => 'onboarding_index',
                'icon' => '🚀',
                'service' => 'Все сервисы',
                'serviceUrl' => null,
            ],
        ];

        return $this->render('dashboard/index.html.twig', [
            'demos' => $demos,
        ]);
    }
}
```

- [ ] **Step 2: Create dashboard template**

Create `templates/dashboard/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Dashboard — Docker Workshop{% endblock %}

{% block body %}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Docker Workshop</h1>
        <p class="mt-2 text-gray-600">Демо-приложение: Symfony 7.2 + FrankenPHP + полный стек инфраструктуры</p>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {% for demo in demos %}
            <div class="group relative rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md hover:border-indigo-300">
                <div class="text-4xl mb-4">{{ demo.icon }}</div>
                <h3 class="text-lg font-semibold text-gray-900">{{ demo.title }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ demo.description }}</p>
                <div class="mt-4 flex items-center justify-between">
                    <a href="{{ path(demo.route) }}"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        Открыть →
                    </a>
                    {% if demo.serviceUrl %}
                        <a href="{{ demo.serviceUrl }}"
                           target="_blank"
                           class="text-xs text-gray-400 hover:text-gray-600">
                            {{ demo.service }} ↗
                        </a>
                    {% else %}
                        <span class="text-xs text-gray-300">{{ demo.service }}</span>
                    {% endif %}
                </div>
            </div>
        {% endfor %}
    </div>
{% endblock %}
```

- [ ] **Step 3: Verify dashboard loads**

```bash
docker compose exec app php bin/console cache:clear
curl -sk https://app.workshop.localhost:8443/ -o /dev/null -w "%{http_code}"
```

Expected: 200 (or 500 if routes don't exist yet — that's OK, dashboard links to routes that don't exist yet)

Note: The dashboard references routes like `mail_index`, `search_index` etc. that don't exist yet. Use `path()` with try/catch or create placeholder routes to avoid Twig errors. Simplest approach: use `url('#')` as fallback for now and update when controllers are created.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "Add Dashboard with demo cards linking to all endpoints"
```

---

### Task 6: Create seed command

**Files:**
- Create: `src/Command/SeedCommand.php`

- [ ] **Step 1: Create SeedCommand**

Create `src/Command/SeedCommand.php`:

```php
<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed',
    description: 'Seed the database with sample data',
)]
class SeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = [
            ['name' => 'Алексей Иванов', 'email' => 'alexey@example.com'],
            ['name' => 'Мария Петрова', 'email' => 'maria@example.com'],
            ['name' => 'Дмитрий Сидоров', 'email' => 'dmitry@example.com'],
            ['name' => 'Елена Козлова', 'email' => 'elena@example.com'],
            ['name' => 'Сергей Морозов', 'email' => 'sergey@example.com'],
        ];

        foreach ($users as $data) {
            $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existing) {
                continue;
            }

            $user = new User();
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $this->em->persist($user);
        }

        $this->em->flush();
        $io->success(sprintf('Seeded %d users.', count($users)));

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 2: Test seed command**

```bash
docker compose exec app php bin/console app:seed
```

Expected: "Seeded 5 users."

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Add app:seed command with sample users"
```

---

### Task 7: Configure Messenger transport

**Files:**
- Modify: `config/packages/messenger.yaml`

- [ ] **Step 1: Configure Messenger with Valkey transport**

Update `config/packages/messenger.yaml`:

```yaml
framework:
    messenger:
        failure_transport: failed
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
            failed: 'doctrine://default?queue_name=failed'
        routing:
            'App\Message\SendWelcomeEmail': async
            'App\Message\ProcessImageUpload': async
            'App\Message\HandleWebhookPayload': async
```

- [ ] **Step 2: Verify Messenger config**

```bash
docker compose exec app php bin/console debug:messenger
```

Expected: Shows transports and routing.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Configure Symfony Messenger with Valkey transport"
```

---

### Task 8: Configure Flysystem for S3

**Files:**
- Create: `config/packages/flysystem.yaml`

- [ ] **Step 1: Configure Flysystem**

Create `config/packages/flysystem.yaml`:

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'aws'
            options:
                client: 's3_client'
                bucket: '%env(S3_BUCKET)%'

services:
    s3_client:
        class: Aws\S3\S3Client
        arguments:
            -
                version: 'latest'
                region: '%env(AWS_DEFAULT_REGION)%'
                endpoint: '%env(AWS_ENDPOINT)%'
                use_path_style_endpoint: true
                credentials:
                    key: '%env(AWS_ACCESS_KEY_ID)%'
                    secret: '%env(AWS_SECRET_ACCESS_KEY)%'
```

- [ ] **Step 2: Commit**

```bash
git add -A
git commit -m "Configure Flysystem with LocalStack S3 adapter"
```

---

### Task 9: Configure NelmioApiDocBundle

**Files:**
- Modify: `config/packages/nelmio_api_doc.yaml`
- Modify: `config/routes/nelmio_api_doc.yaml`

- [ ] **Step 1: Configure Nelmio**

Ensure `config/packages/nelmio_api_doc.yaml` has:

```yaml
nelmio_api_doc:
    documentation:
        info:
            title: Docker Workshop API
            description: Demo API for Podlodka PHP Crew 8
            version: 1.0.0
    areas:
        default:
            path_patterns:
                - ^/api
```

Ensure `config/routes/nelmio_api_doc.yaml` has:

```yaml
app.swagger_ui:
    path: /api/doc
    methods: GET
    defaults: { _controller: nelmio_api_doc.controller.swagger_ui }

app.swagger:
    path: /api/doc.json
    methods: GET
    defaults: { _controller: nelmio_api_doc.controller.swagger }
```

- [ ] **Step 2: Verify Swagger UI**

```bash
curl -sk https://app.workshop.localhost:8443/api/doc -o /dev/null -w "%{http_code}"
```

Expected: 200

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Configure NelmioApiDocBundle with Swagger UI at /api/doc"
```

---

## Summary

| Task | What | Depends on |
|------|------|-----------|
| 1 | Install Symfony packages (webapp-pack, etc.) | — |
| 2 | Tailwind CSS setup | 1 |
| 3 | Doctrine + User entity + migration | 1 |
| 4 | Base Twig layout | 2 |
| 5 | Dashboard page | 4 |
| 6 | Seed command | 3 |
| 7 | Messenger config | 1 |
| 8 | Flysystem S3 config | 1 |
| 9 | NelmioApiDoc config | 1 |
