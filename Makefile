# Resgata WP – comandos de desenvolvimento. Rode `make help` para ver a lista.
SHELL := /bin/bash
WP := docker compose run --rm -T wpcli wp

.DEFAULT_GOAL := help
.PHONY: help setup up down clean install seed unseed wp logs composer phpcs phpcbf check pot

help: ## Mostra esta ajuda
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

setup: up install seed ## Sobe o ambiente, instala o WordPress e popula dados de exemplo

up: ## Sobe o ambiente (WordPress, MariaDB e Mailpit)
	@test -f .env || cp .env.example .env
	docker compose up -d --wait db
	docker compose up -d

down: ## Derruba o ambiente (mantém banco e uploads)
	docker compose --profile tools down

clean: ## Derruba o ambiente e APAGA banco e uploads
	docker compose --profile tools down -v

install: ## Instala o WordPress em pt_BR, garante o Twenty Twenty-Five e ativa plugin e tema
	./scripts/install.sh

seed: ## Cria animais de exemplo
	$(WP) resgata seed-animals --count=12

unseed: ## Remove os animais de exemplo
	$(WP) resgata seed-animals --delete

wp: ## Roda um comando WP-CLI. Ex.: make wp cmd="plugin list"
	$(WP) $(cmd)

logs: ## Acompanha os logs do WordPress
	docker compose logs -f wordpress

composer: ## Instala as dependências de desenvolvimento (PHPCS) e o autoload do plugin
	composer install
	composer --working-dir=plugins/resgata-wp dump-autoload

phpcs: vendor ## Verifica o padrão de código (WordPress Coding Standards)
	vendor/bin/phpcs

phpcbf: vendor ## Corrige automaticamente o que for possível
	vendor/bin/phpcbf || true

check: ## Roda o Plugin Check no plugin
	$(WP) plugin is-installed plugin-check || $(WP) plugin install plugin-check
	$(WP) plugin activate plugin-check
	$(WP) plugin check resgata-wp --exclude-directories=vendor

pot: ## Gera os arquivos .pot de tradução do plugin e do tema
	$(WP) i18n make-pot wp-content/plugins/resgata-wp wp-content/plugins/resgata-wp/languages/resgata-wp.pot --exclude=vendor
	$(WP) i18n make-pot wp-content/themes/resgata-tema wp-content/themes/resgata-tema/languages/resgata-tema.pot

vendor:
	composer install
