# Resgata WP – comandos de desenvolvimento. Rode `make help` para ver a lista.
SHELL := /bin/bash
WP := docker compose run --rm -T wpcli wp
PLUGIN := plugins/resgata-wp

# Node e Composer: usa os da máquina quando existem (o Node, só se a versão servir); senão,
# roda os dois pelo Docker, para o projeto exigir apenas Docker e Make.
# `USE_DOCKER=1 make ...` força o Docker.
DOCKER_RUN := docker run --rm -u $(shell id -u):$(shell id -g) -e HOME=/tmp -v "$(CURDIR)":/app -w /app
NODE_OK := $(shell node -e 'const [a, b] = process.versions.node.split( "." ).map( Number ); process.exit( ( a === 22 && b >= 22 ) || ( a === 24 && b >= 15 ) || a >= 26 ? 0 : 1 )' 2>/dev/null && echo 1)
COMPOSER_OK := $(shell command -v composer >/dev/null 2>&1 && echo 1)

ifeq ($(USE_DOCKER),1)
NODE_OK :=
COMPOSER_OK :=
endif

NPM := $(if $(NODE_OK),npm,$(DOCKER_RUN) node:24 npm)
COMPOSER := $(if $(COMPOSER_OK),composer,$(DOCKER_RUN) composer:2 composer)

.DEFAULT_GOAL := help
.PHONY: help setup up down clean install seed unseed wp logs composer npm build watch typecheck test zip phpcs phpcbf check pot stripe stripe-secret stripe-setup

help: ## Mostra esta ajuda
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

setup: up $(PLUGIN)/vendor build install seed ## Prepara tudo do zero: dependências, build, WordPress instalado e animais de exemplo
	@./scripts/install.sh --resumo

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

composer: ## Instala as dependências de desenvolvimento (PHPCS) e as do plugin (biblioteca da Stripe)
	$(COMPOSER) install
	$(COMPOSER) --working-dir=$(PLUGIN) install --no-dev

npm: ## Instala as dependências de JavaScript/TypeScript do plugin
	$(NPM) --prefix $(PLUGIN) ci

build: $(PLUGIN)/node_modules ## Gera os arquivos do painel, dos blocos e do site (pasta build/)
	$(NPM) --prefix $(PLUGIN) run build

watch: $(PLUGIN)/node_modules ## Recompila a cada alteração, para desenvolver
	$(NPM) --prefix $(PLUGIN) run start

typecheck: $(PLUGIN)/node_modules ## Confere os tipos do TypeScript
	$(NPM) --prefix $(PLUGIN) run typecheck

test: ## Testa as permissões da API do painel e o processamento dos webhooks da Stripe
	$(WP) eval-file wp-content/plugins/resgata-wp/tests/rest-permissions.php
	$(WP) eval-file wp-content/plugins/resgata-wp/tests/stripe-webhooks.php

stripe-setup: ## Cria os planos de apadrinhamento e o portal do cliente no sandbox da Stripe
	$(WP) resgata stripe-setup

stripe-secret: ## Mostra o segredo dos webhooks locais (cole em STRIPE_WEBHOOK_SECRET no .env)
	docker compose --profile stripe run --rm stripe listen --print-secret

stripe: ## Repassa os eventos do sandbox da Stripe para o site local (deixe rodando)
	docker compose --profile stripe up stripe

zip: build ## Gera dist/resgata-wp.zip (plugin) e dist/resgata-tema.zip (tema), prontos para instalar
	$(COMPOSER) --working-dir=$(PLUGIN) install --no-dev --optimize-autoloader
	@mkdir -p dist && rm -f dist/resgata-wp.zip dist/resgata-tema.zip
	@# Cada pacote leva a sua cópia da licença (a GPL pede que ela acompanhe o programa).
	@cp LICENSE $(PLUGIN)/LICENSE && cp LICENSE themes/resgata-tema/LICENSE
	@# O plugin leva só o que roda no site: o que fica de fora está em .distignore.
	@sed -e '/^#/d' -e '/^$$/d' -e 's|^/||' $(PLUGIN)/.distignore \
		| awk '{ print "resgata-wp/" $$0; print "resgata-wp/" $$0 "/*" }' > dist/.plugin-exclude
	cd plugins && zip -rq ../dist/resgata-wp.zip resgata-wp -x '*/.DS_Store' -x@../dist/.plugin-exclude
	@rm -f dist/.plugin-exclude
	cd themes && zip -rq ../dist/resgata-tema.zip resgata-tema -x '*/.DS_Store'
	@rm -f $(PLUGIN)/LICENSE themes/resgata-tema/LICENSE
	@echo "Pacotes gerados em dist/resgata-wp.zip e dist/resgata-tema.zip"

phpcs: vendor ## Verifica o padrão de código (WordPress Coding Standards)
	$(COMPOSER) run-script phpcs

phpcbf: vendor ## Corrige automaticamente o que for possível
	$(COMPOSER) run-script phpcbf || true

check: ## Roda o Plugin Check no plugin
	$(WP) plugin is-installed plugin-check || $(WP) plugin install plugin-check
	$(WP) plugin activate plugin-check
	$(WP) plugin check resgata-wp --exclude-directories=vendor,node_modules,resources,tests,.wordpress-org

# O .pot do plugin inclui as frases do painel, lidas do build (o WP-CLI não lê TypeScript).
# Roda com mais memória, por causa do tamanho do build, e como o usuário da máquina, para
# conseguir gravar na pasta languages/.
POT := docker compose run --rm -T --user $(shell id -u):$(shell id -g) -e HOME=/tmp wpcli php -d memory_limit=2G /usr/local/bin/wp i18n make-pot

pot: build ## Gera os arquivos .pot de tradução do plugin e do tema
	$(POT) wp-content/plugins/resgata-wp wp-content/plugins/resgata-wp/languages/resgata-wp.pot --exclude=vendor,node_modules,tests
	$(POT) wp-content/themes/resgata-tema wp-content/themes/resgata-tema/languages/resgata-tema.pot

vendor:
	$(COMPOSER) install

$(PLUGIN)/vendor:
	$(COMPOSER) --working-dir=$(PLUGIN) install --no-dev

$(PLUGIN)/node_modules:
	$(NPM) --prefix $(PLUGIN) ci
