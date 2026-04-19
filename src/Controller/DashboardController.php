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
                'icon' => "\u{1F4E7}",
                'service' => 'Mailpit',
                'serviceUrl' => 'https://mailpit.workshop.localhost:8443',
            ],
            [
                'title' => 'Хранилище (S3)',
                'description' => 'Upload файлов в LocalStack S3',
                'route' => 'storage_index',
                'icon' => "\u{1F4E6}",
                'service' => 'LocalStack',
                'serviceUrl' => 'https://s3.workshop.localhost:8443',
            ],
            [
                'title' => 'Поиск',
                'description' => 'Полнотекстовый поиск через Meilisearch',
                'route' => 'search_index',
                'icon' => "\u{1F50D}",
                'service' => 'Meilisearch',
                'serviceUrl' => 'https://search.workshop.localhost:8443',
            ],
            [
                'title' => 'Кэш',
                'description' => 'Операции с Valkey (Redis-compatible)',
                'route' => 'cache_index',
                'icon' => "\u{26A1}",
                'service' => 'Valkey',
                'serviceUrl' => null,
            ],
            [
                'title' => 'Webhooks',
                'description' => 'Приём вебхуков через Cloudflare Tunnel',
                'route' => 'webhook_index',
                'icon' => "\u{1F517}",
                'service' => 'Cloudflared',
                'serviceUrl' => null,
            ],
            [
                'title' => 'Observability',
                'description' => 'Трейсы, логи, метрики через OpenTelemetry',
                'route' => 'observability_index',
                'icon' => "\u{1F4CA}",
                'service' => 'Grafana',
                'serviceUrl' => 'https://grafana.workshop.localhost:8443',
            ],
            [
                'title' => 'Onboarding',
                'description' => 'Сквозной сценарий: регистрация пользователя',
                'route' => 'onboarding_index',
                'icon' => "\u{1F680}",
                'service' => 'Все сервисы',
                'serviceUrl' => null,
            ],
        ];

        return $this->render('dashboard/index.html.twig', [
            'demos' => $demos,
        ]);
    }
}
