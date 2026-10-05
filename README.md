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
| Configurações (módulos ativos, retenção de dados) | Inicial |
| Pedidos de adoção | Planejado |
| Apadrinhamento | Planejado |
| Prestação de contas | Planejado |

## Requisitos

- WordPress 6.9 ou superior e PHP 8.1 ou superior.
- Para desenvolver: Docker (com Compose), Make e Composer.

## Instalação em um site WordPress

1. Copie `plugins/resgata-wp` para `wp-content/plugins/` e ative o plugin.
2. Opcional: instale o tema Twenty Twenty-Five, copie `themes/resgata-tema` para
   `wp-content/themes/` e ative o **Resgata Tema**.
3. Cadastre os animais em **Animais → Adicionar animal**.
4. A lista pública fica em `/animais/`. Se o endereço não abrir, vá em
   **Configurações → Links permanentes** e clique em **Salvar alterações**.

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
make setup              # sobe o ambiente, instala o WordPress e cria animais de exemplo
```

Depois disso:

- Site: <http://localhost:8080> (ou a porta definida em `WP_PORT`)
- Painel: <http://localhost:8080/wp-admin>, usuário `admin`, senha `admin`
- E-mails capturados pelo Mailpit: <http://localhost:8025>

Comandos mais usados (`make help` mostra todos):

| Comando | O que faz |
|---|---|
| `make up` / `make down` | Sobe / derruba o ambiente (os dados ficam guardados) |
| `make install` | Instala o WordPress em pt_BR, garante o Twenty Twenty-Five e ativa plugin e tema |
| `make seed` / `make unseed` | Cria / remove os animais de exemplo |
| `make wp cmd="plugin list"` | Roda qualquer comando WP-CLI |
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
