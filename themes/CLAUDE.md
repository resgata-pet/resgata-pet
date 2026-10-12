# Tema `resgata-tema`

Block theme filho do Twenty Twenty-Five. Só aparência: `theme.json`, modelos, parts, patterns
e o CSS dos blocos do plugin. Post types, metadados, formulários e regras de negócio ficam no
plugin.

Slug e text domain `resgata-tema`; funções com prefixo `resgata_tema_`.

Antes de qualquer mudança visual, leia o `designer.md` (raiz): ele manda partir do estilo que
já existe e diz onde cada estilo mora. Carregue também a skill da área: `wp-block-themes`
(`theme.json`, modelos, parts, estilos de bloco) ou `wp-patterns` (patterns).

## Editável por inteiro no Editor do Site

Tudo o que o tema entrega continua editável em Aparência → Editor. Quatro regras garantem
isso:

- **Patterns em marcação de blocos pura** (`patterns/*.php`): o único PHP é texto traduzível
  com `esc_html_e()`. O que o pattern imprime vira conteúdo salvo quando alguém edita, então
  consultas a páginas, opções ou ao plugin e `return` condicional ficam fora dele.
- **Links e textos dinâmicos vêm de ligações de blocos** (`Core\Bindings`, no plugin):
  - `resgata-wp/link` no `url` de um botão, com as chaves `animals`, `help`, `donate`,
    `sponsorship`, `contact` e `home`. Se a página não existe, vale a próxima da lista de
    reserva.
  - `resgata-wp/organization` no `content` de um parágrafo, com `name`, `tagline` e
    `copyright`.
  - Listas de links (rodapé) usam o bloco `resgata-wp/links`, que só lista as páginas que
    existem.
  - O editor mostra os valores reais (`rwpBlocks.bindings`).
- **Aparência no `theme.json` e em estilos de bloco**, em vez de classes soltas. Cores, raios
  (`settings.border.radiusSizes`), sombras, botões (inclusive o contornado), campos de
  formulário, título do site e menu ficam no `theme.json`. Os estilos de bloco ("Cartão",
  "Faixa suave", "Chamada do topo", "Cabeçalho do site", "Rodapé do site") são declarados em
  `styles/blocks/*.json`; os que precisam de CSS (ícones, numeração) são registrados em
  `resgata_tema_block_styles()`, com o CSS em `assets/css/blocks/core-group.css` e
  `core-button.css` (`.is-style-resgata-*`). Todos aparecem no painel "Estilos" do bloco.
- **Paletas prontas** (Editor → Estilos → Cores) em `styles/colors/*.json`: cada uma traz as
  15 cores do `theme.json`, com os mesmos slugs, porque a paleta escolhida substitui a do tema
  inteira. Os arquivos têm de propósito os nomes dos do Twenty Twenty-Five (`01-evening.json`
  a `08-midnight.json`): o WordPress descarta a variação do tema pai que tem o mesmo nome de
  arquivo, e assim as paletas e os estilos completos dele, que não conhecem as nossas cores
  extras, saem da lista. O nome que a pessoa vê é o `title`. Cor nova no `theme.json` entra
  em todas as paletas. Só paletas claras: o CSS dos blocos tem texto branco fixo sobre a cor
  principal.
- **Layout com os recursos nativos** do bloco Grupo: grade (`columnCount` +
  `minimumColumnWidth`), linha, empilhado. Títulos com os estilos `text-display` e
  `text-subtitle` herdados do Twenty Twenty-Five.

## CSS dos blocos do plugin

`functions.php` declara `add_theme_support( 'resgata-wp' )`: o plugin deixa de enviar o CSS
dele e o tema estiliza as classes `.rwp-*`.

O CSS é dividido por bloco, em `assets/css/blocks/`, e cada página carrega só os arquivos dos
blocos que tem (o editor carrega todos). A lista bloco → arquivo fica em
`resgata_tema_block_css_files()`, no `functions.php`: bloco novo do plugin ganha um arquivo e
uma linha ali. `assets/css/comum.css` traz as variáveis `--rwp-*` e as peças que mais de um
bloco usa (botões, selos, etiquetas, setas) e acompanha qualquer arquivo de bloco do plugin.
Os arquivos `core-*.css` são os ajustes dos blocos do WordPress (Grupo, Botão, Navegação,
Paginação da consulta e Conteúdo).

Os enfeites de fundo (corações, patas, nuvens e círculos) da "Chamada do topo", da "Faixa
suave", do banner de doação, dos números, dos cartões de plano e do cartão "Não pode adotar?"
vêm de `assets/css/enfeites.css`, que traz as formas (`--resgata-tema-shape-*`) e a base (os
pseudo-elementos `::before` e `::after` da peça, atrás do conteúdo). A composição de cada peça
fica no arquivo CSS do bloco dela, nas variáveis `--resgata-tema-decor-fill` (formas cheias) e
`--resgata-tema-decor-line` (formas em traço); a cor vem de `--resgata-tema-decor-tone`. Peça
nova com enfeite entra na lista de seletores do `enfeites.css`. No celular, só enfeites nos
cantos, longe do texto.

As ilustrações ficam em `assets/img/ilustracoes/` (WebP, recortadas e com até 560px; os
originais, em `docs/ilustracoes/`, fora do pacote do tema). São enfeite, então entram como
fundo de um `::before`, por CSS: na lista de animais vazia, pelas classes `.rwp-empty--none` e
`.rwp-empty--filtered` que o plugin entrega (`animals-list.css`); em modelos e patterns, pelos
estilos "Com ilustração" do bloco Grupo (`.is-style-resgata-art-*`, em `core-group.css`).
Ilustração nova segue o bloco de estilo do `designer.md`.

A faixa "Seja um padrinho" (pattern `home-chamada`) usa o estilo de Grupo "Faixa suave com
abraço" (`styles/blocks/faixa-com-abraco.json`, com as mesmas cores e espaços da "Faixa
suave"): a foto `seja-um-padrinho.webp` fica apoiada na base da faixa, por CSS, no `::after`
(`core-group.css`), no lugar dos enfeites em traço. É a única imagem em estilo de foto; as
demais são ilustrações.

O CSS sai depois dos estilos globais (gancho `enqueue_block_assets`), e as regras `.rwp-*`
contam com essa ordem: por isso o tema não usa `wp_enqueue_block_style()`, que o colocaria
antes.

As regras dos blocos do plugin usam só as variáveis `--rwp-*`, declaradas uma vez no topo do
`comum.css` a partir das cores, raios e sombras do `theme.json`. A única cor fixa são os
selos de situação do animal (`.rwp-badge--*`). O painel tem a mesma paleta em outro arquivo:
ao mudar uma cor da marca, mude nos dois (`designer.md`).

## Modelos, parts e patterns

- `templates/single-rwp_animal.html` e `archive-rwp_animal.html` substituem os modelos que o
  plugin registra. A página do animal usa só os blocos `resgata-wp/animal-details` e
  `resgata-wp/adoption-form`; o arquivo `/animais/` usa o pattern `animais-chamada` (título e
  apresentação) e a lista em 4 colunas. Modelo `.html` não é traduzível: todo texto fica em
  pattern.
- Template PHP do plugin: para sobrescrever um, crie `resgata-wp/<arquivo>.php` na pasta do
  tema.
- `templates/front-page.html`: banners, atalhos, animais para adoção, banner de doação,
  números e chamada final. As seções são os patterns `home-*`.
- Blog: `templates/home.html` (página de publicações), `archive.html` (categoria, etiqueta,
  autor e data) e `single.html` (publicação). A lista é o pattern `blog-lista` (consulta
  herdada da página, cartões em até 3 colunas e paginação); o topo do blog é o `blog-chamada`
  e o fim da publicação traz o `blog-mais-publicacoes`. Sem publicações, a lista mostra um Grupo
  "Com ilustração: gato dormindo". O cartão da publicação é um Grupo
  "Cartão" sem espaço interno, com a foto no topo; ao mudar o cartão, mude nos dois patterns.
  Navegação entre publicações e comentários vêm dos patterns do Twenty Twenty-Five. A
  paginação (bloco do WordPress) é estilizada em
  `assets/css/blocks/core-query-pagination.css`, igual à da lista de animais.
- `templates/404.html` → pattern `pagina-nao-encontrada`: Grupo "Com ilustração: cachorrinho
  perdido", com os botões "Ver os animais" e "Voltar ao início", e o banner de doação.
- `parts/header.html` → pattern `cabecalho`, com o botão "Quero ajudar" (leva à "Como
  ajudar").
- `parts/footer.html` → pattern `rodape`: nome do site e frase da protetora, links das páginas
  que existem ("O site" e "Ajude"), contato resumido (bloco `resgata-wp/contact` com
  `compact: true`) e linha de direitos, com a política de privacidade se houver.
- Patterns de página, para usar com o modelo "Página sem título": `pagina-contato`,
  `pagina-como-ajudar` (chamada, atalhos, Pix, apadrinhamento, números e animais) e
  `pagina-apadrinhamento` (chamada, planos, doação única por Pix, números, "Como funciona?",
  animais e "Já é padrinho?").
- As páginas "Contato" e "Como ajudar" são criadas pelo plugin; o tema aplica o pattern e o
  modelo a elas pelos filtros `rwp_contact_page_content` / `rwp_contact_page_template` e
  `rwp_help_page_content` / `rwp_help_page_template`.
- A página "Obrigado" (agradecimento do apadrinhamento) também é criada pelo plugin; o tema
  só aplica o modelo sem título (`rwp_thanks_page_template`). O CSS do bloco
  `resgata-wp/sponsorship-thanks` fica em `assets/css/blocks/sponsorship.css`.
- O plugin acha a página de apadrinhamento e a "Como ajudar" pelo bloco que está **no conteúdo
  da página** (`Core\Links`). Mantenha os blocos `resgata-wp/sponsorship-plans` e
  `resgata-wp/pix-donation` no pattern da página, fora do modelo.
- O bloco `resgata-wp/donate-banner` está na página inicial, na lista de animais e na página
  do animal.

## Conferir

Uma mudança em modelo, part ou pattern está pronta quando:

1. O Editor do Site abre o modelo e `wp.blocks.parse()` da marcação não devolve bloco com
   `isValid: false` nem `core/missing`.
2. O site continua legível com o Twenty Twenty-Five puro
   (`make wp cmd="theme activate twentytwentyfive"`; volte para `resgata-tema` depois).
3. `make pot` foi rodado, se algum texto mudou (o `.pot` do tema fica em `languages/`).

`make zip` gera `dist/resgata-tema.zip` com a pasta `resgata-tema/` inteira; este arquivo fica
fora do pacote de propósito.
