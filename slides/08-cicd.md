---
layout: section
sectionNumber: '08'
---

# Тестирование

---

# Headless-браузеры в контейнерах

<div class="accent-line"></div>

- **Playwright** — E2E-тесты, MCP-сервер для AI-агентов, скриншоты, PDF
- **Selenium + Codeception** — классика PHP: `standalone-chrome` в compose
- **Gotenberg** — REST API для генерации PDF (Chromium + LibreOffice)
- Все запускаются как Docker-сервисы — не нужен браузер на хосте

---

# Testcontainers для PHP

<div class="accent-line"></div>

- Интеграционные тесты с **реальными контейнерами** прямо из PHPUnit
- `testcontainers-php` — поднимает PostgreSQL, Redis, Meilisearch на лету
- Каждый тест получает чистый контейнер — нет shared state
- Замена моков: тестируем с настоящей БД, а не с фейковой
- Тренд 2025–2026: Testcontainers стал стандартом для integration tests

---
layout: code-block
---

```php
class DatabaseTest extends TestCase {
    private static PostgresContainer $container;

    public static function setUpBeforeClass(): void {
        self::$container = (new PostgresContainer())
            ->withPostgresUser('test')
            ->withPostgresPassword('test')
            ->withPostgresDatabase('testdb')
            ->start();
    }

    public function testConnection(): void {
        $pdo = new \PDO(sprintf(
            'pgsql:host=%s;port=%d;dbname=testdb',
            self::$container->getHost(),
            self::$container->getFirstMappedPort()
        ), 'test', 'test');
        $this->assertNotFalse($pdo->query('SELECT 1'));
    }
}
```

---
layout: compare
alt: true
---

# Аудит и безопасность образов

<div class="comparison-grid">
  <CompareCard title="Hadolint" description="Линтер Dockerfile" />
  <CompareCard title="Dockle" description="Аудит безопасности образов" />
  <CompareCard title="Trivy" description="Сканер уязвимостей (CVE)" />
  <CompareCard title="Docker Scout" description="Встроенный SBOM + CVE анализ" />
</div>
