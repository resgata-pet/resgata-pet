#!/usr/bin/env bash
# Instala o WordPress em pt_BR e ativa o plugin e o tema do Resgata WP.
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
	cp .env.example .env
	echo "Arquivo .env criado a partir de .env.example."
fi

set -a
# shellcheck disable=SC1091
. ./.env
set +a

WP="docker compose run --rm -T wpcli wp"
URL="http://localhost:${WP_PORT:-8080}"

resumo() {
	echo
	echo "Pronto!"
	echo "  Site:            $URL"
	echo "  Painel Resgata:  $URL/painel    usuário: ${WP_PANEL_USER:-protetora}   senha: ${WP_PANEL_PASSWORD:-protetora}"
	echo "  WordPress:       $URL/wp-admin  usuário: ${WP_ADMIN_USER:-admin}   senha: ${WP_ADMIN_PASSWORD:-admin}"
	echo "  E-mails de teste: http://localhost:${MAILPIT_PORT:-8025}"
}

# `--resumo` só mostra os endereços e os acessos (usado no fim do `make setup`).
if [ "${1:-}" = "--resumo" ]; then
	resumo
	exit 0
fi

echo "Aguardando os arquivos do WordPress..."
for _ in $(seq 1 60); do
	if $WP core version >/dev/null 2>&1; then
		break
	fi
	sleep 2
done

NOVO=0
if ! $WP core is-installed >/dev/null 2>&1; then
	NOVO=1
	$WP core install \
		--url="$URL" \
		--title="${WP_TITLE:-Protetora Exemplo}" \
		--admin_user="${WP_ADMIN_USER:-admin}" \
		--admin_password="${WP_ADMIN_PASSWORD:-admin}" \
		--admin_email="${WP_ADMIN_EMAIL:-admin@example.org}" \
		--skip-email
fi

$WP language core install pt_BR --activate
$WP option update timezone_string "America/Sao_Paulo"
$WP option update date_format "d/m/Y"
$WP rewrite structure '/%postname%/'

# O tema filho depende do Twenty Twenty-Five.
$WP theme is-installed twentytwentyfive || $WP theme install twentytwentyfive
$WP language theme install twentytwentyfive pt_BR || true

$WP plugin activate resgata-wp
$WP theme activate resgata-tema

# Em uma instalação nova, liga todos os módulos prontos, para o ambiente já mostrar o kit
# inteiro (o apadrinhamento vem desligado por padrão em um site de verdade).
if [ "$NOVO" = "1" ]; then
	$WP option update resgata_modules '["animals","adoptions","sponsorship"]' --format=json
fi

# Usuário de teste do painel (perfil "Protetor admin").
if ! $WP user get "${WP_PANEL_USER:-protetora}" --field=ID >/dev/null 2>&1; then
	$WP user create "${WP_PANEL_USER:-protetora}" "${WP_PANEL_EMAIL:-protetora@example.org}" \
		--role=resgata_protetor_admin \
		--user_pass="${WP_PANEL_PASSWORD:-protetora}" \
		--display_name="Protetora Teste"
fi

$WP rewrite flush

resumo
