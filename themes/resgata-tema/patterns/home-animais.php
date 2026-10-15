<?php
/**
 * Title: Início: animais para adoção
 * Slug: resgata-tema/home-animais
 * Categories: resgata-tema, featured
 * Keywords: animais, adoção, destaque
 * Description: Quatro animais disponíveis para adoção, com link para ver todos.
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata Pet (`resgata-pet/link`), resolvidas a cada exibição.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"style":{"spacing":{"blockGap":"0.35rem"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"className":"is-style-text-subtitle"} -->
			<h2 class="wp-block-heading is-style-text-subtitle"><?php esc_html_e( 'Animais para adoção', 'resgata-tema' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"accent-4"} -->
			<p class="has-accent-4-color has-text-color"><?php esc_html_e( 'Conheça alguns dos nossos resgatados que estão à procura de um lar cheio de amor.', 'resgata-tema' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-resgata-arrow-link","metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"animals"}}}}} -->
			<div class="wp-block-button is-style-resgata-arrow-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Ver todos os animais', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:resgata-pet/animals-list {"statuses":["available"],"perPage":4,"columns":4,"showFilters":false,"showPagination":false} /-->
</div>
<!-- /wp:group -->
