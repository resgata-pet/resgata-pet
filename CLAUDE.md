# Resgata Pet

Kit open source (GPL-2.0-or-later) para WordPress, para protetoras independentes de animais:
divulgar animais e receber pedidos de adoção, arrecadar com apadrinhamento mensal e Pix, e
prestar contas publicamente.

Quem usa o painel é **não técnico**: a usabilidade vem primeiro. Rótulos claros, textos de
ajuda curtos, linguagem do dia a dia, desenho para celular antes do computador.

## Regra de ouro

**Funcionalidade no plugin, aparência no tema.**

- `plugins/resgata-pet/`: dados, regras de negócio, formulários, painel, blocos e templates
  neutros. Funciona com qualquer tema.
- `themes/resgata-tema/`: block theme filho do Twenty Twenty-Five. Só `theme.json`, modelos,
  parts, patterns e o CSS dos blocos do plugin.

Como os dois conversam:

- Template PHP: `TemplateLoader::locate()` procura em `<tema>/resgata-pet/<arquivo>.php` e
  depois em `plugins/resgata-pet/templates/`. As variáveis chegam em `$resgata_args`.
- Tema de blocos: o plugin registra os modelos `single-resgata_animal` e `archive-resgata_animal`
  (`register_block_template`); o tema os substitui com arquivos de mesmo nome em `templates/`.
- Tema clássico: `template_include` usa `single-animal.php` / `archive-animal.php`.
- `add_theme_support( 'resgata-pet' )` faz o plugin registrar o handle `resgata-frontend` vazio; o
  tema estiliza as classes `.resgata-*`.

## Onde está cada instrução

Leia o documento da área **antes** de editar nela:

| Vai mexer em | Leia |
| --- | --- |
| Qualquer coisa em `plugins/` | `plugins/CLAUDE.md` (padrões do plugin, build, API, blocos) |
| Qualquer coisa em `themes/` | `themes/CLAUDE.md` (Editor do Site, patterns, estilos) |
| Um módulo ou o painel | `docs/<área>.md`, listados em `plugins/CLAUDE.md` |
| Aparência do site ou do painel | `designer.md` (os valores visuais ficam só no código) |
| Uma API do WordPress | a skill da área em `.claude/skills/` (lista em `.claude/skills/README.md`; na dúvida, `wordpress-router`) |

## Padrões do repositório

- PHP 8.1+, WordPress 6.9+, só APIs nativas do WordPress (sem ACF nem outro plugin).
- Strings escritas em **português do Brasil** no código e sempre passadas pelas funções de
  tradução. Rode `make pot` ao criar ou mudar textos.
- Dependência nova precisa de licença compatível com GPLv2 (MIT e BSD servem; Apache-2.0 não).
- WordPress Coding Standards via PHPCS (`phpcs.xml.dist`).
- Commits pequenos e descritivos, em português.

## Segurança (obrigatório)

- Todo formulário e ação: nonce **e** `current_user_can()`.
- Sanitize toda entrada (`sanitize_text_field`, `absint`, `sanitize_email`...) e escape toda
  saída (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- Toda query própria com `$wpdb->prepare()`.
- Dados de adotantes, padrinhos e finanças ficam atrás de permissão: todo endpoint tem
  `permission_callback` explícito.
- Credenciais: só ambiente local e chaves de sandbox, no `.env` (fora do git).

## Ambiente e verificação

- `make help` lista os comandos. `make setup` sobe tudo do zero (só Docker e Make);
  `make wp cmd="..."` roda qualquer comando WP-CLI.
- O `Makefile` usa o Node e o Composer da máquina ou, na falta deles, o Docker. Comando novo
  que use `npm` ou `composer` usa as variáveis `$(NPM)` e `$(COMPOSER)`.
- Site local em `http://localhost:8080` (`WP_PORT`), e-mails no Mailpit em `:8025`. Usuários:
  `admin` / `admin` (Administrador) e `protetora` / `protetora` (Protetor admin).
- O `debug.log` fica em `wp-content/debug.log` dentro do contêiner `wordpress`.
- As imagens do `docker-compose.yml` têm versão fixa: atualize de propósito, em commit
  próprio, e rode `make test` depois.

**Pronto** quer dizer: `make phpcs`, `make typecheck`, `make test` e `make check` passando, e
os pontos sensíveis de segurança da mudança listados para revisão humana.
