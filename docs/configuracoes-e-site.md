# Configurações da protetora, contato, banners, números e Pix

Código em `src/Modules/Settings/`. Tudo aqui usa a capability `resgata_manage_settings`
(Administrador e Protetor admin; o Editor comum não).

A tela "Resgata Pet → Configurações" do wp-admin (módulos ativos e retenção de dados) é outra
coisa e continua só para o Administrador.

## Recursos do site (módulos)

- Aba "Recursos do site" em `/painel/configuracoes?aba=recursos` (`components/modules-card.tsx`):
  um interruptor por módulo (Animais, Pedidos de adoção, Apadrinhamento; "Prestação de contas"
  aparece como "Em breve"). Rotas `GET/PUT /modules` (`ModulesController`), com
  `resgata_manage_settings`; o PUT só aceita módulos disponíveis (`Modules::choices()`).
- Pedidos de adoção ficam travados sem Animais. A escolha é guardada como foi feita
  (`Modules::chosen()`): religar Animais traz a adoção de volta.
- Desligar não apaga dados. A troca agenda a recriação das regras de URL.
- Ao salvar, o painel recarrega: menu, rotas e telas vêm de `window.resgataPainel.modules`.
- No site, os blocos do módulo desligado não são registrados, os links para as páginas dele
  somem (`Core\Links`, bloco `resgata-pet/links`, banner padrão) e as seções do tema marcadas
  com `resgata-needs-<módulo>` saem (`Core\ModuleVisibility`, ver `themes/CLAUDE.md`).

## Configurações da protetora

- Tela em `/painel/configuracoes`; dados na option `resgata_organization`.
- `Organization.php` reúne campos, tamanhos máximos e sanitização: campo novo entra só ali.
- Campos: identidade (nome, frase, CNPJ), contato (WhatsApp, mensagem inicial, telefone,
  e-mail, horário), redes (Instagram, Facebook, TikTok, YouTube; aceita link ou @usuario),
  localização (cidade, UF, bairro; **nunca endereço completo**), adoção (e-mail interno, taxa,
  regras), apadrinhamento (e-mail interno dos avisos; página, título e texto do
  agradecimento), privacidade (responsável, texto) e Pix (tipo, chave, titular).
- `adoption_email`, `sponsorship_email` e `sponsorship_thanks_page` são internos
  (`Organization::PRIVATE_FIELDS`) e ficam fora do site.
- A tela tem duas abas, com a aberta guardada na URL (`?aba=pagamentos`): "Protetora e site"
  e "Pagamentos e apadrinhamento" (Pix e, com o módulo ligado, o cartão das chaves da Stripe,
  o e-mail dos avisos e a página, o título e o texto do agradecimento; ver
  `docs/apadrinhamento.md`).
- O `<form>` da tela é só a barra de salvar: cada campo se liga a ele pelo atributo `form`,
  porque o cartão da Stripe tem formulário próprio e formulários não podem ficar um dentro do
  outro.
- O link do WhatsApp é gerado por `Organization::whatsapp_url()`, que troca `{animal}` na
  mensagem.

## Contato

- Bloco `resgata-pet/contact` (template `contact.php`): WhatsApp em destaque, um cartão por
  dado (telefone, e-mail, horário, localização, CNPJ), redes sociais com o logotipo de cada
  uma (`Icons::brand()`) e a chave Pix com o botão "Doar com QR Code" quando existe a página
  "Como ajudar".
- Com `compact: true` (template `contact-compact.php`, usado no rodapé): WhatsApp, telefone,
  e-mail, local e redes sociais em ícones.
- Ao salvar as configurações pela primeira vez, `ContactPage::ensure()` cria a página
  "Contato" (option `resgata_contact_page_id`); se a página for apagada, é recriada no próximo
  salvamento. O conteúdo e o modelo vêm dos filtros `resgata_contact_page_content` e
  `resgata_contact_page_template`.

## Banners

- Tela `/painel/banners` (no celular, o acesso é por Configurações). Até 5 banners, cada um
  com foto, título, final do título em verde, texto e dois botões com link; dá para
  ligar/desligar e reordenar.
- Dados na option `resgata_banners`. `Banners.php` reúne limites, sanitização e renderização.
- Links dos botões: endereço completo `http(s)://`, caminho do site (`/animais/`) ou âncora;
  qualquer outra coisa é recusada (`Banners::sanitize_url()`).
- Site: bloco `resgata-pet/banners` (template `banners.php`). Sem banner ligado, mostra um
  banner padrão com a frase da protetora. Com mais de um, rolam para o lado com o mesmo script
  da galeria do animal, sem avançar sozinhos.

## Números da protetora

Bloco `resgata-pet/impact` (`Settings\Impact`, template `impact.php`), registrado sempre:
animais resgatados, adoções e esperando um lar (com o módulo Animais) e padrinhos ativos (o
módulo de apadrinhamento os acrescenta pelo filtro `resgata_impact_items`). Sem número maior
que zero, o bloco não aparece.

## Doação por Pix e página "Como ajudar"

- Sem módulo próprio e sem Stripe: usa a chave Pix das Configurações. `Organization::pix()`
  devolve a chave, o titular e os valores já no formato do QR Code.
- O Pix cai direto na conta: **as doações não são registradas**. Doação com confirmação
  automática fica para o módulo de doações avulsas pela Stripe.
- Bloco `resgata-pet/pix-donation` (template `pix-donation.php`, `Settings\PixDonation`): QR
  Code, valores sugeridos (filtro `resgata_pix_amounts`), chave com botão de copiar e "Pix copia e
  cola". O código e o QR são montados no navegador pela biblioteca `qrcode-pix` (MIT), em
  `resources/frontend/pix.ts` (configuração `wp` do webpack, com o polyfill `buffer`). Sem o
  script, a página mostra só a chave e o titular.
- **A página publicada que tem esse bloco é a "Como ajudar" do site** (`Core\Links::help()`).
  Ao salvar as Configurações com chave Pix, `HelpPage::ensure()` cria essa página (option
  `resgata_help_page_id`); se já existir uma página com o bloco, ela é aproveitada, e se a página
  for apagada, nasce de novo no próximo salvamento. O conteúdo e o modelo vêm dos filtros
  `resgata_help_page_content` e `resgata_help_page_template`.
- Bloco `resgata-pet/pix-once`: o mesmo QR Code e a mesma chave, com textos de "doação única",
  para colocar dentro de outras páginas sem que elas virem a "Como ajudar".
- Bloco `resgata-pet/donate-banner` (template `donate-banner.php`): chamada "Doe por Pix" com o
  QR Code (sem valor: a pessoa escolhe no app), o botão "Copiar código Pix" e o botão que leva
  à "Como ajudar". Usa o mesmo `pix.ts` (`data-resgata-pix`), com o código num campo oculto; sem o
  script, ficam o texto e o botão. No celular o tema esconde o QR Code, que não tem como ser
  lido na própria tela, e fica o botão de copiar. Fica invisível sem chave Pix, sem a página
  ou na própria página.
