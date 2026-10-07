# Como contribuir com o Resgata WP

Obrigado por querer ajudar. O Resgata WP é um kit para WordPress usado por protetoras
independentes de animais, pessoas que em geral **não são técnicas**. Toda contribuição é
avaliada pensando nelas: telas simples, textos curtos, nada de jargão.

Você não precisa programar para ajudar. Relatar um problema, sugerir uma melhoria, revisar
textos ou testar em um celular diferente já é contribuição.

## Relatar um problema ou sugerir algo

Abra uma issue contando:

- o que você tentou fazer e o que aconteceu;
- em que tela (site, `/painel` ou painel do WordPress) e em que aparelho;
- as versões do WordPress e do plugin;
- uma captura de tela, se ajudar.

**Falha de segurança não vai em issue pública.** Use o botão "Report a vulnerability", na aba
"Security" do repositório, para avisar em privado. Nunca cole chaves, senhas ou dados de
adotantes e padrinhos em issues, pull requests ou capturas de tela.

## Preparar o ambiente

Você precisa de Docker (com Compose) e Make. No Windows, use o WSL.

```bash
git clone <endereço do seu fork> resgata-wp
cd resgata-wp
make setup
```

O comando sobe o WordPress já instalado, com o plugin, o tema e animais de exemplo, e mostra
os endereços e os acessos no final. Os detalhes estão em "Desenvolvimento local", no
[README](README.md). `make help` lista todos os comandos.

Ao mexer no painel, nos blocos ou nos scripts, deixe `make watch` rodando: ele recompila o
TypeScript a cada alteração.

## Como o projeto é organizado

| Pasta | O que tem |
|---|---|
| `plugins/resgata-wp` | Toda a funcionalidade: cadastros, formulários, painel, blocos, pagamentos |
| `themes/resgata-tema` | Só a aparência: `theme.json`, modelos, patterns e CSS dos blocos |

**Regra de ouro: funcionalidade no plugin, aparência no tema.** O plugin precisa funcionar
com qualquer tema; o tema não guarda dados nem regras de negócio.

Dois documentos explicam o resto:

- [`CLAUDE.md`](CLAUDE.md): arquitetura, padrões técnicos e o funcionamento de cada módulo.
  Foi escrito para assistentes de código, mas é a referência mais completa também para
  pessoas.
- [`designer.md`](designer.md): onde o estilo mora e os princípios visuais do site e do painel.
  Leia antes de qualquer mudança visual.

## Padrões

- **PHP 8.1+ e WordPress 6.9+**, só com APIs nativas do WordPress (sem ACF nem outros plugins).
- **WordPress Coding Standards**, conferidos por `make phpcs` (`make phpcbf` corrige o que for
  automático). Prefixo `rwp_` no plugin e `resgata_tema_` no tema.
- **TypeScript** para todo JavaScript do plugin, em `plugins/resgata-wp/resources/`.
- **Textos em português do Brasil**, sempre pelas funções de tradução (`__()`, `esc_html__()`;
  no TypeScript, `__()` de `@wordpress/i18n`). Ao criar ou mudar textos, rode `make pot` e
  inclua os arquivos `.pot` atualizados no pull request.
- **Celular primeiro**: alvos de toque de 44px e telas conferidas em largura de celular.
- **Licença das dependências**: o projeto é GPL-2.0-or-later. Não inclua biblioteca com licença
  incompatível com a GPLv2 (Apache-2.0, por exemplo).

### Segurança

Estas regras valem para todo código novo:

- todo formulário e ação conferem nonce **e** permissão (`current_user_can()`);
- toda entrada é sanitizada e toda saída é escapada;
- toda consulta própria ao banco usa `$wpdb->prepare()`;
- rotas da API do painel são registradas por `BaseController::route()`, que nega por padrão;
- dados de adotantes, padrinhos e pagamentos nunca aparecem em página pública nem em rota sem
  permissão;
- nada de credenciais reais: no ambiente local, só chaves de sandbox.

Se a sua mudança toca em pagamentos, dados pessoais ou permissões, diga isso na descrição do
pull request e aponte os trechos que merecem revisão cuidadosa.

## Enviar uma mudança

1. Para algo maior que uma correção, abra antes uma issue para combinar o caminho.
2. Crie um ramo a partir de `main` (por exemplo `feat/filtro-por-idade` ou `fix/galeria-celular`).
3. Faça commits pequenos, com mensagem em português que diga o que mudou, como
   `feat(painel): filtro de animais por idade` ou `fix(adoção): telefone com DDD de dois dígitos`.
4. Antes de abrir o pull request, rode:

   ```bash
   make phpcs       # padrão de código PHP
   make typecheck   # tipos do TypeScript
   make test        # permissões da API do painel e webhooks da Stripe
   make check       # Plugin Check do WordPress
   ```

   O `make test` cria e apaga usuários e animais temporários no seu site local.
5. Em mudança visual, confira também com o tema Twenty Twenty-Five puro
   (`make wp cmd="theme activate twentytwentyfive"`) e volte para o `resgata-tema` depois.
6. No pull request, explique o que mudou e por quê, como testar e, se for visual, inclua
   capturas de tela do celular e do computador.

Mudança que cria rota, tabela, permissão ou módulo novo precisa vir com o teste
correspondente e com o `CLAUDE.md` atualizado.

## Usando assistentes de código

A pasta `.claude/skills/` traz as skills oficiais do WordPress, que ajudam o assistente a
seguir as práticas atuais (veja o [guia das skills](.claude/skills/README.md)). Você continua
responsável pelo que envia: leia, entenda e teste o código antes de abrir o pull request.

## Licença

Ao contribuir, você concorda que a sua contribuição seja distribuída sob a
[GPL-2.0-or-later](LICENSE), a mesma licença do projeto.
