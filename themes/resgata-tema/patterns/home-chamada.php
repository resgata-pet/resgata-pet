<?php
/**
 * Title: Início: chamada para apadrinhar
 * Slug: resgata-tema/home-chamada
 * Categories: resgata-tema, call-to-action
 * Keywords: padrinho, apadrinhar, ajudar, chamada
 * Description: Faixa que fecha a página convidando a apadrinhar.
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata WP (`resgata-wp/link`), resolvidas a cada exibição.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"wide","className":"is-style-resgata-band","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide is-style-resgata-band">
	<!-- wp:group {"style":{"spacing":{"blockGap":"0.5rem"},"layout":{"selfStretch":"fill"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"className":"is-style-text-subtitle"} -->
		<h2 class="wp-block-heading is-style-text-subtitle"><?php esc_html_e( 'Seja um padrinho', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Com um pequeno gesto mensal, você ajuda a garantir alimentação, cuidados veterinários e um futuro melhor para os nossos resgatados.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"is-style-resgata-paw","metadata":{"bindings":{"url":{"source":"resgata-wp/link","args":{"key":"sponsorship"}}}}} -->
		<div class="wp-block-button is-style-resgata-paw"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Quero ser padrinho', 'resgata-tema' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
