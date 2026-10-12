<?php
/**
 * Title: Animais: título da lista
 * Slug: resgata-tema/animais-chamada
 * Categories: resgata-tema, banner
 * Keywords: animais, adoção, título, lista
 * Description: Título e apresentação da página que lista os animais para adoção.
 * Viewport Width: 1400
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"0.5rem"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading"><?php esc_html_e( 'Animais para adoção', 'resgata-tema' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textColor":"accent-4"} -->
	<p class="has-accent-4-color has-text-color"><?php esc_html_e( 'Conheça quem está esperando por um lar. Todos foram resgatados, cuidados e estão prontos para receber amor.', 'resgata-tema' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
