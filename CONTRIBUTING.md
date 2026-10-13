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

**Falha de segurança não vai em issue pública.** Siga a [política de segurança](SECURITY.md)
para avisar em privado. Nunca cole chaves, senhas ou dados de
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

Estes documentos explicam o resto. Os três primeiros foram escritos para assistentes de
código, mas são a referência mais completa também para pessoas:

- [`CLAUDE.md`](CLAUDE.md): o essencial, que vale no repositório inteiro.
- [`plugins/CLAUDE.md`](plugins/CLAUDE.md) e [`themes/CLAUDE.md`](themes/CLAUDE.md): padrões
  técnicos do plugin e do tema.
- [`docs/`](docs/): o funcionamento do painel e de cada módulo.
- [`designer.md`](designer.md): onde o estilo mora e os princípios visuais do site e do painel.
  Leia antes de qualquer mudança visual.

## Padrões

- **PHP 8.1+ e WordPress 6.9+**, só com APIs nativas do WordPress (sem ACF nem outros plugins).
- **WordPress Coding Standards**, conferidos por `make phpcs` (`make phpcbf` corrige o que for
  automático). Prefixo `resgata_` no plugin e `resgata_tema_` no tema.
- **TypeScript** para todo JavaScript do plugin, em `plugins/resgata-wp/resources/`.
- **Textos em português do Brasil**, sempre pelas funções de tradução (`__()`, `esc_html__()`;
  no TypeScript, `__()` de `@wordpress/i18n`). Ao criar ou mudar textos, rode `make pot` e
  inclua os arquivos `.pot` atualizados no pull request.
- **Celular primeiro**: alvos de toque de 44px e telas conferidas em largura de celular.
- **Licença das dependências**: o projeto é GPL-2.0-or-later. Não inclua biblioteca com licença
  incompatível com a GPLv2 (Apache-2.0, por exemplo). Ao acrescentar uma, inclua-a na lista de
  "Componentes de terceiros" do [README](README.md).
- **Imagens, ícones, fontes e textos** seguem a mesma regra: só entra no repositório o que é
  seu ou tem licença compatível com a GPLv2, com a origem citada no pull request.

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
correspondente e com a documentação atualizada (`plugins/CLAUDE.md` e o arquivo do módulo em
`docs/`).

## Publicar uma versão

Só quem mantém o projeto publica versões.

1. Atualize a versão em `plugins/resgata-wp/resgata-wp.php` (`Version` e `RESGATA_VERSION`) e em
   `plugins/resgata-wp/readme.txt` (`Stable tag` e o `Changelog`).
2. Leve a mudança ao `main`.
3. Crie e envie a tag com o mesmo número, sem `v`: `git tag 0.5.1 && git push origin 0.5.1`.

O fluxo `.github/workflows/release.yml` confere a versão, gera os pacotes (`make zip`) e cria a
release no GitHub com `resgata-wp.zip` e `resgata-tema.zip`. Com a variável `WPORG_SLUG`
definida no repositório, ele também envia a versão do plugin ao WordPress.org.

## Usando assistentes de código

A pasta `.claude/skills/` traz as skills oficiais do WordPress (GPL-2.0-or-later), que ajudam
o assistente a seguir as práticas atuais (veja o [guia das skills](.claude/skills/README.md)). Você continua
responsável pelo que envia: leia, entenda e teste o código antes de abrir o pull request.

## Licença

Ao contribuir, você concorda que a sua contribuição seja distribuída sob a
[GPL-2.0-or-later](LICENSE), a mesma licença do projeto.
