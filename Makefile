# Resgata WP – comandos de desenvolvimento. Rode `make help` para ver a lista.
SHELL := /bin/bash
WP := docker compose run --rm -T wpcli wp
PLUGIN := plugins/resgata-wp

.DEFAULT_GOAL := help
.PHONY: help setup up down clean install seed unseed wp logs composer npm build watch typecheck test zip phpcs phpcbf check pot

help: ## Mostra esta ajuda
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

setup: up build install seed ## Sobe o ambiente, gera o build, instala o WordPress e popula dados de exemplo

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

npm: ## Instala as dependências de JavaScript/TypeScript do plugin
	npm --prefix $(PLUGIN) ci

build: $(PLUGIN)/node_modules ## Gera os arquivos do painel, dos blocos e do wp-admin (pasta build/)
	npm --prefix $(PLUGIN) run build

watch: $(PLUGIN)/node_modules ## Recompila a cada alteração, para desenvolver
	npm --prefix $(PLUGIN) run start

typecheck: $(PLUGIN)/node_modules ## Confere os tipos do TypeScript
	npm --prefix $(PLUGIN) run typecheck

test: ## Testa as permissões de todas as rotas da API do painel
	$(WP) eval-file wp-content/plugins/resgata-wp/tests/rest-permissions.php

zip: build ## Gera dist/resgata-wp.zip, pronto para instalar em um site
	@mkdir -p dist && rm -f dist/resgata-wp.zip
	cd plugins && zip -rq ../dist/resgata-wp.zip resgata-wp \
		-x 'resgata-wp/node_modules/*' 'resgata-wp/vendor/*' 'resgata-wp/tests/*' '*/.DS_Store'
	@echo "Pacote gerado em dist/resgata-wp.zip"

phpcs: vendor ## Verifica o padrão de código (WordPress Coding Standards)
	vendor/bin/phpcs

phpcbf: vendor ## Corrige automaticamente o que for possível
	vendor/bin/phpcbf || true

check: ## Roda o Plugin Check no plugin
	$(WP) plugin is-installed plugin-check || $(WP) plugin install plugin-check
	$(WP) plugin activate plugin-check
	$(WP) plugin check resgata-wp --exclude-directories=vendor,node_modules,resources,tests

pot: ## Gera os arquivos .pot de tradução do plugin e do tema
	$(WP) i18n make-pot wp-content/plugins/resgata-wp wp-content/plugins/resgata-wp/languages/resgata-wp.pot --exclude=vendor,node_modules,tests
	$(WP) i18n make-pot wp-content/themes/resgata-tema wp-content/themes/resgata-tema/languages/resgata-tema.pot

vendor:
	composer install

$(PLUGIN)/node_modules:
	npm --prefix $(PLUGIN) ci
