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
    frontend/               scripts do site público (galeria da página do animal)
  build/                    gerado por `make build`; fora do git
  tests/                    testes rodados por `make test`
  assets/css/               CSS do site e do wp-admin (sem build)
themes/resgata-tema/        style.css, functions.php, theme.json, templates/, patterns/, assets/
docker/mu-plugins/          somente desenvolvimento (envia e-mails para o Mailpit)
.claude/skills/             skills oficiais do WordPress (WordPress/agent-skills), versionadas
designer.md                 guia de design do site e do painel
```

## Skills e documentos de apoio

- `.claude/skills/` guarda, no git, só as skills oficiais do WordPress (lista e uso em
  `.claude/skills/README.md`). Antes de mexer em uma área, carregue a skill dela:
  `wp-rest-api` (rotas do painel), `wp-block-development` (blocos), `wp-block-themes` e
  `wp-patterns` (tema), `wp-plugin-development` (estrutura e segurança do plugin),
  `wp-wpcli-and-ops` (comandos `wp resgata`), `blueprint`/`wp-playground` (demonstração no
  Playground). Na dúvida, `wordpress-router` aponta a certa.
- As skills são genéricas; quando uma skill e este arquivo divergirem, **vale este arquivo**
  (ex.: o painel não usa `@wordpress/components`, apesar da `wpds`).
- Skills pessoais ficam só na máquina de cada pessoa: o `.gitignore` recusa `.agents/`,
  `skills-lock.json` e tudo em `.claude/skills/` fora da lista liberada. Não versione skill
  que não seja do WordPress, e não crie subpastas em `.claude/skills/` (o assistente só lê
  `.claude/skills/<nome>/SKILL.md`).
- Trabalho visual (site ou painel): leia o `designer.md` antes.

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
  `uninstall.php`). Hoje existem as tabelas do apadrinhamento. `Schema::install()` também reaplica
  perfis/permissões e agenda a recriação das regras de URL.
- Commits pequenos e descritivos, em português.

## TypeScript e build

- **TypeScript é o padrão** para todo JavaScript do plugin. O código fica em `resources/` e é
  compilado por `@wordpress/scripts` (`make build`; `make watch` ao desenvolver). Sem o build,
  o painel e os scripts do editor/wp-admin não carregam (o site público continua funcionando).
- `webpack.config.js` tem três configurações:
  - `painel`: **React 19 embutido**. Só `@wordpress/api-fetch` e `@wordpress/i18n` ficam
    externos. **Nunca** importe no painel pacotes do WordPress que dependem de React
    (`@wordpress/components`, `@wordpress/data`, `@wordpress/element`): eles rodariam no
    React 18 do WordPress, ao lado do nosso.
  - `site`: `resources/site/selects.tsx`, que troca os `<select data-rwp-select>` das páginas
    públicas pelo Select do shadcn/ui (o mesmo `components/ui/select.tsx` do painel), também
    com React embutido (~120 KB comprimido). Só é carregado onde há um `<select>` (filtros da
    lista de animais e formulário de adoção), via `Blocks::enqueue_select_assets()`. O
    `<select>` nativo continua na página, escondido, e é ele que o formulário envia; sem o
    build, o visitante usa o campo nativo. O CSS (`selects.css`) traz só as classes do
    componente, sem o reset do Tailwind, e as cores vêm do `theme.json`.
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
- Identidade (site e painel, ver `designer.md`): verde `#2F7D55` em botões, links e destaques
  (`--primary`/`--brand` no painel; `accent-3` no `theme.json`), títulos em azul-marinho
  `#14213D`, fundo `#FAFBFC`, cartões brancos com borda `#E3E8EE`. Desenhe primeiro para
  celular (alvos de toque de 44px).
- Node 22.22+ ou 24.15+ (exigência do `@wordpress/scripts`).

## Painel (/painel)

- Aplicação React em página própria, sem tema e sem `wp_head()`: `Router` cria a regra de URL
  e confere o acesso; `Page` imprime o HTML e os dados iniciais em `window.rwpPainel`.
- Acesso pela capability `rwp_access_panel` (administrador, editor e Protetor admin).
  O menu mostra Início, Animais, Adoções, Padrinhos, Banners e Configurações (para quem pode) e
  Minha conta.
- Perfil **Protetor admin** (`rwp_protetor_admin`): cópia do Editor; `Access` o redireciona de
  qualquer tela do wp-admin para `/painel` (AJAX liberado) e esconde a barra do WordPress.
  O administrador usa os dois, com atalhos de ida e volta.
- Quem não está logado vai para o login padrão do WordPress e volta para a tela pedida.
- API: toda rota fica em `resgata-wp/v1` e é registrada por `BaseController::route()`, que
  nega por padrão (login + `rwp_access_panel` + verificação própria da rota) e valida os
  parâmetros pelo schema. Nunca chame `register_rest_route()` direto para o painel. A única
  rota pública do namespace é o webhook da Stripe (ver Módulo Apadrinhamento).
- Fotos: enviadas pelo painel ao endpoint do WordPress `wp/v2/media`, já reduzidas no
  aparelho (`lib/image.ts`, máx. 2000 px).
- Componentes reutilizáveis do painel (`resources/painel/components/`): `OptionSelect` (Select
  do shadcn a partir de um mapa valor => rótulo), `DatePicker` (Popover + Calendar, valor
  "AAAA-MM-DD"), `ImageDropzone` (arrastar, escolher ou tirar foto; envia e devolve a imagem),
  `RichText` (Tiptap), `Stepper` (passos de formulário em etapas), `MaskedInput` (campo com
  máscara de `lib/masks.ts`: `maskCnpj`, `maskPhone` ou `createMask( '#####-###' )`), `Field`,
  `PageHeader`. Não use `<select>` nem `<input type="date">`
  nativos no painel.
- Painéis de filtro seguem `components/animal-filters.tsx`: Popover no computador e Sheet
  deslizando da direita no celular (`useMediaQuery`), aplicados só ao tocar em "Aplicar". Na
  API, filtros de lista chegam separados por vírgula e são validados por `enum` no schema.
- Temperamento: caixas de seleção com a lista de `Options::temperaments()` (filtro
  `rwp_temperament_options`); os nomes são gravados como termos de `rwp_temperament`.
- Largura: o conteúdo vai até 1400px; formulários usam duas colunas a partir de `md`.
- Telas: Início (cartões de resumo que levam às listas já filtradas: animais por situação,
  pedidos de adoção novos e padrinhos ativos; "Precisa da sua atenção" com pedidos sem resposta
  e rascunhos; atalhos. Os números vêm de `GET /dashboard`, que devolve `null` nas áreas que a
  pessoa não pode ver), Animais (tabela, 25/50/75/100 por página; busca por nome e filtros por período do
  resgate, publicação, espécie, sexo, situação, porte e castração, guardados na URL; situação e
  publicação trocadas direto na linha, com atualização otimista), cadastro em 4 passos
  (rascunho ao concluir o passo 1; publicar no último; no cabeçalho da edição dá para
  alternar entre Publicado e Rascunho), Minha conta.
- `make test` roda `tests/rest-permissions.php` (e `tests/stripe-webhooks.php`): descobre todas as rotas do namespace e
  confere 401 para visitante, 403 para assinante e 2xx para Protetor admin e Administrador.
  Cria e apaga usuários e animais temporários no site de desenvolvimento.

## Tema e Editor do Site

O `resgata-tema` é editável por inteiro em Aparência → Editor. Para continuar assim:

- **Patterns em marcação de blocos pura** (`patterns/*.php`): só texto traduzível com
  `esc_html_e()`. Nada de consultar páginas, opções ou o plugin dentro do pattern, nem
  `return` condicional: o que o pattern imprime vira conteúdo salvo quando alguém edita.
- **Links e textos dinâmicos vêm de ligações de blocos** (`Core\Bindings`, WP 6.5+):
  `resgata-wp/link` (chaves `animals`, `help`, `donate`, `sponsorship`, `contact`, `home`; se a
  página não existe, vale a próxima da lista de reserva) no `url` de um botão, e
  `resgata-wp/organization` (`name`, `tagline`, `copyright`) no `content` de um parágrafo. O
  editor mostra os valores reais (`rwpBlocks.bindings`). Listas de links (rodapé) usam o
  bloco `resgata-wp/links`, que só lista as páginas existentes.
- **Aparência no `theme.json` e em estilos de bloco**, não em classes soltas: cores, raios
  (`settings.border.radiusSizes`), sombras, botões (inclusive o contornado), campos de
  formulário, título do site e menu ficam no `theme.json`. Estilos de bloco declarados em
  `styles/blocks/*.json` ("Cartão", "Faixa suave", "Chamada do topo", "Cabeçalho do site",
  "Rodapé do site") e, quando precisam de CSS (ícones, numeração), registrados em
  `resgata_tema_block_styles()` com o CSS em `assets/css/resgata-wp.css`
  (`.is-style-resgata-*`). Todos aparecem no painel "Estilos" do bloco.
- **Layout com os recursos nativos** do bloco Grupo: grade (`columnCount` +
  `minimumColumnWidth`), linha, empilhado; títulos com os estilos `text-display` e
  `text-subtitle` herdados do Twenty Twenty-Five.
- **Blocos do plugin são registrados no `init` com prioridade 20 ou 21**: o WordPress
  acrescenta os atributos de suporte (alinhamento, espaçamento) na prioridade 22, e um bloco
  registrado depois falha na prévia do editor ("Erro ao carregar o bloco").
- Para conferir depois de mexer em modelos ou patterns: abrir o Editor do Site e validar a
  marcação (`wp.blocks.parse()` sem blocos `isValid: false` nem `core/missing`).

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
  `rwp_dewormed_date`, `rwp_neutered`, `rwp_fiv`, `rwp_felv`, `rwp_tagline` (frase de
  apresentação), `rwp_intro` (resumo), `rwp_weight` (kg), `rwp_vet_checked`, `rwp_vaccinated`
  e `rwp_microchip` (sim / ainda não).
- A situação do animal é a meta `rwp_status`, não o status do post. Lista pública padrão:
  `available` e `reserved`.
- Idade: o cadastro pede anos e meses; grava-se a data de nascimento estimada, que só muda se
  a idade informada for diferente da calculada.
- `AnimalWriter::save()` é o único caminho de escrita da ficha, usado pelo wp-admin e pela API
  do painel; as regras de validação ficam em `Meta.php`.
- Cadastro pelo painel (`/painel/animais`) e também pelo wp-admin, na tela clássica (editor de
  blocos desligado para o CPT, porque ele esconde as metaboxes num painel recolhido).
- Página do animal: o bloco `resgata-wp/animal-details` (template `animal-details.php`,
  dados montados em `Frontend\DetailsRenderer`) desenha a página inteira (caminho, fotos,
  resumo, história, saúde, "Não pode adotar?" e outros animais) ou uma seção só
  (atributo `section`). Os modelos da página usam só esse bloco e o formulário de adoção. A
  página não mostra localização. O botão "Quero adotar" vem do filtro `rwp_animal_adopt_url`
  (módulo de adoção); os links de "Não pode adotar?", do filtro `rwp_animal_help_links`.
- Ícones do site: `Core\Icons::svg()` (Lucide em SVG embutido; no painel, `lucide-react`).
- A galeria funciona sem JavaScript (rolagem lateral); `resources/frontend/animal-gallery.ts`
  só acrescenta setas e miniaturas.
- Lista de animais: bloco `resgata-wp/animals-list` (templates `animals-list.php` e
  `animal-card.php`): filtros, contagem, cartões (foto, situação, nome, resumo de
  `Animal::card_summary()`, até três temperamentos e "Ver perfil") e paginação. O arquivo
  `/animais/` usa 4 colunas.
- Visual: a paleta fica no `theme.json` do `resgata-tema` (site inteiro); antes de mexer em
  aparência, siga o `designer.md`, que manda partir do estilo que já existe. O
  CSS dos blocos (`assets/css/resgata-wp.css`) só usa as variáveis `--rwp-*`, declaradas uma
  vez no topo do arquivo a partir das cores do `theme.json`; não escreva cor fixa nas regras
  (exceção: selos de situação).
- Todo `<select>` novo em template público leva o atributo `data-rwp-select` e quem o imprime
  chama `Blocks::enqueue_select_assets()`.
- Público: blocos `resgata-wp/animals-list` e `resgata-wp/animal-details`, shortcode
  `[rwp_animais]`, filtros por GET (`rwp_especie`, `rwp_sexo`, `rwp_porte`, `rwp_pg`).
- WP-CLI: `wp resgata seed-animals [--count=<n>] [--skip-images] [--delete]`.

## Configurações da protetora (implementado)

- Tela em `/painel/configuracoes`; dados na option `rwp_organization`
  (`Modules/Settings/Organization.php`: campos, tamanhos máximos e sanitização em um lugar só).
- Capability `rwp_manage_settings`: Administrador e Protetor admin (o Editor comum não).
- Campos: identidade (nome, frase, CNPJ), contato (WhatsApp, mensagem inicial, telefone, e-mail,
  horário), redes (Instagram, Facebook, TikTok, YouTube; aceita link ou @usuario), localização
  (cidade, UF, bairro; nunca endereço completo), adoção (e-mail interno, taxa, regras),
  privacidade (responsável, texto) e Pix (tipo, chave, titular).
- `adoption_email` é interno (`Organization::PRIVATE_FIELDS`) e nunca vai para o site.
- O link do WhatsApp é gerado por `Organization::whatsapp_url()` (troca `{animal}` na mensagem).
- Site: bloco `resgata-wp/contact` (template `contact.php`): WhatsApp em destaque, um cartão
  por dado (telefone, e-mail, horário, localização, CNPJ), redes sociais com o logotipo de cada
  uma (`Icons::brand()`, que usa os ícones sociais do WordPress) e a chave Pix com o botão
  "Doar com QR Code" quando existe a página "Como ajudar". Ao salvar as configurações pela
  primeira vez, `ContactPage::ensure()` cria a página "Contato" (option `rwp_contact_page_id`);
  se a página for apagada, é recriada no próximo salvamento. O conteúdo e o modelo vêm dos
  filtros `rwp_contact_page_content` e `rwp_contact_page_template`; o tema os usa para aplicar
  o pattern `resgata-tema/pagina-contato`.
- A tela "Resgata WP → Configurações" do wp-admin (módulos ativos e retenção de dados) continua
  só para o Administrador.

## Banners e página inicial (implementado)

- Banners: tela `/painel/banners` (capability `rwp_manage_settings`; no celular, o acesso é por
  Configurações). Até 5 banners, cada um com foto, título, final do título em verde, texto e
  dois botões com link; dá para ligar/desligar e reordenar. Dados na option `rwp_banners`
  (`Modules/Settings/Banners.php`: limites, sanitização e renderização em um lugar só).
- Links dos botões: endereço completo `http(s)://`, caminho do site (`/animais/`) ou âncora;
  qualquer outra coisa é recusada (`Banners::sanitize_url()`).
- Site: bloco `resgata-wp/banners` (template `banners.php`). Sem banner ligado, mostra um banner
  padrão com a frase da protetora. Com mais de um, rolam para o lado (mesmo script da galeria
  do animal; não avançam sozinhos).
- Bloco `resgata-wp/impact` ("Números da protetora", template `impact.php`): animais
  resgatados, adoções, esperando um lar e padrinhos ativos, contados dos cadastros (filtro
  `rwp_impact_items`).
- `Core\Links`: endereços da lista de animais, da página de apadrinhamento (a que tem o bloco
  dos planos), da "Como ajudar" (a que tem o bloco de doação por Pix) e da página de contato;
  vazio quando a página não existe.
- Página inicial (tema): `templates/front-page.html` com banners, atalhos, animais para adoção,
  banner de doação, números e chamada final. As seções são patterns (`patterns/home-*.php`);
  o cabeçalho (`parts/header.html` → pattern `cabecalho`) tem o botão "Quero ajudar".
- Rodapé (tema): `parts/footer.html` → pattern `rodape`: nome do site e frase da protetora
  (a das Configurações do painel; sem ela, a "Descrição do site" do WordPress), links das
  páginas que existem ("O site" e "Ajude"), contato resumido e linha de direitos (com a
  política de privacidade, se houver). O contato vem do bloco `resgata-wp/contact` com
  `compact: true` (template `contact-compact.php`): WhatsApp, telefone, e-mail, local e redes
  sociais em ícones.

## Doação por Pix e página "Como ajudar" (implementado)

- Sem módulo próprio e sem Stripe: usa a chave Pix das Configurações (`Organization::pix()`
  devolve a chave, o titular e os valores já no formato do QR Code).
- Bloco `resgata-wp/pix-donation` (template `pix-donation.php`, `Settings\PixDonation`): QR
  Code, valores sugeridos (filtro `rwp_pix_amounts`), chave com botão de copiar e "Pix copia e
  cola". O código e o QR são montados no navegador pela biblioteca `qrcode-pix` (MIT), em
  `resources/frontend/pix.ts` (configuração `wp` do webpack, com o polyfill `buffer`). Sem o
  script, a página mostra só a chave e o titular.
- A página publicada que tem esse bloco é a "Como ajudar" do site (`Core\Links::help()`).
  Ao salvar as Configurações com chave Pix, `Settings\HelpPage::ensure()` cria essa página
  (option `rwp_help_page_id`); se já existir uma página com o bloco, ela é aproveitada, e se a
  página for apagada, nasce de novo no próximo salvamento. O conteúdo e o modelo vêm dos
  filtros `rwp_help_page_content` e `rwp_help_page_template`, que o tema usa para aplicar o
  seu layout.
- Bloco `resgata-wp/pix-once`: o mesmo QR Code e a mesma chave, com textos de "doação única",
  para colocar dentro de outras páginas sem que elas virem a "Como ajudar". O pattern da
  página de apadrinhamento traz esse bloco logo depois dos planos, para quem não pode ajudar
  todo mês.
- Bloco `resgata-wp/donate-banner` (template `donate-banner.php`): chamada "Doe por Pix" que
  leva a essa página. Não aparece sem chave Pix, sem a página ou na própria página. No tema,
  está na página inicial, na lista de animais e na página do animal.
- Tema: pattern `resgata-tema/pagina-como-ajudar` (chamada, atalhos, Pix, apadrinhamento,
  números e animais), para usar com o modelo "Página sem título". O botão "Quero ajudar" do
  cabeçalho e o atalho "Faça uma doação" levam a essa página.
- Não há registro das doações: o Pix cai direto na conta. Doações com confirmação automática
  continuam previstas para o módulo de doações avulsas pela Stripe.

## Módulo Pedidos de adoção (implementado)

- Depende do módulo Animais. Código em `src/Modules/Adoptions/`.
- Fluxo: a pessoa preenche o questionário na página do animal → o pedido é gravado na fila
  interna → a tela de confirmação mostra o botão "Enviar pedido pelo WhatsApp", que abre a
  conversa com o WhatsApp das Configurações já com o pedido escrito (`Messages::whatsapp_text()`).
  Se houver `adoption_email` nas Configurações, a protetora também recebe um e-mail só com nome,
  animal e link do painel (as respostas não vão por e-mail).
- Dados: CPT `rwp_adoption`, **não público** (sem tela no wp-admin, fora da REST do WordPress e
  das buscas), status de post `private`, título = nome de quem pediu. Metas:
  `rwp_adoption_status` (new, reviewing, interview, approved, declined),
  `rwp_adoption_animal_id`, `rwp_adoption_answers`, `rwp_adoption_consent` (data + texto exato
  aceito) e `rwp_adoption_notes` (anotações internas).
- Capability `rwp_manage_adoptions`: Administrador e Protetor admin (o Editor comum não).
- Perguntas em `Questionnaire::fields()` (filtro `rwp_adoption_questionnaire_fields`): a mesma
  definição monta o formulário, valida, alimenta o painel e a mensagem do WhatsApp.
- Formulário: bloco `resgata-wp/adoption-form` (templates `adoption-form.php` e
  `adoption-success.php`), já incluído nos modelos da página do animal. Só aparece para animal
  publicado e "disponível". Em outra página, mostra um seletor de animais disponíveis
  (`?rwp_animal=ID` pré-seleciona).
- Envio (`Submission`): POST na própria página, nonce, campo-armadilha contra robôs, limite de 5
  pedidos aceitos por hora por endereço de rede (guardado só como resumo irreversível) e
  redirecionamento com código de uso único (`?rwp_pedido=`) para a confirmação.
- Consentimento (LGPD): caixa obrigatória; o texto vem de "Texto de privacidade" nas
  Configurações (ou de um texto padrão) e é guardado junto com o pedido.
- Painel: `/painel/adocoes` (lista com filtro por situação e troca rápida) e
  `/painel/adocoes/:id` (respostas, contato, animal, consentimento, anotações internas). O menu
  mostra o número de pedidos novos. Excluir um pedido apaga em definitivo (não há lixeira).
- Apagar um animal em definitivo apaga os pedidos ligados a ele.

## Módulo Apadrinhamento (implementado)

- Código em `src/Modules/Sponsorship/`. Desligado por padrão; só fica disponível com a
  biblioteca `stripe/stripe-php` instalada (`composer install` na pasta do plugin; o
  `make zip` inclui a pasta `vendor/`). O resto do plugin continua funcionando sem ela.
- Assinatura mensal em BRL, **só cartão**, pelo Stripe Checkout (página hospedada). Nunca
  renderize campo de cartão no site. Pix Automático não existe para contas Stripe do Brasil.
- Chaves (`Keys`): vêm das constantes `RWP_STRIPE_SECRET_KEY` (de preferência restrita, `rk_`)
  e `RWP_STRIPE_WEBHOOK_SECRET` do `wp-config.php` ou, sem elas, do que o Administrador salvou
  no painel (`PUT /sponsorship/keys`, só `manage_options`; o Protetor admin recebe 403). A
  constante tem prioridade e bloqueia o campo (409). O que vem do painel é gravado cifrado na
  option `rwp_stripe_keys` (sem autoload) por `Core\Encryption`: AES-256-CTR com IV aleatório,
  chave derivada de `RWP_ENCRYPTION_KEY`/`RWP_ENCRYPTION_SALT` ou, sem elas, de
  `LOGGED_IN_KEY`/`LOGGED_IN_SALT` (a mesma lógica do Site Kit, reescrita: o código dele é
  Apache-2.0). Sem OpenSSL ou sem essas constantes nada é gravado (não há texto puro). Se elas
  mudarem, a chave fica ilegível e o painel pede para colar de novo. As chaves nunca vão para a
  API do painel (só "definida", origem e começo + 4 últimos caracteres) nem para o log. A
  chave da API é testada na Stripe antes de ser gravada (`Gateway::verify_key()`). A option é
  apagada na desinstalação mesmo com "Manter os dados" marcado. O painel só aceita chave
  restrita (`rk_`); a completa (`sk_`) é recusada (no `wp-config.php` ela ainda funciona).
- Permissões da chave: `Gateway::permissions()` (`GET /sponsorship/permissions`) confere as
  cinco de `Gateway::PERMISSIONS` com pedidos de escrita sobre itens que não existem (nada é
  criado: "não encontrado" = liberada, "sem permissão" = pendente). O cartão "Pagamentos
  (Stripe)" lista cada uma com o check verde ou o alerta. Chamada nova à Stripe que exija
  outra permissão precisa entrar nessa lista e em `STRIPE_PERMISSIONS` (`pages/sponsorship.tsx`).
- `Gateway` é o único ponto que chama a API da Stripe (`StripeClient` instanciado; sem
  `payment_method_types`; `excluded_payment_method_types` esconde o boleto). As operações de
  planos passam antes pelo filtro `rwp_sponsorship_pre_stripe`, que os testes usam para não
  falar com a Stripe.
- Planos: cadastrados em `/painel/apadrinhamento` → "Planos" (`PlanService`, rotas
  `/sponsorship/plans`). Cada plano é um produto da Stripe (o nome) ligado ao preço mensal em
  vigor, com descrição, benefícios, destaque ("Mais escolhido", um só) e "No site" (à venda ou
  escondido). Mudar o valor cria outro preço no mesmo produto e guarda o antigo em
  `old_prices`: quem já assina segue no valor antigo e continua reconhecido
  (`Plans::find()`); só o preço em vigor de plano à venda pode ser comprado
  (`Plans::find_for_sale()`). Plano com padrinhos não é excluído, só escondido. A cada mudança
  o portal do cliente é atualizado com os planos à venda. A chave do site precisa de escrita
  em Products, Prices e Customer portal configurations; sem ela o painel explica o que falta.
- A option `rwp_sponsorship` guarda os planos (`plans`; o formato antigo `prices` ainda é
  lido), o ID do portal e o ajuste manual do contador. `wp resgata stripe-setup` continua
  funcionando e não apaga nomes já cadastrados.
- Tabelas (`Core\Schema`, schema 5): `rwp_sponsors` (nome, e-mail, IDs de cliente/assinatura/
  preço, situação, aviso de pagamento, datas; **sem** CPF, endereço ou dados de pagamento) e
  `rwp_stripe_events` (IDs de eventos já processados, limpos após 30 dias).
- Webhook: `POST /wp-json/resgata-wp/v1/stripe/webhook` (`WebhookController`). É a **única**
  rota pública do namespace e não usa `BaseController::route()`: a autenticação é a assinatura
  do evento, conferida em `WebhookHandler::handle()` sobre o corpo bruto. Evento repetido
  responde 200 sem efeito; evento mais antigo que o último aplicado é ignorado; falha de
  gravação libera o evento e responde 500 para a Stripe tentar de novo.
- Eventos tratados: `checkout.session.completed` (nome e e-mail), `customer.subscription.created`
  / `updated` / `deleted` (situação e plano), `invoice.paid`, `invoice.payment_failed` e
  `customer.updated`. O padrinho só existe no site depois do webhook; a página de retorno do
  Checkout apenas agradece.
- Contador público (`Plans::counter()`): assinaturas `active` + ajuste manual; a option
  `rwp_sponsorship_counter` guarda o número e a data ("Atualizado em").
- Área do padrinho (`MagicLink`): e-mail → link de uso único (30 min, só o resumo do código é
  gravado) → botão de confirmação → sessão do portal da Stripe. A resposta é a mesma para
  e-mail conhecido ou não.
- Site: blocos `resgata-wp/sponsorship-plans` e `resgata-wp/sponsor-area` (templates
  `sponsorship-plans.php` e `sponsor-area.php`).
  Os planos aparecem como cartões, um por valor, cada um com o seu botão (o botão envia o
  plano; não há mais lista de opções).
- Página de apadrinhamento (tema): pattern `resgata-tema/pagina-apadrinhamento` (chamada,
  planos, números, "Como funciona?", animais e "Já é padrinho?"), para usar com o modelo
  "Página sem título". Os blocos precisam ficar no conteúdo da página (não no modelo), porque
  é assim que `Core\Links::sponsorship()` encontra a página.
- Painel: `/painel/apadrinhamento` (menu "Padrinhos"), capability `rwp_manage_sponsorship`
  (Administrador e Protetor admin), rotas `/sponsorship` e `/sponsors`.
- Local: `make stripe-secret`, `make stripe-setup`, `make stripe` (Stripe CLI no Docker, perfil
  `stripe`). `make test` também roda `tests/stripe-webhooks.php` (eventos assinados localmente,
  sem falar com a Stripe).
- Doações avulsas (Pix): ainda não implementadas. O ponto de entrada é
  `WebhookHandler::checkout_completed()` (sessões `mode: payment`).

## Módulos previstos (ainda não implementados)

Doações avulsas por Pix (Stripe Checkout), Prestação de contas (lançamentos, fechamento
mensal, página pública).

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
make setup     do zero: dependências, build, WordPress instalado, módulos ligados e animais de exemplo
make up        sobe WordPress, MariaDB e Mailpit
make install   instala o WP em pt_BR, garante o Twenty Twenty-Five, ativa plugin e tema
make build     compila o TypeScript (painel, blocos, wp-admin)   (make watch recompila sozinho)
make typecheck confere os tipos
make test      testa as permissões da API do painel e os webhooks da Stripe
make zip       gera dist/resgata-wp.zip (plugin) e dist/resgata-tema.zip (tema) para instalar em um site
make seed      cria animais de exemplo      (make unseed remove)
make wp cmd="plugin list"                   roda qualquer comando WP-CLI
make phpcs     PHPCS                        (make phpcbf corrige o que for automático)
make check     Plugin Check
make pot       gera os .pot do plugin e do tema (roda o build antes)
make logs      logs do WordPress
make down      derruba o ambiente           (make clean também apaga banco e uploads)
```

O `make setup` exige só Docker e Make: o `Makefile` usa o Node e o Composer da máquina quando
existem (o Node, só na versão exigida) e, senão, roda os dois pelo Docker (`node:24` e
`composer:2`); `USE_DOCKER=1 make ...` força o Docker. Comando novo que use `npm` ou `composer`
deve usar as variáveis `$(NPM)` e `$(COMPOSER)`. No Windows, o caminho recomendado é o WSL.

O `.env` é criado sozinho a partir do `.env.example` (portas `WP_PORT`, padrão 8080, e
`MAILPIT_PORT`, padrão 8025; chaves de sandbox da Stripe). Usuários locais: administrador
(`admin` / `admin`) e Protetor admin de teste (`protetora` / `protetora`), definidos em
`WP_ADMIN_*` e `WP_PANEL_*`. Numa instalação nova, `scripts/install.sh` liga todos os módulos
prontos (em um site de verdade o apadrinhamento vem desligado). O `debug.log` fica em
`wp-content/debug.log` dentro do contêiner `wordpress`.

Traduções: os `.pot` ficam versionados em `languages/` do plugin e do tema; rode `make pot` ao
criar ou mudar textos. As frases do painel entram no `.pot` do plugin pelo `build/` (o WP-CLI
não lê TypeScript). O painel ainda não carrega arquivos de tradução (não há
`wp_set_script_translations`): hoje ele só existe em português.

As imagens do `docker-compose.yml` têm versão fixa (WordPress 7.1, WP-CLI 2.12, MariaDB 11.8,
Mailpit e Stripe CLI): atualize de propósito, em commit próprio, e rode `make test` depois.

Ao testar uma mudança visual, confira também com o Twenty Twenty-Five puro
(`make wp cmd="theme activate twentytwentyfive"`) e volte para `resgata-tema` depois.
