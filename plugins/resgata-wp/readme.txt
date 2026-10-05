=== Resgata WP ===
Contributors: resgatawp
Tags: animais, adoção, protetora, resgate, ong
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Kit para protetoras independentes de animais: divulgação de animais para adoção, pedidos de adoção, apadrinhamento e prestação de contas.

== Description ==

O Resgata WP ajuda protetoras independentes de animais a divulgar os animais resgatados, receber pedidos de adoção, arrecadar com apadrinhamento e prestar contas com transparência.

Módulos:

* **Animais** (disponível): cadastro com ficha, saúde e galeria de fotos (antes e depois), listagem pública com filtros e página individual de cada animal.
* **Pedidos de adoção** (em breve)
* **Apadrinhamento** (em breve)
* **Prestação de contas** (em breve)

O plugin funciona com qualquer tema. Temas podem substituir os templates criando a pasta `resgata-wp/` e assumir o visual declarando `add_theme_support( 'resgata-wp' )`.

== Installation ==

1. Envie a pasta `resgata-wp` para `wp-content/plugins/`.
2. Ative o plugin em "Plugins".
3. Cadastre os animais em seusite.org/painel (ou em "Animais → Adicionar animal" no WordPress).
4. A lista pública fica em `/animais/`. Para mostrar animais em outra página, use o bloco "Lista de animais" ou o shortcode `[rwp_animais]`.

== Frequently Asked Questions ==

= Quais animais aparecem na lista pública? =

Por padrão, os que estão com a situação "Disponível para adoção" ou "Reservado". No bloco "Lista de animais" é possível escolher outras situações (por exemplo, "Adotado" para uma página de finais felizes).

= Como uso o shortcode? =

`[rwp_animais especie="gato" quantidade="6" colunas="3" filtros="nao" paginacao="nao" status="disponivel,reservado"]`

= O que acontece com os dados se eu excluir o plugin? =

Nada: os dados são mantidos. Para apagá-los, desmarque "Manter os dados" em "Resgata WP → Configurações" antes de excluir o plugin.

== Changelog ==

= 0.2.0 =
* Painel próprio em /painel, feito para o celular, com cadastro de animais em quatro passos.
* Novo perfil "Protetor admin", que usa somente o painel.
* API própria (resgata-wp/v1) com permissão exigida em todas as rotas.

= 0.1.0 =
* Primeira versão: esqueleto do plugin, configurações e módulo Animais.
