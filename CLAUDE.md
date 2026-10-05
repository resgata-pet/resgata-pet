# Resgata WP

Kit open source (GPL-2.0-or-later) para WordPress, voltado a protetoras independentes de
animais. Objetivos: divulgar animais e receber pedidos de adoção; arrecadar com
apadrinhamento mensal e doações avulsas; prestar contas publicamente (com conciliação OFX).

O painel é usado por pessoas **não técnicas**: a usabilidade do admin é prioridade. Rótulos
claros, textos de ajuda curtos, nada de jargão.

## Regra de ouro

**Funcionalidade no plugin, aparência no tema.**

- `plugins/resgata-wp/`: post types, metadados, tabelas, formulários, configurações,
  shortcodes, blocos e templates neutros. Tem de funcionar com qualquer tema. Nenhuma lógica
  de negócio depende do tema.
- `themes/resgata-tema/`: block theme filho do Twenty Twenty-Five. Só `theme.json`,
  templates, parts, patterns e CSS dos blocos do plugin. Sem post types, metadados ou
  formulários.

## Estrutura

```
plugins/resgata-wp/
  resgata-wp.php            cabeçalho, constantes RWP_*, autoload, hooks de ativação
  uninstall.php             só apaga dados se "Manter os dados" estiver desmarcado
  src/Plugin.php            bootstrap (plugins_loaded): carrega os módulos ativos
  src/Core/                 Activator, Deactivator, Capabilities, Modules, Schema, TemplateLoader
  src/Modules/<Módulo>/     uma classe por responsabilidade
  blocks/<bloco>/           block.json (apiVersion 3) + render.php
  templates/                templates PHP neutros, sobrescrevíveis pelo tema
  assets/css|js/            sem etapa de build (JS puro)
themes/resgata-tema/        style.css, functions.php, theme.json, templates/, patterns/, assets/
docker/mu-plugins/          somente desenvolvimento (envia e-mails para o Mailpit)
.claude/skills/             skills oficiais do WordPress (WordPress/agent-skills)
```

## Padrões técnicos

- PHP 8.1+, WordPress 6.9+. Sem plugins de terceiros (nada de ACF): só APIs nativas.
- Plugin: slug e text domain `resgata-wp`, prefixo `rwp_` (constantes `RWP_`), namespace
  `ResgataWP` com autoload PSR-4 (`src/`). Há um autoloader de reserva no arquivo principal,
  então o plugin funciona sem `composer install`.
- Tema: slug e text domain `resgata-tema`, funções com prefixo `resgata_tema_`.
- Strings: escritas em **português do Brasil** no código e sempre passadas pelas funções de
  tradução (`__()`, `esc_html__()`...). Não chamar funções de tradução antes do hook `init`
  (ex.: `Modules::is_enabled()` roda em `plugins_loaded` e por isso não usa rótulos).
- WordPress Coding Standards via PHPCS (`phpcs.xml.dist` na raiz). O sniff `PrefixAllGlobals`
  está desligado porque recusa prefixos de 3 letras; confira o prefixo `rwp_` em revisão.
- Módulos ligam/desligam em Resgata WP → Configurações (option `rwp_modules`). Para criar um
  módulo: adicionar em `Core\Modules::all()` e `AVAILABLE`, e registrar em `Plugin::boot()`.
- Dados financeiros: tabela própria com `dbDelta` em `Core\Schema` (incrementar
  `RWP_DB_VERSION` a cada mudança; listar a tabela em `table_names()` e removê-la no
  `uninstall.php`). Ainda não existe nenhuma tabela.
- Blocos: renderizados no servidor (`render.php`), registrados no editor por
  `assets/js/blocks-editor.js` sem build. Estilo pelo handle `rwp-frontend`.
- Commits pequenos e descritivos, em português.

## Como plugin e tema conversam

- Template PHP: `TemplateLoader::locate()` procura em `<tema>/resgata-wp/<arquivo>.php` e
  depois em `plugins/resgata-wp/templates/`. Variáveis chegam em `$rwp_args`.
- Temas de blocos: o plugin registra os modelos `single-rwp_animal` e `archive-rwp_animal`
  (`register_block_template`); o tema substitui com arquivos de mesmo nome em `templates/`.
- Temas clássicos: `template_include` usa `single-animal.php` / `archive-animal.php`.
- `add_theme_support( 'resgata-wp' )` faz o plugin registrar o handle `rwp-frontend` vazio
  (sem CSS); o tema estiliza as classes `.rwp-*`.

## Módulo Animais (implementado)

- CPT `rwp_animal` (arquivo `/animais/`, REST `wp/v2/animais`), capabilities próprias
  (`edit_rwp_animals`...), concedidas a administrador e editor na ativação.
- Taxonomias: `rwp_species` (escolhida num select da ficha; fora da REST) e
  `rwp_temperament` (etiquetas).
- Metas (`Meta.php`, todas com `show_in_rest => false`): `rwp_status`, `rwp_sex`, `rwp_size`,
  `rwp_color`, `rwp_birth_date`, `rwp_rescue_date`, `rwp_gallery`, `rwp_vaccines`,
  `rwp_dewormed_date`, `rwp_neutered`, `rwp_fiv`, `rwp_felv`.
- A situação do animal é a meta `rwp_status`, não o status do post. Lista pública padrão:
  `available` e `reserved`.
- Idade: o painel pede anos e meses; grava-se a data de nascimento estimada, que só muda se a
  idade informada for diferente da calculada.
- Edição na tela clássica (editor de blocos desligado para o CPT), porque o editor de blocos
  esconde as metaboxes num painel recolhido.
- Público: blocos `resgata-wp/animals-list` e `resgata-wp/animal-details`, shortcode
  `[rwp_animais]`, filtros por GET (`rwp_especie`, `rwp_sexo`, `rwp_porte`, `rwp_pg`).
- WP-CLI: `wp resgata seed-animals [--count=<n>] [--skip-images] [--delete]`.

## Módulos previstos (ainda não implementados)

Pedidos de adoção (questionário com consentimento LGPD, fila interna, e-mail), Apadrinhamento
(planos com links de gateway externo, contador manual), Prestação de contas (lançamentos,
fechamento mensal, página pública), e o restante das Configurações (dados da protetora, Pix,
redes sociais, texto de privacidade).

## Regras de segurança (obrigatórias)

- Todo formulário e ação: nonce **e** `current_user_can()`.
- Sanitizar toda entrada (`sanitize_text_field`, `absint`, `sanitize_email`...) e escapar toda
  saída (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- Toda query própria com `$wpdb->prepare()`.
- Dados de adotantes, padrinhos e finanças nunca expostos publicamente nem pela REST sem
  permissão; todo endpoint com `permission_callback` explícito.
- Nunca usar credenciais reais: só ambiente local e chaves de sandbox, em `.env` (fora do git).
- Ao terminar cada módulo: rodar `make phpcs` e `make check`, e listar os pontos sensíveis de
  segurança para revisão humana.

## Comandos

```
make setup     sobe o ambiente, instala o WordPress e cria animais de exemplo
make up        sobe WordPress, MariaDB e Mailpit
make install   instala o WP em pt_BR, garante o Twenty Twenty-Five, ativa plugin e tema
make seed      cria animais de exemplo      (make unseed remove)
make wp cmd="plugin list"                   roda qualquer comando WP-CLI
make phpcs     PHPCS                        (make phpcbf corrige o que for automático)
make check     Plugin Check
make pot       gera os .pot
make logs      logs do WordPress
make down      derruba o ambiente           (make clean também apaga banco e uploads)
```

As portas vêm do `.env` (`WP_PORT`, padrão 8080; `MAILPIT_PORT`, padrão 8025). Usuário local:
`admin` / `admin`. O `debug.log` fica em `wp-content/debug.log` dentro do contêiner
`wordpress`.

Ao testar uma mudança visual, confira também com o Twenty Twenty-Five puro
(`make wp cmd="theme activate twentytwentyfive"`) e volte para `resgata-tema` depois.
