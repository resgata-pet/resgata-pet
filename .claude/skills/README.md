# Skills do projeto

Skills são instruções que o Claude Code (e outros assistentes compatíveis) carrega quando a
tarefa pede. As desta pasta ensinam as práticas atuais do WordPress e valem para qualquer
pessoa que desenvolva o Resgata WP com um assistente.

## O que vai para o repositório

Só as skills oficiais do WordPress, copiadas de
[WordPress/agent-skills](https://github.com/WordPress/agent-skills):

| Skill | Use quando for mexer em |
| --- | --- |
| `wordpress-router` | Qualquer tarefa de WordPress: aponta a skill certa |
| `wp-project-triage` | Inspeção geral do repositório (ferramentas, testes, versões) |
| `wp-plugin-development` | Estrutura do plugin, hooks, ativação, desinstalação, segurança |
| `wp-rest-api` | Rotas da API do painel (`resgata-wp/v1`), schema e permissões |
| `wp-block-development` | Blocos do plugin (`blocks/`, `block.json`, `render.php`) |
| `wp-block-themes` | `theme.json`, modelos e parts do `resgata-tema` |
| `wp-patterns` | Patterns do tema (`themes/resgata-tema/patterns/`) |
| `wp-interactivity-api` | Interatividade nos blocos com a Interactivity API |
| `wpds` | Interfaces com o WordPress Design System (wp-admin e editor) |
| `wp-wpcli-and-ops` | Comandos WP-CLI (`wp resgata ...`) e operação do site |
| `wp-performance` | Desempenho: consultas, options carregadas, cache |
| `wp-phpstan` | Análise estática com PHPStan |
| `wp-plugin-directory-guidelines` | Regras do diretório de plugins do WordPress.org e licenças |
| `wp-env` | Ambiente local com `@wordpress/env` (o projeto usa Docker; ver o README) |
| `wp-playground` | Rodar ou compartilhar o site no WordPress Playground |
| `blueprint` | Escrever ou revisar um Blueprint do Playground |
| `wp-abilities-api`, `wp-abilities-audit`, `wp-abilities-verify` | Abilities API do WordPress |

Para atualizar, copie de novo a pasta da skill do repositório de origem e faça um commit só
com essa mudança.

## O que fica só na sua máquina

Skills pessoais (produtividade, estilo de trabalho, ferramentas suas) não vão para o git. O
`.gitignore` recusa tudo em `.claude/skills/` que não esteja na lista acima, além de
`.agents/` e `skills-lock.json`, que é onde o instalador `npx skills` guarda as skills e cria
os atalhos para esta pasta.

## Regras

- **Uma pasta por skill, direto aqui.** O assistente só encontra `.claude/skills/<nome>/SKILL.md`;
  uma skill dentro de subpasta (`.claude/skills/wordpress/<nome>/`) deixa de ser carregada.
- **Skill nova do WordPress:** acrescente a pasta e libere o nome no `.gitignore` (as que
  começam com `wp-` já estão liberadas).
- **As regras do Resgata WP não ficam em skill:** estão no `CLAUDE.md` (código) e no
  `designer.md` (visual), na raiz do repositório.
