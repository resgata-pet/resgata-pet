# Plugin `resgata-pet`

Toda a funcionalidade do kit. Tem de funcionar com qualquer tema: nenhuma regra de negócio
depende do `resgata-tema`.

## Mapa

```
resgata-pet/
  resgata-pet.php         cabeçalho, constantes RESGATA_*, autoload, hooks de ativação
  uninstall.php           só apaga dados se "Manter os dados" estiver desmarcado
  src/Plugin.php          bootstrap (plugins_loaded): carrega os módulos ativos
  src/Core/               Activator, Assets, Bindings, Capabilities, Encryption, Html, Icons,
                          Links, Modules, Schema, TemplateLoader
  src/Modules/<Módulo>/   uma classe por responsabilidade
  blocks/<bloco>/         block.json (apiVersion 3) + render.php
  templates/              templates PHP neutros, sobrescrevíveis pelo tema
  resources/              TypeScript: painel/, site/, frontend/, blocks/editor.tsx
  build/                  gerado por `make build`; fora do git
  assets/css/             CSS do site (sem build)
  tests/                  rodados por `make test`
  .wordpress-org/         ícone e imagens da página do plugin no WordPress.org; fora do pacote
```

## Módulos

Antes de mexer em um módulo, leia o documento dele (pasta `docs/`, na raiz do repositório):

| Área | Código | Documento |
| --- | --- | --- |
| Painel `/painel` (React) e a API `resgata-pet/v1` | `src/Modules/Panel/`, `resources/painel/` | `docs/painel.md` |
| Animais | `src/Modules/Animals/` | `docs/animais.md` |
| Pedidos de adoção | `src/Modules/Adoptions/` | `docs/adocoes.md` |
| Apadrinhamento (Stripe) | `src/Modules/Sponsorship/` | `docs/apadrinhamento.md` |
| Configurações da protetora, contato, banners, números e Pix | `src/Modules/Settings/` | `docs/configuracoes-e-site.md` |

Previstos, ainda sem código: doações avulsas por Pix (Stripe Checkout) e prestação de contas
(lançamentos, fechamento mensal, página pública, conciliação OFX).

Os módulos ligam e desligam em Resgata Pet → Configurações (option `resgata_modules`). Para criar
um módulo:

1. Acrescente-o em `Core\Modules::all()` e em `AVAILABLE`.
2. Registre-o em `Plugin::boot()`.
3. Registre as rotas dele em `PanelModule::register_rest_routes()`.
4. Escreva o `docs/<módulo>.md` e a linha dele na tabela acima.

## Padrões PHP

- Slug e text domain `resgata-pet`, prefixo `resgata_` (constantes `RESGATA_`), namespace `ResgataPet`
  com autoload PSR-4 em `src/`. O arquivo principal tem um autoloader de reserva: o plugin
  funciona sem `composer install`.
- Funções de tradução só a partir do hook `init`. O que roda em `plugins_loaded`
  (`Modules::is_enabled()`, `Capabilities::grant()`) usa rótulos sem tradução.
- O PHPCS confere o prefixo `resgata_` em tudo o que é global (sniff `PrefixAllGlobals`).
- Capability nova entra em `custom_capabilities` no `phpcs.xml.dist`.
- Tabela própria (dados financeiros): `dbDelta` em `Core\Schema`. A cada mudança, incremente
  `RESGATA_DB_VERSION`, liste a tabela em `table_names()` e remova-a no `uninstall.php`.
  `Schema::install()` também reaplica perfis e permissões e agenda a recriação das regras de
  URL.
- HTML vindo do painel (texto rico) passa por `wp_kses_post()` no servidor.

## API do painel

Toda rota fica em `resgata-pet/v1` e é registrada por `BaseController::route()`, que nega por
padrão (login + `resgata_access_panel` + verificação própria da rota) e valida os parâmetros pelo
schema. A única rota pública do namespace, e a única registrada fora de `route()`, é o webhook
da Stripe.

Rota nova entra sozinha em `tests/rest-permissions.php`, que descobre todas as rotas do
namespace e confere 401 para visitante, 403 para assinante e 2xx para Protetor admin e
Administrador. O teste cria e apaga usuários e animais temporários no site de desenvolvimento.

## Blocos e templates

- Registre os blocos no `init` com prioridade **20 ou 21**. O WordPress acrescenta os
  atributos de suporte (alinhamento, espaçamento) na prioridade 22, e um bloco registrado
  depois falha na prévia do editor ("Erro ao carregar o bloco").
- O `render.php` monta os dados e chama um template de `templates/` pelo `TemplateLoader`. A
  marcação é neutra, com classes `.resgata-*`; o visual é do tema.
- Nada sai sem escape, nem com `phpcs:ignore` (a revisão do WordPress.org cobra isso): HTML
  montado por template sai com `wp_kses( $html, Html::allowed() )`, ícones com
  `wp_kses( Icons::svg( ... ), Html::svg() )` e os atributos do wrapper com `wp_kses_data()`.
  Template dentro de template usa `TemplateLoader::display()`. Tag ou atributo novo no HTML
  entra em `Core\Html`, senão o `wp_kses()` o remove.
- Todo `<select>` de template público leva o atributo `data-resgata-select`, e quem o imprime
  chama `Blocks::enqueue_select_assets()`.
- Ícones do site: `Core\Icons::svg()` (Lucide em SVG embutido) e `Icons::brand()` (redes
  sociais, com os ícones do WordPress).
- Endereços das páginas do site: `Core\Links` (lista de animais, apadrinhamento, "Como
  ajudar", contato); devolve vazio quando a página não existe.
- `Core\Bindings` fornece ao tema os links e textos dinâmicos dos patterns (uso em
  `themes/CLAUDE.md`).

## TypeScript e build

**TypeScript é o padrão** para todo JavaScript do plugin. O código fica em `resources/` e é
compilado por `@wordpress/scripts` (`make build`; `make watch` ao desenvolver; Node 22.22+ ou
24.15+). Sem o build, o painel e os scripts do editor não carregam; o site público continua
funcionando.

O `webpack.config.js` tem três configurações:

- `painel`: **React 19 embutido**. Do WordPress, o painel importa só `@wordpress/api-fetch` e
  `@wordpress/i18n`, que ficam externos. Os pacotes do WordPress que dependem de React
  (`@wordpress/components`, `@wordpress/data`, `@wordpress/element`) rodariam no React 18 do
  WordPress, ao lado do nosso: ficam fora do painel.
- `site`: `resources/site/selects.tsx` troca os `<select data-resgata-select>` das páginas
  públicas pelo Select do shadcn/ui (o mesmo `components/ui/select.tsx` do painel), também
  com React embutido (~120 KB comprimido), carregado só onde há um `<select>`. O `<select>`
  nativo continua na página, escondido, e é ele que o formulário envia; sem o build, o
  visitante usa o campo nativo. O `selects.css` traz só as classes do componente, sem o reset
  do Tailwind, e as cores vêm do `theme.json`.
- `wp`: blocos e scripts do site, com o React e os pacotes do próprio WordPress.

Scripts do site público melhoram o que já funciona sem eles: a galeria rola de lado sem
JavaScript, a página do Pix mostra a chave sem o QR Code.

As frases do painel entram no `.pot` pelo `build/` (o WP-CLI não lê TypeScript). O painel
ainda não carrega arquivos de tradução (falta `wp_set_script_translations`): hoje ele só
existe em português.

## Pacote

`make zip` gera `dist/resgata-pet.zip` só com o que roda no site: o código PHP, `blocks/`,
`templates/`, `assets/`, `languages/`, `build/`, a pasta `vendor/` (biblioteca da Stripe) e uma
cópia da `LICENSE` da raiz. O que fica de fora está em `plugins/resgata-pet/.distignore`
(`resources/`, `tests/`, `node_modules/`, `.wordpress-org/` e os arquivos de configuração do
build): arquivo novo que não roda no site entra ali. O `readme.txt` é em **inglês** (regra do
WordPress.org) e aponta para o repositório como a origem do que está compilado em `build/`. Este arquivo e os de `docs/` ficam fora do pacote de propósito:
documentação para agentes mora fora de `plugins/resgata-pet/`.
