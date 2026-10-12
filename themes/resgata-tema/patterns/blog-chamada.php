<?php
/**
 * Title: Blog: chamada do topo
 * Slug: resgata-tema/blog-chamada
 * Categories: resgata-tema, banner
 * Keywords: blog, notícias, histórias, chamada
 * Description: Título e apresentação do blog, para o topo da página que lista as publicações.
 * Viewport Width: 1400
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"full","className":"is-style-resgata-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-resgata-hero">
	<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":1,"className":"is-style-text-display"} -->
		<h1 class="wp-block-heading is-style-text-display"><?php esc_html_e( 'Nosso blog', 'resgata-tema' ); ?><br><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-accent-3-color"><?php esc_html_e( 'histórias e novidades', 'resgata-tema' ); ?></mark></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"large"} -->
		<p class="has-large-font-size"><?php esc_html_e( 'Acompanhe os resgates, as adoções e o dia a dia de quem cuida dos nossos animais.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
