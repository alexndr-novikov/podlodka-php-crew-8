.PHONY: help setup up down restart logs shell watch build \
        composer console migrate seed test \
        debug tunnel tunnel-ngrok reset lint \
        slides slides-build slides-export slides-share

.DEFAULT_GOAL := help

# Colors
BLUE := \033[34m
GREEN := \033[32m
RESET := \033[0m

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "$(BLUE)%-20s$(RESET) %s\n", $$1, $$2}'

# --- Setup ---

setup: ## First-time setup: generate TLS certs, copy .env
	@echo "$(GREEN)Generating mkcert certificates...$(RESET)"
	@command -v mkcert >/dev/null 2>&1 || { echo "Error: mkcert is not installed. Install it: https://github.com/FiloSottile/mkcert"; exit 1; }
	@mkcert -install 2>/dev/null || true
	@mkcert -cert-file docker/traefik/certs/local-cert.pem \
		-key-file docker/traefik/certs/local-key.pem \
		"workshop.localhost" "*.workshop.localhost"
	@if [ ! -f .env.local ]; then \
		cp .env .env.local; \
		echo "$(GREEN)Created .env.local from .env$(RESET)"; \
	fi
	@echo "$(GREEN)Setup complete! Run 'make up' to start.$(RESET)"

build: ## Build Docker images
	docker compose build

# --- Docker ---

up: ## Start all services
	docker compose up -d --wait

down: ## Stop all services
	docker compose down

restart: down up ## Restart all services

logs: ## Follow logs (all services)
	docker compose logs -f

shell: ## Open bash in app container
	docker compose exec app bash

watch: ## Start with file watching
	docker compose watch

# --- App ---

composer: ## Run composer command (usage: make composer ARGS="require foo/bar")
	docker compose exec app composer $(ARGS)

console: ## Run Symfony console (usage: make console ARGS="cache:clear")
	docker compose exec app php bin/console $(ARGS)

migrate: ## Run database migrations
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

seed: ## Seed database with test data
	docker compose exec app php bin/console app:seed

test: ## Run PHPUnit tests
	docker compose exec app php bin/phpunit

# --- Profiles ---

debug: ## Start with debug profile (Buggregator)
	docker compose --profile debug up -d

tunnel: ## Start with tunnel profile (Cloudflare)
	docker compose --profile tunnel up -d

tunnel-ngrok: ## Start with ngrok tunnel
	docker compose --profile tunnel-ngrok up -d

# --- Maintenance ---

reset: ## Full reset: volumes, rebuild, migrate, seed
	docker compose down -v
	docker compose up -d --wait --build
	$(MAKE) migrate
	$(MAKE) seed

lint: ## Run linters (PHPStan + CS Fixer)
	docker compose exec app vendor/bin/phpstan analyse
	docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff

# --- Slides ---

slides: ## Start Slidev dev server with HMR
	cd slides && npx slidev --port 3030 --open

slides-build: ## Build slides as static SPA
	cd slides && npx slidev build

slides-export: ## Export slides to PDF
	cd slides && npx slidev export

slides-share: ## Share slides via Cloudflare Tunnel (public URL)
	@echo "$(GREEN)Serving built slides + Cloudflare Tunnel...$(RESET)"
	@npx --yes serve slides/dist -l 3031 &>/dev/null & sleep 2 && cloudflared tunnel --url http://localhost:3030
