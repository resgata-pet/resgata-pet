# Resgata Pet

Kit open source para WordPress que ajuda **protetoras independentes de animais** a:

1. divulgar os animais resgatados e receber pedidos de adoção;
2. arrecadar com apadrinhamento mensal e doações avulsas;
3. prestar contas com transparência.

O kit tem duas partes, neste repositório:

| Pasta | O que é |
|---|---|
| `plugins/resgata-pet` | Plugin com toda a funcionalidade e os dados. Funciona com qualquer tema. |
| `themes/resgata-tema` | Tema de blocos (filho do Twenty Twenty-Five) com visual acolhedor, editável pelo Editor do Site. |

## Situação atual

| Módulo | Situação |
|---|---|
| Animais (cadastro, listagem com filtros, página individual) | Pronto |
| Painel próprio em `/painel` (cadastro de animais pelo celular) | Pronto |
| Configurações da protetora (contato, redes, localização, Pix) e página de contato | Pronto |
| Pedidos de adoção (questionário no site, envio pelo WhatsApp, fila no painel) | Pronto |
| Apadrinhamento mensal com cartão (Stripe): planos, área do padrinho, registro dos pagamentos, página de agradecimento e contador no site | Pronto |
| Doações avulsas por Pix (Stripe) | Planejado |
| Prestação de contas | Planejado |

## Requisitos

- WordPress 6.9 ou superior e PHP 8.1 ou superior.
- Para desenvolver: Docker (com Compose) e Make. Node e Composer são opcionais: se não
  estiverem instalados (ou se o Node for mais antigo que 22.22 / 24.15), os comandos rodam os
  dois pelo Docker.
- No Windows, o recomendado é usar o [WSL](https://learn.microsoft.com/pt-br/windows/wsl/install)
  (Ubuntu) com o Docker Desktop integrado a ele, e clonar o projeto dentro do WSL.

## Instalação em um site WordPress

1. Gere os pacotes com `make zip` (cria `dist/resgata-pet.zip` e `dist/resgata-tema.zip`).
   Copiar a pasta do plugin direto do repositório não basta: o painel precisa dos arquivos
   gerados pelo build.
2. No WordPress, vá em **Plugins → Adicionar plugin → Enviar plugin**, envie o
   `resgata-pet.zip` e ative.
3. Opcional: instale o tema Twenty Twenty-Five, vá em **Aparência → Temas → Adicionar tema →
   Enviar tema**, envie o `resgata-tema.zip` e ative o **Resgata Tema**.
4. O site precisa usar links permanentes (qualquer opção diferente de "Padrão" em
   **Configurações → Links permanentes**). Se `/animais/` ou `/painel/` não abrirem, clique em
   **Salvar alterações** nessa mesma tela.

## O painel da protetora

O dia a dia acontece em `seusite.org/painel`, um painel próprio, feito para o celular:

- **Cadastrar animal** em quatro passos (básico, ficha, saúde, história e fotos). Ao concluir
  o primeiro passo o animal já fica salvo como rascunho; o último passo publica no site.
- **Fotos** tiradas na hora ou escolhidas do aparelho, reduzidas antes do envio.
- **Animais**: lista com foto, nome e situação.
- **Excluir** move o cadastro para a lixeira, onde ele fica por 30 dias. O painel não tem tela
  de lixeira: quem administra o servidor restaura com `wp post untrash <ID>` (WP-CLI).
- **Adoções**: quem quer adotar preenche um questionário na página do animal e envia o pedido
  pelo WhatsApp da protetora. O pedido também fica guardado no painel, com situação (novo, em
  análise, entrevista, aprovado, recusado) e anotações internas.
- **Padrinhos**: os planos, quem apadrinha pelo site (nome, e-mail, plano, situação e total
  doado), a arrecadação do mês, a lista de pagamentos (com estornos e contestações), o número
  mostrado no site e um ajuste manual para somar quem ajuda por fora.
- **Configurações**, em duas abas:
  - **Protetora e site**: WhatsApp, telefone, e-mail, redes sociais, cidade, adoção e
    privacidade. Ao salvar pela primeira vez, o site ganha uma página **Contato** com esses
    dados. Para mostrá-los em outra página, use o bloco **Contato da protetora**.
  - **Pagamentos e apadrinhamento**: chave Pix, chaves da Stripe, e-mail que recebe os avisos
    de apadrinhamento e a página de agradecimento (qual página, título e texto).

Para dar acesso a alguém, crie o usuário em **Usuários → Adicionar usuário** com a função
**Protetor admin**. Essa pessoa entra pelo login normal do site e usa somente o painel; se
tentar abrir o painel do WordPress, é levada de volta para `/painel`. Administradores usam os
dois (há um atalho "Painel Resgata" na barra do WordPress).

### Mostrando animais em outras páginas

- Bloco **Lista de animais** (categoria "Resgata Pet" no editor).
- Pattern **Gatos em destaque** (com o Resgata Tema ativo).
- Shortcode: `[resgata_animais especie="gato" quantidade="6" colunas="3" filtros="nao"]`

  | Atributo | Valores | Padrão |
  |---|---|---|
  | `especie` | `gato`, `cao` ou vazio para todas | vazio |
  | `status` | `disponivel`, `reservado`, `adotado`, `em-tratamento`, `falecido` (separados por vírgula) | `disponivel,reservado` |
  | `quantidade` | animais por página | `12` |
  | `colunas` | 1 a 6 | `3` |
  | `filtros` | `sim` ou `nao` | `sim` |
  | `paginacao` | `sim` ou `nao` | `sim` |

## Desenvolvimento local

```bash
git clone https://github.com/resgata-pet/resgata-pet.git
cd resgata-pet
make setup
```

Esse único comando instala as dependências, compila o painel, sobe o WordPress já instalado
em português, ativa o plugin e o tema, liga todos os módulos prontos e cria 12 animais de
exemplo. A primeira vez leva alguns minutos, por causa dos downloads.

Ao terminar, ele mostra os endereços e os acessos:

| O quê | Endereço | Usuário | Senha |
|---|---|---|---|
| Site | <http://localhost:8080> | | |
| Painel Resgata | <http://localhost:8080/painel> | `protetora` | `protetora` |
| WordPress | <http://localhost:8080/wp-admin> | `admin` | `admin` |
| E-mails de teste (Mailpit) | <http://localhost:8025> | | |

As portas vêm do `.env` (`WP_PORT` e `MAILPIT_PORT`); os endereços acima são os do padrão.

Nos outros dias, `make up` sobe o ambiente e `make down` derruba, sem perder os dados.
Tudo funciona sem conta na Stripe; ela só é necessária para testar pagamentos do
apadrinhamento (veja abaixo).

No Windows, rode esses comandos dentro do WSL.

Comandos mais usados (`make help` mostra todos):

| Comando | O que faz |
|---|---|
| `make setup` | Prepara tudo do zero (pode rodar de novo sem perder dados; só cria mais animais de exemplo) |
| `make up` / `make down` | Sobe / derruba o ambiente (os dados ficam guardados) |
| `make install` | Instala o WordPress em pt_BR, garante o Twenty Twenty-Five e ativa plugin e tema |
| `make seed` / `make unseed` | Cria / remove os animais de exemplo |
| `make wp cmd="plugin list"` | Roda qualquer comando WP-CLI |
| `make build` / `make watch` | Compila o TypeScript (painel, blocos, scripts do site) uma vez / a cada alteração |
| `make typecheck` | Confere os tipos do TypeScript |
| `make test` | Testa as permissões da API do painel e o processamento dos webhooks da Stripe |
| `make stripe-setup` / `make stripe-secret` / `make stripe` | Apadrinhamento: cria os planos no sandbox / mostra o segredo dos webhooks / repassa os eventos ao site local |
| `make zip` | Gera `dist/resgata-pet.zip` (plugin) e `dist/resgata-tema.zip` (tema) para instalar em um site |
| `make phpcs` / `make phpcbf` | Verifica / corrige o padrão de código |
| `make check` | Roda o Plugin Check |
| `make pot` | Gera os arquivos de tradução (`.pot`) do plugin e do tema |
| `make clean` | Derruba o ambiente e apaga banco e uploads |

O ambiente roda com `WP_DEBUG` ligado e serve só para desenvolvimento: nunca use nele
credenciais reais.

## Apadrinhamento (Stripe)

O padrinho escolhe um valor mensal no site e paga com **cartão de crédito** na página da
Stripe (Checkout). O site nunca mostra campo de cartão. Depois de pagar, a pessoa cai na
página **Obrigado** e recebe um e-mail de agradecimento. Na **Área do padrinho**, o padrinho
informa o e-mail, recebe um link de uso único e vê quanto já doou; dali segue para o portal da
Stripe para trocar o cartão, mudar o valor ou cancelar. Não há senha.

O site guarda o nome, o e-mail, o plano, a situação e o valor e a data de cada pagamento.
Dados de cartão, CPF e endereço ficam só na Stripe.

> O Pix recorrente (Pix Automático) não está disponível para contas Stripe do Brasil, por
> isso a assinatura é só no cartão. Doações avulsas por Pix ficam para a próxima fase.

### Em um site de verdade

1. Rode `composer install --no-dev` na pasta do plugin (o `make zip` já faz isso): o módulo
   usa a biblioteca oficial `stripe/stripe-php`. Sem ela, o módulo aparece como indisponível.
2. Na Stripe, crie uma **chave restrita** (`rk_live_...`) só com permissão de escrita em
   *Checkout Sessions*, *Customer portal sessions*, *Products*, *Prices* e *Customer portal
   configurations*. As três últimas servem para criar e alterar os planos pelo painel. Não
   use a chave secreta completa.
3. Cadastre o webhook `https://seusite.org/wp-json/resgata-pet/v1/stripe/webhook` com os eventos
   `checkout.session.completed`, `customer.subscription.created`, `customer.subscription.updated`,
   `customer.subscription.deleted`, `invoice.paid`, `invoice.payment_failed`,
   `invoice_payment.paid`, `charge.refunded`, `charge.dispute.created`, `charge.dispute.updated`,
   `charge.dispute.closed` e `customer.updated`.
4. Informe as duas chaves. Há dois caminhos:
   - **Pelo painel** (não exige acesso ao servidor): o Administrador abre **Painel →
     Configurações → Pagamentos e apadrinhamento**, cola a chave restrita e o segredo do
     webhook e toca em "Guardar chaves". A chave completa (`sk_`) é recusada. O mesmo cartão
     lista as permissões que a chave precisa ter e marca em verde as que já estão liberadas.
     As chaves são gravadas cifradas (AES-256, com chave derivada de
     `LOGGED_IN_KEY`/`LOGGED_IN_SALT` do `wp-config.php`) e nunca voltam inteiras para o
     navegador. Se as chaves de segurança do `wp-config.php` forem trocadas, é preciso colar
     as chaves da Stripe de novo.
   - **No `wp-config.php`** (mais seguro, porque nada vai para o banco). As constantes têm
     prioridade e bloqueiam os campos do painel:

     ```php
     define( 'RESGATA_STRIPE_SECRET_KEY', 'rk_live_...' );
     define( 'RESGATA_STRIPE_WEBHOOK_SECRET', 'whsec_...' );
     ```

5. Ligue o módulo em **Resgata Pet → Configurações** e crie os planos em **Painel → Padrinhos
   → Planos**: cada plano tem nome (opcional), valor mensal, descrição, lista do que ajuda a
   pagar e o selo "Mais escolhido". Mudar o valor cria um preço novo na Stripe; quem já
   apadrinha continua no valor antigo. Um plano com padrinhos não é excluído, só escondido
   do site. Quem prefere a linha de comando ainda pode usar `wp resgata stripe-setup`
   (padrão: R$ 15, 30, 50, 75 e 100; `--amounts=20,40,80`; ou `--price-ids=price_...` para
   preços criados no Dashboard), que não apaga os nomes já cadastrados.
6. Coloque os blocos **Planos de apadrinhamento** e **Área do padrinho** em uma página.
7. Em **Painel → Configurações → Pagamentos e apadrinhamento**, informe o e-mail que recebe os
   avisos (novo padrinho, pedido de cancelamento, pagamento recusado e pagamento contestado;
   sem ele, os avisos não são enviados) e, se quiser, troque a página, o título e o texto do
   agradecimento. A página **Obrigado** é criada sozinha quando há um plano à venda.
8. Confira se o site envia e-mails: o link da área do padrinho, o agradecimento e os avisos
   dependem disso. Em muitas hospedagens é preciso configurar um serviço de envio (SMTP).
9. No Dashboard da Stripe, deixe ligadas só as formas de pagamento que quer aceitar na
   assinatura (o plugin já esconde o boleto) e ligue os e-mails de recibo e de cobrança
   recusada: esses dois o site deixa por conta da Stripe.

### No ambiente local (somente sandbox)

1. Crie um [sandbox](https://dashboard.stripe.com/sandboxes) na Stripe e copie a chave dele
   para `STRIPE_SANDBOX_SECRET_KEY` no `.env`. **Nunca** use chave `live` aqui.
2. `make stripe-secret` mostra o segredo dos webhooks locais (`whsec_...`): cole em
   `STRIPE_WEBHOOK_SECRET` no `.env` e rode `make up` para o WordPress receber as chaves.
3. O `make setup` já liga o módulo. Se ele estiver desligado, marque "Apadrinhamento" em
   Resgata Pet → Configurações.
4. `make stripe-setup` cria os planos e o portal no sandbox.
5. Deixe `make stripe` rodando em outro terminal: ele repassa os eventos da Stripe para
   `http://wordpress/wp-json/resgata-pet/v1/stripe/webhook`.

Para testar o cadastro das chaves pelo painel, ponha `STRIPE_KEYS_IN_PANEL=1` no `.env` e rode
`make up`: o site deixa de ler as chaves do `.env` (o `make stripe` continua usando) e passa a
esperar as do painel, que só aceita chave restrita (`rk_test_...`).

Cartões de teste (qualquer data futura e qualquer CVC):

| Cartão | O que acontece |
|---|---|
| `4242 4242 4242 4242` | Pagamento aprovado |
| `4000 0007 6000 0002` | Aprovado, cartão emitido no Brasil |
| `4000 0025 0000 3155` | Pede autenticação (3D Secure) |
| `4000 0000 0000 9995` | Recusado por falta de saldo |
| `4000 0000 0000 0341` | Assina, mas as cobranças seguintes falham (testa "pagamento falhou") |

A lista completa está em <https://docs.stripe.com/testing>.

**Simulação de Pix** (para as doações avulsas, quando forem implementadas): no Checkout de
teste, escolha Pix, use `000.000.000-00` como CPF e clique em **Simular digitalização**; a
página de teste da Stripe deixa autorizar ou expirar o pagamento.

## Personalizando pelo tema

O plugin traz templates e estilos neutros. Um tema pode:

- **Assumir o visual:** declare `add_theme_support( 'resgata-pet' );` e estilize as classes
  `.resgata-*`. O plugin deixa de carregar o CSS dele.
- **Substituir um template PHP:** copie o arquivo de `plugins/resgata-pet/templates/` para a
  pasta `resgata-pet/` do tema (por exemplo, `seu-tema/resgata-pet/animal-card.php`).
- **Temas de blocos:** crie `templates/single-resgata_animal.html` e
  `templates/archive-resgata_animal.html`, ou edite os modelos "Página do animal" e
  "Lista de animais" no Editor do Site.

## Contribuindo

Toda ajuda é bem-vinda, com ou sem código. O [guia de contribuição](CONTRIBUTING.md) explica
como relatar um problema, preparar o ambiente e enviar uma mudança. A arquitetura está em
[`CLAUDE.md`](CLAUDE.md), [`plugins/CLAUDE.md`](plugins/CLAUDE.md), [`themes/CLAUDE.md`](themes/CLAUDE.md)
e [`docs/`](docs/); o guia visual, em [`designer.md`](designer.md).

## Segurança

Encontrou uma falha? Não abra issue pública: siga a [política de segurança](SECURITY.md).

## Licença

Copyright (C) 2026 contribuidores do Resgata Pet.

O Resgata Pet é software livre: você pode redistribuí-lo e modificá-lo sob os termos da
Licença Pública Geral GNU, versão 2 ou (à sua escolha) qualquer versão posterior, publicada
pela Free Software Foundation. Ele é distribuído sem nenhuma garantia. O texto completo está
em [`LICENSE`](LICENSE) e acompanha os pacotes gerados pelo `make zip`.

O plugin e o tema usam a mesma licença: [GPL-2.0-or-later](LICENSE).

### Componentes de terceiros

Os pacotes incluem bibliotecas com licenças compatíveis com a GPLv2:

- [stripe-php](https://github.com/stripe/stripe-php) (MIT), na pasta `vendor/` do plugin.
- No painel e nos scripts do site, compilados em `build/`: React, React Router, Base UI,
  TanStack Query e Table, Tiptap, react-day-picker, date-fns, sonner, qrcode-pix e Tailwind CSS
  (MIT); lucide (ISC); `@wordpress/api-fetch` e `@wordpress/i18n` (GPL-2.0-or-later).
- As skills de `.claude/skills/` vêm de
  [WordPress/agent-skills](https://github.com/WordPress/agent-skills) (GPL-2.0-or-later).
- O tema é filho do [Twenty Twenty-Five](https://wordpress.org/themes/twentytwentyfive/)
  (GPL-2.0-or-later), que não é distribuído junto.

O ícone do plugin (`plugins/resgata-pet/.wordpress-org/`) e a imagem de apresentação do tema
(`themes/resgata-tema/screenshot.png`) foram criados para o projeto e seguem a mesma licença.

A lista completa e as versões estão em `plugins/resgata-pet/package.json` e
`plugins/resgata-pet/composer.json`.
