# Guia de design — Resgata WP

Vale para o site público (`themes/resgata-tema` e os blocos do plugin) e para o painel
(`/painel`).

**O estilo já existe, e o código é a fonte da verdade.** Este guia diz onde o estilo mora e
quais princípios o orientam; os valores (cores, raios, sombras, tamanhos) ficam só no código.
Todo trabalho visual começa lendo o que já está feito e termina reaproveitando-o.

## Antes de desenhar ou implementar

1. **Ache o equivalente que já existe.** Procure, na tabela de [Onde o estilo mora](#onde-o-estilo-mora),
   o componente, pattern, estilo de bloco ou classe `.rwp-*` mais próximo do que você vai
   fazer, e abra o arquivo. Um cartão novo parte do cartão existente; uma tela nova do painel
   parte de uma tela vizinha.
2. **Reaproveite antes de criar.** Use o componente, o pattern ou o estilo de bloco como está.
   Se faltar uma variação, estenda o que existe no próprio arquivo dele.
3. **Use os tokens.** Cada cor, raio, sombra e espaçamento que você escrever é um preset do
   `theme.json`, uma variável `--rwp-*` ou uma variável do painel (`--primary`, `--border`...).
4. **Precisa de um valor novo?** Crie o token na fonte (`theme.json` para o site,
   `resources/painel/index.css` para o painel) e use o token. Cor nova do site entra na paleta
   do `theme.json` e ganha a sua `--rwp-*` no topo de `assets/css/comum.css`.
5. **Confira.** O trabalho está pronto quando todo valor visual da mudança aponta para um
   token existente (ou criado no passo 4), o resultado fica coerente ao lado de uma tela já
   pronta do mesmo tipo e, no site, a página continua editável no Editor do Site e legível com
   o Twenty Twenty-Five puro.

## Onde o estilo mora

### Site público

| O quê | Onde |
| --- | --- |
| Paleta, raios, sombras, tipografia, botões, links, campos, título do site e menu | `themes/resgata-tema/theme.json` |
| Paletas prontas para trocar as cores do site de uma vez (Oceano, Terracota, Ameixa, Mel, Petróleo, Framboesa, Oliva, Grafite) | `themes/resgata-tema/styles/colors/*.json` |
| Estilos de bloco ("Cartão", "Faixa suave", "Faixa suave com abraço", "Chamada do topo", "Cabeçalho do site", "Rodapé do site") | `themes/resgata-tema/styles/blocks/*.json` |
| Estilos de bloco com CSS (ícone ao lado do título, passo numerado, grupo com ilustração, botão com coração/pata, link com seta) | `resgata_tema_block_styles()` em `functions.php` + `.is-style-resgata-*` em `assets/css/blocks/core-group.css` e `core-button.css` |
| Visual dos blocos do plugin (classes `.rwp-*`) | `themes/resgata-tema/assets/css/blocks/<bloco>.css`, um arquivo por bloco |
| Variáveis `--rwp-*` e peças que mais de um bloco usa (botões, selos, etiquetas, setas) | `themes/resgata-tema/assets/css/comum.css` (variáveis no topo) |
| Selos de situação do animal (`.rwp-badge--*`) | `comum.css`; única regra com cor fixa |
| Enfeites de fundo das faixas e dos cartões (corações, patas, nuvens, círculos) | formas e base em `themes/resgata-tema/assets/css/enfeites.css`; a composição de cada peça, no CSS do bloco dela |
| Composição das páginas e seções | `themes/resgata-tema/templates/`, `parts/` e `patterns/` |
| Marcação neutra dos blocos | `plugins/resgata-wp/templates/` e `blocks/` |
| CSS mínimo para outros temas | `plugins/resgata-wp/assets/css/frontend.css` |
| Ícones | `Core\Icons::svg()` (Lucide) e `Icons::brand()` (redes sociais) |

### Painel

| O quê | Onde |
| --- | --- |
| Cores, raio base e fonte | `plugins/resgata-wp/resources/painel/index.css` |
| Componentes de base (botão, selo, campo, popover, tabela...) | `resources/painel/components/ui/` |
| Componentes do projeto (`PageHeader`, `Field`, `OptionSelect`, `DatePicker`, `MaskedInput`, `ImageDropzone`, `RichText`, `Stepper`, filtros...) | `resources/painel/components/` |
| Estrutura das telas (menu, cabeçalho, largura) | `components/layout.tsx` e as telas em `pages/` |
| Ícones | `lucide-react`, um import por ícone |

Site e painel compartilham a mesma paleta: ao mudar uma cor da marca, mude nos dois lugares.

## Padrão de temas do WordPress

O `resgata-tema` é um block theme filho do Twenty Twenty-Five e segue o padrão de temas de
blocos do WordPress. Na prática:

- **`theme.json` primeiro.** A aparência é declarada em `settings` (presets) e `styles`
  (elementos e blocos). CSS próprio só para o que o `theme.json` não expressa, e sempre com as
  variáveis `--wp--preset--*` / `--rwp-*`.
- **Herde do tema pai.** Fontes, tamanhos de texto, espaçamentos e os estilos `text-display` e
  `text-subtitle` vêm do Twenty Twenty-Five; o tema filho declara só o que muda.
- **Blocos nativos e seus recursos.** Layout com Grupo (grade, linha, empilhado), Colunas,
  Botões; variações visuais como estilos de bloco, que aparecem no painel "Estilos".
- **Tudo editável no Editor do Site.** Templates e parts em HTML de blocos; patterns em
  marcação de blocos pura, com texto traduzível; conteúdo dinâmico por ligações de blocos
  (`resgata-wp/link`, `resgata-wp/organization`).
- **Aparência no tema, funcionalidade no plugin.** O plugin entrega marcação neutra com
  classes `.rwp-*` e funciona com qualquer tema; o tema é quem a estiliza.
- **Padrões de código do WordPress.** Prefixo `resgata_tema_`, text domain `resgata-tema`,
  toda saída escapada, strings pelas funções de tradução, PHPCS (`make phpcs`).

As regras detalhadas de patterns, ligações e estilos de bloco estão em `themes/CLAUDE.md`; a
do registro de blocos, em `plugins/CLAUDE.md`.

### Skills que ajudam no trabalho visual

Quem usa um assistente de código tem, em `.claude/skills/`, as skills oficiais do WordPress
(versionadas no repositório). Para trabalho visual, carregue a da área antes de começar:

| Vai mexer em | Skill |
| --- | --- |
| `theme.json`, modelos, parts e estilos de bloco | `wp-block-themes` |
| Patterns (`patterns/*.php`) | `wp-patterns` |
| Blocos do plugin e a prévia deles no editor | `wp-block-development` |
| Telas do wp-admin e do editor (componentes do WordPress) | `wpds` |

As skills trazem o padrão geral do WordPress; este guia e os arquivos `CLAUDE.md` trazem as escolhas do
projeto e valem quando houver diferença. O painel (`/painel`) não usa os componentes do
WordPress: ele segue a tabela "Painel" acima.

## Princípios

O que o código não conta sozinho: use para decidir quando houver mais de um caminho.

1. **Acolhedor e confiável.** Verde como cor de ação, muito branco, fotos grandes e felizes
   dos animais. O animal aparece bem cuidado; a emoção vem da esperança.
2. **A foto é a protagonista.** Cartões, banners e a página do animal começam pela imagem; o
   texto apoia.
3. **Uma ação principal por bloco.** Um botão verde cheio; as demais ações são contornadas ou
   links.
4. **Leve e arredondado.** A borda fina delimita o cartão; a sombra é quase invisível e só
   cresce em elementos que flutuam. Fundos bem claros separam as seções.
5. **Simples para quem não é técnico.** Rótulos curtos, ícone ao lado do texto, linguagem do
   dia a dia, tanto para o visitante quanto para a protetora no painel.
6. **Celular primeiro.** Alvos de toque de 44px; grades de 4 colunas viram 2 e depois 1.
7. **Cor de apoio tem significado.** Azul, laranja, roxo e vermelho distinguem categorias
   (informação, destaque, "Não pode adotar?", erro) e a situação do animal; a etiqueta sempre
   traz o texto junto da cor.
8. **Um conjunto de ícones.** Lucide em traço, em todo lugar; a pata é o ícone da marca.
   Logotipos de redes sociais vêm de `Icons::brand()`.

## Ilustrações

Ilustração entra onde não há foto: estados vazios do site e do painel, página não encontrada,
agradecimentos. Ela nunca disputa espaço com a foto de um animal.

Para o conjunto parecer uma família só, toda ilustração gerada por inteligência artificial usa
o mesmo bloco de estilo: o prompt é a descrição da cena seguida deste trecho, sem mudanças.

```
Flat vector illustration, friendly and warm, rounded shapes, soft thick outlines with rounded line caps, minimal detail, no gradients, no shading, no texture. Limited palette: forest green #2F7D55, pale green #E6F2EA, dark navy #14213D for outlines, warm orange #F0932B as a small accent, white. The animal looks healthy, well cared for and happy. A few small decorative hearts, paw prints and soft cloud shapes floating around. Centered composition with generous empty space around it, transparent background, square 1:1 format. No text, no letters, no numbers, no logos, no watermark, no frame.
```

- **Cores.** São as da paleta padrão do `theme.json` (`accent-3`, `accent-2`, `contrast` e
  `accent-1`), escritas em hexadecimal porque o gerador de imagens não lê tokens: se a paleta
  padrão mudar, atualize o bloco. Em cena de apadrinhamento ou doação, troque o verde por
  `help` (`#6D3FD9`) e o verde claro por `help-soft` (`#F0EAFD`).
- **Mesmo traço.** Gere a primeira imagem até acertar e use-a como referência de estilo nas
  seguintes, quando o gerador aceitar imagem de referência.
- **Sem texto na imagem.** A mensagem vem do site, pelas funções de tradução; se sair letra ou
  número, gere de novo.
- **Tom.** Animal bem cuidado e feliz; a emoção vem da esperança (princípio 1).
- **Arquivo.** Gere em PNG com fundo transparente e guarde o original, convertido para WebP
  no tamanho inteiro (qualidade 90), em `docs/ilustracoes/`.
  No tema entra a versão recortada, em WebP com até 560px, em
  `themes/resgata-tema/assets/img/ilustracoes/`, exibida como fundo por CSS (é enfeite, fica
  fora da leitura de tela). Como ligar a imagem a uma tela: `themes/CLAUDE.md`.
- **Licença.** Só entra no repositório imagem cujos termos de uso permitam redistribuir em um
  projeto GPL.

## Acessibilidade

- Contraste mínimo de 4,5:1 em texto e 3:1 em ícones e bordas de campo. O laranja `accent-1`
  com texto branco fica abaixo disso em tamanho normal: use texto escuro sobre ele.
- Texto sobre foto sempre com degradê ou película garantindo o contraste.
- Foco sempre visível, no padrão de `:focus-visible` já usado no CSS dos blocos (`assets/css/`).
- Botões só com ícone levam rótulo acessível; ícone de enfeite leva `aria-hidden="true"`.
- Carrosséis funcionam por teclado e só avançam por ação da pessoa.
- Animações curtas e discretas, desligadas em `prefers-reduced-motion`.

## Voz e textos

- Português do Brasil, primeira pessoa do plural, tom caloroso e direto: "Resgatamos, cuidamos
  e encontramos novas famílias".
- Botões começam com verbo ou com "Quero": "Quero adotar", "Quero ser padrinho", "Ver perfil".
- Títulos de seção curtos, com uma linha de apoio embaixo.
- Valores em real (`R$ 1.250,00`), datas em `dd/mm/aaaa`.
