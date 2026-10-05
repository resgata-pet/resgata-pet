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
  src/Core/                 Activator, Deactivator, Assets, Capabilities, Modules, Schema, TemplateLoader
  src/Modules/<Módulo>/     uma classe por responsabilidade
  src/Modules/Panel/        painel /painel: Router, Page, Access e Rest/ (API resgata-wp/v1)
  blocks/<bloco>/           block.json (apiVersion 3) + render.php
  templates/                templates PHP neutros, sobrescrevíveis pelo tema
  resources/                código-fonte TypeScript
    painel/                 aplicação React do painel (pages/, components/, lib/)
    blocks/editor.tsx       registro dos blocos no editor
    admin/animal.ts         tela de cadastro do wp-admin
  build/                    gerado por `make build`; fora do git
  tests/                    testes rodados por `make test`
  assets/css/               CSS do site e do wp-admin (sem build)
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
  tradução (`__()`, `esc_html__()`...; no TypeScript, `__()` de `@wordpress/i18n`). Não
  chamar funções de tradução antes do hook `init` (ex.: `Modules::is_enabled()` e
  `Capabilities::grant()` rodam em `plugins_loaded` e por isso não usam rótulos traduzidos).
- WordPress Coding Standards via PHPCS (`phpcs.xml.dist` na raiz). O sniff `PrefixAllGlobals`
  está desligado porque recusa prefixos de 3 letras; confira o prefixo `rwp_` em revisão.
  Capabilities novas precisam ser listadas em `custom_capabilities` no `phpcs.xml.dist`.
- Módulos ligam/desligam em Resgata WP → Configurações (option `rwp_modules`). Para criar um
  módulo: adicionar em `Core\Modules::all()` e `AVAILABLE`, registrar em `Plugin::boot()` e
  registrar as rotas dele em `PanelModule::register_rest_routes()`.
- Dados financeiros: tabela própria com `dbDelta` em `Core\Schema` (incrementar
  `RWP_DB_VERSION` a cada mudança; listar a tabela em `table_names()` e removê-la no
  `uninstall.php`). Ainda não existe nenhuma tabela. `Schema::install()` também reaplica
  perfis/permissões e agenda a recriação das regras de URL.
- Commits pequenos e descritivos, em português.

## TypeScript e build

- **TypeScript é o padrão** para todo JavaScript do plugin. O código fica em `resources/` e é
  compilado por `@wordpress/scripts` (`make build`; `make watch` ao desenvolver). Sem o build,
  o painel e os scripts do editor/wp-admin não carregam (o site público continua funcionando).
- `webpack.config.js` tem duas configurações:
  - `painel`: **React 19 embutido**. Só `@wordpress/api-fetch` e `@wordpress/i18n` ficam
    externos. **Nunca** importe no painel pacotes do WordPress que dependem de React
    (`@wordpress/components`, `@wordpress/data`, `@wordpress/element`): eles rodariam no
    React 18 do WordPress, ao lado do nosso.
  - `wp`: blocos e wp-admin, com o React e os pacotes do próprio WordPress.
- Painel: Tailwind 4 (via `postcss.config.js`, em CommonJS), componentes shadcn/ui na base
  **Base UI** em `resources/painel/components/ui/` (adicionar com
  `npx shadcn@latest add <nome>` dentro de `plugins/resgata-wp`), TanStack Query para dados,
  TanStack Table (v8) para listagens, React Router para navegação, Tiptap para texto rico,
  react-day-picker para o calendário.
- **Sem `class-variance-authority`** (Apache-2.0, incompatível com GPLv2): se um componente
  novo do shadcn vier com `cva`, reescreva as variações à mão, como em `button.tsx` e
  `badge.tsx`. Sem `next-themes`: o painel só tem modo claro, e a linha `@custom-variant dark`
  em `index.css` impede que as classes `dark:` valham pelo tema do sistema.
- Do Tiptap, só os pacotes MIT (`@tiptap/react`, `pm`, `starter-kit`); nada "Pro".
- Identidade do painel: sálvia `#8FA98F` (`--brand`) e sálvia escuro `#4F6B53` (`--primary`,
  para botões e links, por contraste). Desenhe primeiro para celular (alvos de toque de 44px).
- Node 22.22+ ou 24.15+ (exigência do `@wordpress/scripts`).

## Painel (/painel)

- Aplicação React em página própria, sem tema e sem `wp_head()`: `Router` cria a regra de URL
  e confere o acesso; `Page` imprime o HTML e os dados iniciais em `window.rwpPainel`.
- Acesso pela capability `rwp_access_panel` (administrador, editor e Protetor admin).
- Perfil **Protetor admin** (`rwp_protetor_admin`): cópia do Editor; `Access` o redireciona de
  qualquer tela do wp-admin para `/painel` (AJAX liberado) e esconde a barra do WordPress.
  O administrador usa os dois, com atalhos de ida e volta.
- Quem não está logado vai para o login padrão do WordPress e volta para a tela pedida.
- API: toda rota fica em `resgata-wp/v1` e é registrada por `BaseController::route()`, que
  nega por padrão (login + `rwp_access_panel` + verificação própria da rota) e valida os
  parâmetros pelo schema. Nunca chame `register_rest_route()` direto para o painel.
- Fotos: enviadas pelo painel ao endpoint do WordPress `wp/v2/media`, já reduzidas no
  aparelho (`lib/image.ts`, máx. 2000 px).
- Componentes reutilizáveis do painel (`resources/painel/components/`): `OptionSelect` (Select
  do shadcn a partir de um mapa valor => rótulo), `DatePicker` (Popover + Calendar, valor
  "AAAA-MM-DD"), `ImageDropzone` (arrastar, escolher ou tirar foto; envia e devolve a imagem),
  `RichText` (Tiptap), `Stepper` (passos de formulário em etapas), `Field`, `PageHeader`. Não use `<select>` nem `<input type="date">`
  nativos no painel.
- Temperamento: caixas de seleção com a lista de `Options::temperaments()` (filtro
  `rwp_temperament_options`); os nomes são gravados como termos de `rwp_temperament`.
- Largura: o conteúdo vai até 1400px; formulários usam duas colunas a partir de `md`.
- Telas: Início (só o botão "Cadastrar animal"; o painel de números vem depois dos outros
  módulos), Animais (tabela, 25/50/75/100 por página, sem filtros), cadastro em 4 passos
  (rascunho ao concluir o passo 1; publicar no último; no cabeçalho da edição dá para
  alternar entre Publicado e Rascunho), Minha conta.
- `make test` roda `tests/rest-permissions.php`: descobre todas as rotas do namespace e
  confere 401 para visitante, 403 para assinante e 2xx para Protetor admin e Administrador.
  Cria e apaga usuários e animais temporários no site de desenvolvimento.

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
- Idade: o cadastro pede anos e meses; grava-se a data de nascimento estimada, que só muda se
  a idade informada for diferente da calculada.
- `AnimalWriter::save()` é o único caminho de escrita da ficha, usado pelo wp-admin e pela API
  do painel; as regras de validação ficam em `Meta.php`.
- Cadastro pelo painel (`/painel/animais`) e também pelo wp-admin, na tela clássica (editor de
  blocos desligado para o CPT, porque ele esconde as metaboxes num painel recolhido).
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
  permissão; todo endpoint com `permission_callback` explícito (no painel, via
  `BaseController::route()`).
- HTML vindo do painel (texto rico) passa por `wp_kses_post()` no servidor.
- Nunca usar credenciais reais: só ambiente local e chaves de sandbox, em `.env` (fora do git).
- Ao terminar cada módulo: rodar `make phpcs`, `make typecheck`, `make test` e `make check`, e
  listar os pontos sensíveis de segurança para revisão humana.

## Comandos

```
make setup     sobe o ambiente, gera o build, instala o WordPress e cria animais de exemplo
make up        sobe WordPress, MariaDB e Mailpit
make install   instala o WP em pt_BR, garante o Twenty Twenty-Five, ativa plugin e tema
make build     compila o TypeScript (painel, blocos, wp-admin)   (make watch recompila sozinho)
make typecheck confere os tipos
make test      testa as permissões da API do painel
make zip       gera dist/resgata-wp.zip para instalar em um site
make seed      cria animais de exemplo      (make unseed remove)
make wp cmd="plugin list"                   roda qualquer comando WP-CLI
make phpcs     PHPCS                        (make phpcbf corrige o que for automático)
make check     Plugin Check
make pot       gera os .pot
make logs      logs do WordPress
make down      derruba o ambiente           (make clean também apaga banco e uploads)
```

As portas vêm do `.env` (`WP_PORT`, padrão 8080; `MAILPIT_PORT`, padrão 8025). Usuários locais:
administrador e Protetor admin de teste, com login e senha no `.env` (`WP_ADMIN_*` e
`WP_PANEL_*`). O `debug.log` fica em `wp-content/debug.log` dentro do contêiner
`wordpress`.

Ao testar uma mudança visual, confira também com o Twenty Twenty-Five puro
(`make wp cmd="theme activate twentytwentyfive"`) e volte para `resgata-tema` depois.
