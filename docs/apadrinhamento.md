# Módulo Apadrinhamento (Stripe)

Código em `src/Modules/Sponsorship/`. Desligado por padrão; só fica disponível com a
biblioteca `stripe/stripe-php` instalada (`composer install` na pasta do plugin; o `make zip`
inclui a pasta `vendor/`). O resto do plugin funciona sem ela.

## Regras da integração

- Assinatura mensal em BRL, **só cartão**, pelo **Stripe Checkout** (página hospedada). O
  cartão é digitado na Stripe, nunca em campo do site. Pix Automático não existe para contas
  Stripe do Brasil.
- **`Gateway` é o único ponto que chama a API da Stripe** (`StripeClient` instanciado; sem
  `payment_method_types`; `excluded_payment_method_types` esconde o boleto).
- As operações de planos passam antes pelo filtro `resgata_sponsorship_pre_stripe`, que os testes
  usam para não falar com a Stripe.
- O padrinho só existe no site **depois do webhook**; a página de retorno do Checkout apenas
  agradece.

## Chaves (`Keys`)

- Origem: constantes `RESGATA_STRIPE_SECRET_KEY` (de preferência restrita, `rk_`) e
  `RESGATA_STRIPE_WEBHOOK_SECRET` do `wp-config.php` ou, sem elas, o que o Administrador salvou no
  painel (`PUT /sponsorship/keys`, só `manage_options`; o Protetor admin recebe 403). A
  constante tem prioridade e bloqueia o campo (409).
- O painel só aceita chave restrita (`rk_`); a completa (`sk_`) é recusada (no `wp-config.php`
  ela ainda funciona). A chave é testada na Stripe antes de ser gravada
  (`Gateway::verify_key()`).
- O que vem do painel é gravado **cifrado** na option `resgata_stripe_keys` (sem autoload) por
  `Core\Encryption`: AES-256-CTR com IV aleatório, chave derivada de `RESGATA_ENCRYPTION_KEY` /
  `RESGATA_ENCRYPTION_SALT` ou, sem elas, de `LOGGED_IN_KEY` / `LOGGED_IN_SALT`. É a mesma lógica
  do Site Kit, reescrita, porque o código dele é Apache-2.0.
- Sem OpenSSL ou sem essas constantes nada é gravado: não existe caminho de texto puro. Se as
  constantes mudarem, a chave fica ilegível e o painel pede para colar de novo.
- As chaves ficam fora da API do painel e do log: a API devolve só "definida", a origem e o
  começo + 4 últimos caracteres.
- A option é apagada na desinstalação mesmo com "Manter os dados" marcado.

## Permissões da chave

`Gateway::permissions()` (`GET /sponsorship/permissions`) confere as cinco de
`Gateway::PERMISSIONS` com pedidos de escrita sobre itens que não existem. Nada é criado: "não
encontrado" quer dizer liberada, "sem permissão" quer dizer pendente. O cartão "Pagamentos
(Stripe)", em Configurações → aba "Pagamentos e apadrinhamento", lista cada uma com o check
verde ou o alerta.

Chamada nova à Stripe que exija outra permissão entra em `Gateway::PERMISSIONS` **e** em
`STRIPE_PERMISSIONS` (`components/stripe-card.tsx`).

## Planos

- Cadastrados em `/painel/apadrinhamento` → "Planos" (`PlanService`, rotas
  `/sponsorship/plans`). Cada plano é um produto da Stripe (o nome) ligado ao preço mensal em
  vigor, com descrição, benefícios, destaque ("Mais escolhido", um só) e "No site" (à venda ou
  escondido).
- Mudar o valor cria outro preço no mesmo produto e guarda o antigo em `old_prices`: quem já
  assina segue no valor antigo e continua reconhecido (`Plans::find()`). Só o preço em vigor
  de plano à venda pode ser comprado (`Plans::find_for_sale()`).
- Plano com padrinhos não é excluído, só escondido.
- A cada mudança o portal do cliente é atualizado com os planos à venda. A chave precisa de
  escrita em Products, Prices e Customer portal configurations; sem ela o painel explica o que
  falta.
- A option `resgata_sponsorship` guarda os planos (`plans`; o formato antigo `prices` ainda é
  lido), o ID do portal e o ajuste manual do contador. `wp resgata stripe-setup` continua
  funcionando e preserva os nomes já cadastrados.

## Tabelas (`Core\Schema`, schema 6)

- `resgata_sponsors`: nome, e-mail, IDs de cliente/assinatura/preço, situação, aviso de pagamento
  e datas (último pagamento, fim do período pago em `period_end`, cancelamento pedido em
  `cancel_at`, boas-vindas enviadas em `welcomed_at`). **Sem** CPF, endereço ou dados de
  pagamento.
- `resgata_stripe_events`: IDs de eventos já processados, limpos após 30 dias.
- `resgata_payments` (`Payments`): uma linha por fatura paga, com os IDs da fatura e do pagamento,
  o valor, o total devolvido (`refunded`) e a contestação (`dispute`: vazio, `open`, `won` ou
  `lost`). `Payments::net()` diz quanto ficou com a protetora: nada enquanto a contestação
  está aberta ou se foi perdida; nos outros casos, o valor menos o devolvido. Linhas sem
  padrinho (faturas de outros produtos da conta) são apagadas após 30 dias.
- Site que já tinha padrinhos antes do schema 6: os pagamentos anteriores não são
  registrados. A option `resgata_payments_since` guarda o início do registro, e o resumo desses
  padrinhos mostra "Você doou desde ...".

## Webhook

- `POST /wp-json/resgata-pet/v1/stripe/webhook` (`WebhookController`). É a **única** rota
  pública do namespace e a única fora de `BaseController::route()`: a autenticação é a
  assinatura do evento, conferida em `WebhookHandler::handle()` sobre o corpo bruto.
- Evento repetido responde 200 sem efeito; evento mais antigo que o último aplicado é
  ignorado; falha de gravação libera o evento e responde 500, para a Stripe tentar de novo.
- Eventos tratados: `checkout.session.completed` (nome e e-mail),
  `customer.subscription.created` / `updated` / `deleted` (situação, plano, fim do período e
  cancelamento pedido), `invoice.paid` (data e valor pago), `invoice.payment_failed`,
  `invoice_payment.paid` (liga a fatura ao pagamento), `charge.refunded` (estorno, inteiro ou
  parcial), `charge.dispute.created` / `updated` / `closed` (contestação) e `customer.updated`.
- Estorno e contestação acham o pagamento pelo ID do pagamento (`pi_...`). Nas versões
  recentes da API a fatura não traz esse ID: ele chega em `invoice_payment.paid`, antes ou
  depois do `invoice.paid`, e os dois gravam na mesma linha (`Payments::record()`).
- Evento novo entra em `WebhookHandler::HANDLERS`, na lista do serviço `stripe` do
  `docker-compose.yml` e na lista do `README.md`. Em um site no ar, a protetora precisa
  marcar o evento no webhook cadastrado na Stripe.
- Doações avulsas (Pix pela Stripe) ainda não existem. O ponto de entrada é
  `WebhookHandler::checkout_completed()` (sessões `mode: payment`).

## Site

- Blocos `resgata-pet/sponsorship-plans` e `resgata-pet/sponsor-area` (templates
  `sponsorship-plans.php` e `sponsor-area.php`). Os planos aparecem como cartões, um por
  valor, cada um com o seu botão (o botão envia o plano).
- Contador público (`Plans::counter()`): assinaturas `active` + ajuste manual. A option
  `resgata_sponsorship_counter` guarda o número e a data ("Atualizado em").
- Área do padrinho (`MagicLink`): e-mail → link de uso único (30 min; só o resumo do código é
  gravado) → botão de confirmação → resumo do apadrinhamento (total doado, plano, situação,
  datas) → botão "Alterar ou cancelar" → sessão do portal da Stripe. A resposta ao pedido do
  link é a mesma para e-mail conhecido ou não.
- O padrinho não tem conta nem sessão no site. O resumo só existe na resposta ao envio do
  botão de confirmação (POST, sem cache) e leva um código novo, de uso único, para o botão do
  portal. Recarregar a página gasta o código: a pessoa pede outro link.
- `Core\Links::sponsor_area()` encontra a página que tem o bloco da área do padrinho (usada no
  e-mail de boas-vindas).
- Página de agradecimento (`ThanksPage`): depois de pagar, a Stripe devolve a pessoa para a
  página escolhida nas Configurações (`sponsorship_thanks_page`). O plugin cria a página
  "Obrigado", com o bloco `resgata-pet/sponsorship-thanks` (template `sponsorship-thanks.php`),
  ao salvar o primeiro plano ou as Configurações com um plano à venda; se for apagada, nasce
  de novo no próximo salvamento. O conteúdo e o modelo vêm dos filtros
  `resgata_thanks_page_content` e `resgata_thanks_page_template`. O título e o texto do bloco são os
  campos `sponsorship_thanks_title` e `sponsorship_thanks_text`, com texto padrão quando
  vazios. O plugin não traz imagem: o site pode informar uma
  (inclusive animada) pelo filtro `resgata_thanks_image_url`, e ela aparece no bloco e no e-mail
  de agradecimento. A página leva `noindex`. Sem página publicada, a pessoa volta para a página dos
  planos, que mostra o agradecimento simples (`Checkout::is_thank_you()`).
- A página só agradece: quem a abre direto vê a mesma mensagem. O padrinho só existe no site
  depois do webhook.
- `Core\Links::sponsorship()` encontra a página de apadrinhamento pelo bloco dos planos no
  conteúdo dela (o pattern da página está em `themes/CLAUDE.md`).

## E-mails (`Notifications`)

- Ao padrinho: só o agradecimento, na primeira vez em que ele aparece ativo e com e-mail
  (`Sponsors::claim_welcome()` garante um envio só, em qualquer ordem de eventos). É um e-mail
  de texto; só vai em HTML (com a versão em texto junto) quando o site informa uma imagem
  pelo filtro `resgata_thanks_image_url` (`ThanksPage::image_url()`, a mesma da página). Recibo e
  aviso de cartão recusado são da Stripe (ligados no Dashboard dela).
- À protetora, no e-mail `sponsorship_email` das Configurações (campo interno, como o
  `adoption_email`; sem ele nada é enviado): novo padrinho, pedido de cancelamento,
  apadrinhamento encerrado sem pedido anterior, pagamento recusado (um aviso por atraso) e
  pagamento contestado. Os avisos levam só o nome e o plano; o resto fica no painel.

## Painel

`/painel/apadrinhamento` (menu "Padrinhos"), capability `resgata_manage_sponsorship`
(Administrador e Protetor admin), rotas `/sponsorship`, `/sponsors` e `/payments`.

- As chaves da Stripe, o e-mail dos avisos e a página de agradecimento ficam em
  `/painel/configuracoes?aba=pagamentos` (`components/stripe-card.tsx`, com as mesmas rotas
  `/sponsorship`); a tela de padrinhos só mostra a situação e o atalho para lá.
- Cartão "Arrecadação dos padrinhos" (`Payments::revenue()`): mês atual, mês passado e total,
  nos meses do fuso do site, já sem estornos e contestações (as taxas da Stripe não são
  descontadas), e o alerta de contestações esperando resposta.
- Lista de padrinhos: plano, situação (com "Pagamento falhou" e "Cancela em ..."), total doado
  e último pagamento.
- Lista de pagamentos (`GET /payments`): data, padrinho, valor, valor devolvido e situação
  (`Payments::status()`). O painel só mostra: estorno é feito na Stripe.

## Ambiente local e testes

- `make stripe-secret`, `make stripe-setup` e `make stripe` (Stripe CLI no Docker, perfil
  `stripe`), só com chaves de sandbox no `.env`.
- `make test` roda `tests/stripe-webhooks.php`: eventos assinados localmente, sem falar com a
  Stripe.
