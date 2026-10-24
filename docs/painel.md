# Painel (`/painel`)

Aplicação React em página própria, sem tema e sem `wp_head()`. Código em
`src/Modules/Panel/` (PHP) e `resources/painel/` (`pages/`, `components/`, `lib/`, `hooks/`).
As regras de build e de importação estão em `plugins/CLAUDE.md`; o visual, em `designer.md`.

## Acesso

- `Router` cria a regra de URL e confere o acesso; `Page` imprime o HTML e os dados iniciais
  em `window.resgataPainel`.
- Entra quem tem a capability `resgata_access_panel`: Administrador, Editor e Protetor admin. Quem
  não está logado vai para o login padrão do WordPress e volta para a tela pedida.
- Perfil **Protetor admin** (`resgata_protetor_admin`): cópia do Editor. `Access` o redireciona de
  qualquer tela do wp-admin para `/painel` (AJAX liberado) e esconde a barra do WordPress.
- O Administrador usa os dois lados. No wp-admin, o link "Painel Resgata" fica no topo do
  menu lateral e, como botão verde, na barra fixa do topo (que também aparece no site), para
  quem tem `resgata_access_panel` (`Access::admin_menu_link()` e `admin_bar_link()`).
- Capabilities por área: `resgata_manage_settings`, `resgata_manage_adoptions` e
  `resgata_manage_sponsorship` (Administrador e Protetor admin; o Editor comum não tem nenhuma).
- Menu: Início, Animais, Adoções, Padrinhos, Banners e Configurações (para quem pode) e Minha
  conta. Animais, Adoções e Padrinhos (menu e rotas) só existem com o módulo ligado
  (`hasModule()`); os módulos são escolhidos em Configurações → "Recursos do site".

## API

- Rotas em `resgata-pet/v1`, um controller por área em `src/Modules/Panel/Rest/`, sempre por
  `BaseController::route()` (ver `plugins/CLAUDE.md`).
- Filtros de lista chegam separados por vírgula e são validados por `enum` no schema.
- `GET /dashboard` devolve `null` nas áreas que a pessoa não pode ver.
- Fotos: enviadas ao endpoint do WordPress `wp/v2/media`, já reduzidas no aparelho
  (`lib/image.ts`, máx. 2000 px).

## Base técnica

- Tailwind 4 (via `postcss.config.js`, em CommonJS).
- Componentes shadcn/ui na base **Base UI**, em `resources/painel/components/ui/`. Para
  acrescentar um: `npx shadcn@latest add <nome>` dentro de `plugins/resgata-pet`.
- **Variações escritas à mão**, como em `button.tsx` e `badge.tsx`: se o componente novo vier
  com `cva` (`class-variance-authority`, Apache-2.0, incompatível com GPLv2), reescreva-as.
- Só modo claro. A linha `@custom-variant dark` em `index.css` impede que as classes `dark:`
  valham pelo tema do sistema; `next-themes` fica de fora.
- TanStack Query para dados, TanStack Table (v8) para listagens, React Router para navegação,
  react-day-picker para o calendário.
- Tiptap para texto rico, só os pacotes MIT (`@tiptap/react`, `pm`, `starter-kit`).
- Ícones: `lucide-react`, um import por ícone.

## Componentes do projeto (`resources/painel/components/`)

Use estes em vez dos campos nativos do navegador (`<select>`, `<input type="date">`):

- `OptionSelect`: Select do shadcn a partir de um mapa valor => rótulo.
- `DatePicker`: Popover + Calendar, valor "AAAA-MM-DD".
- `ImageDropzone`: arrastar, escolher ou tirar foto; envia e devolve a imagem.
- `RichText`: Tiptap.
- `Stepper`: passos de formulário em etapas.
- `MaskedInput`: campo com máscara de `lib/masks.ts` (`maskCnpj`, `maskPhone` ou
  `createMask( '#####-###' )`).
- `Field` e `PageHeader`.
- `ui/tabs.tsx`: abas (Base UI), usadas nas Configurações.

## Convenções de tela

- Largura: o conteúdo vai até 1400px; formulários usam duas colunas a partir de `md`. Alvos de
  toque de 44px.
- Painéis de filtro seguem `components/animal-filters.tsx`: Popover no computador e Sheet
  deslizando da direita no celular (`useMediaQuery`), aplicados só ao tocar em "Aplicar".
- Filtros, busca e paginação das listas ficam guardados na URL.
- Trocas rápidas na linha da tabela (situação, publicação) usam atualização otimista.

## Telas

- **Início**: cartões de resumo que levam às listas já filtradas (animais por situação,
  pedidos de adoção novos, padrinhos ativos), "Precisa da sua atenção" (pedidos sem resposta
  e rascunhos) e atalhos.
- **Animais**: tabela com 25/50/75/100 por página; busca por nome; filtros por período do
  resgate, publicação, espécie, sexo, situação, porte e castração.
- **Cadastro do animal**: 4 passos. Vira rascunho ao concluir o passo 1 e é publicado no
  último; no cabeçalho da edição dá para alternar entre Publicado e Rascunho.
- **Adoções**, **Padrinhos**, **Banners** e **Configurações**: descritas no documento de cada
  módulo.
- **Minha conta**.
