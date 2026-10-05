# Resgata WP

Kit open source para WordPress que ajuda **protetoras independentes de animais** a:

1. divulgar os animais resgatados e receber pedidos de adoção;
2. arrecadar com apadrinhamento mensal e doações avulsas;
3. prestar contas com transparência.

O kit tem duas partes, neste repositório:

| Pasta | O que é |
|---|---|
| `plugins/resgata-wp` | Plugin com toda a funcionalidade e os dados. Funciona com qualquer tema. |
| `themes/resgata-tema` | Tema de blocos (filho do Twenty Twenty-Five) com visual acolhedor, editável pelo Editor do Site. |

## Situação atual

| Módulo | Situação |
|---|---|
| Animais (cadastro, listagem com filtros, página individual) | Pronto |
| Painel próprio em `/painel` (cadastro de animais pelo celular) | Pronto |
| Configurações (módulos ativos, retenção de dados) | Inicial |
| Pedidos de adoção | Planejado |
| Apadrinhamento | Planejado |
| Prestação de contas | Planejado |

## Requisitos

- WordPress 6.9 ou superior e PHP 8.1 ou superior.
- Para desenvolver: Docker (com Compose), Make, Composer e Node 22.22+ ou 24.15+.

## Instalação em um site WordPress

1. Gere o pacote com `make zip` (cria `dist/resgata-wp.zip`). Copiar a pasta direto do
   repositório não basta: o painel precisa dos arquivos gerados pelo build.
2. No WordPress, vá em **Plugins → Adicionar plugin → Enviar plugin**, envie o `.zip` e ative.
3. Opcional: instale o tema Twenty Twenty-Five, copie `themes/resgata-tema` para
   `wp-content/themes/` e ative o **Resgata Tema**.
4. O site precisa usar links permanentes (qualquer opção diferente de "Padrão" em
   **Configurações → Links permanentes**). Se `/animais/` ou `/painel/` não abrirem, clique em
   **Salvar alterações** nessa mesma tela.

## O painel da protetora

O dia a dia acontece em `seusite.org/painel`, um painel próprio, feito para o celular:

- **Cadastrar animal** em quatro passos (básico, ficha, saúde, história e fotos). Ao concluir
  o primeiro passo o animal já fica salvo como rascunho; o último passo publica no site.
- **Fotos** tiradas na hora ou escolhidas do aparelho, reduzidas antes do envio.
- **Animais**: lista com foto, nome e situação.
- **Excluir** move o cadastro para a lixeira; o administrador pode restaurar pelo WordPress.

Para dar acesso a alguém, crie o usuário em **Usuários → Adicionar usuário** com a função
**Protetor admin**. Essa pessoa entra pelo login normal do site e usa somente o painel; se
tentar abrir o painel do WordPress, é levada de volta para `/painel`. Administradores usam os
dois (há um atalho "Painel Resgata" na barra do WordPress).

### Mostrando animais em outras páginas

- Bloco **Lista de animais** (categoria "Resgata WP" no editor).
- Pattern **Gatos em destaque** (com o Resgata Tema ativo).
- Shortcode: `[rwp_animais especie="gato" quantidade="6" colunas="3" filtros="nao"]`

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
cp .env.example .env    # ajuste as portas se 8080/8025 já estiverem em uso
make setup              # sobe o ambiente, gera o build, instala o WordPress e cria animais de exemplo
```

Depois disso:

- Site: <http://localhost:8080> (ou a porta definida em `WP_PORT`)
- Painel Resgata: <http://localhost:8080/painel> (usuário de teste em `WP_PANEL_USER` / `WP_PANEL_PASSWORD` do `.env`)
- WordPress: <http://localhost:8080/wp-admin> (`WP_ADMIN_USER` / `WP_ADMIN_PASSWORD` do `.env`)
- E-mails capturados pelo Mailpit: <http://localhost:8025>

Comandos mais usados (`make help` mostra todos):

| Comando | O que faz |
|---|---|
| `make up` / `make down` | Sobe / derruba o ambiente (os dados ficam guardados) |
| `make install` | Instala o WordPress em pt_BR, garante o Twenty Twenty-Five e ativa plugin e tema |
| `make seed` / `make unseed` | Cria / remove os animais de exemplo |
| `make wp cmd="plugin list"` | Roda qualquer comando WP-CLI |
| `make build` / `make watch` | Compila o TypeScript (painel, blocos, wp-admin) uma vez / a cada alteração |
| `make typecheck` | Confere os tipos do TypeScript |
| `make test` | Testa as permissões de todas as rotas da API do painel |
| `make zip` | Gera `dist/resgata-wp.zip` para instalar em um site |
| `make phpcs` / `make phpcbf` | Verifica / corrige o padrão de código |
| `make check` | Roda o Plugin Check |
| `make clean` | Derruba o ambiente e apaga banco e uploads |

O ambiente roda com `WP_DEBUG` ligado. O arquivo `.env` não vai para o git; use somente
chaves de **sandbox** nele.

## Personalizando pelo tema

O plugin traz templates e estilos neutros. Um tema pode:

- **Assumir o visual:** declare `add_theme_support( 'resgata-wp' );` e estilize as classes
  `.rwp-*`. O plugin deixa de carregar o CSS dele.
- **Substituir um template PHP:** copie o arquivo de `plugins/resgata-wp/templates/` para a
  pasta `resgata-wp/` do tema (por exemplo, `seu-tema/resgata-wp/animal-card.php`).
- **Temas de blocos:** crie `templates/single-rwp_animal.html` e
  `templates/archive-rwp_animal.html`, ou edite os modelos "Página do animal" e
  "Lista de animais" no Editor do Site.

## Contribuindo

- Regra de ouro: **funcionalidade no plugin, aparência no tema**.
- Siga os WordPress Coding Standards (`make phpcs`) e as regras de segurança descritas em
  [`CLAUDE.md`](CLAUDE.md), que também documenta a arquitetura.

## Licença

[GPL-2.0-or-later](LICENSE).
