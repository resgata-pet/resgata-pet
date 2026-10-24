<?php
/**
 * Title: Início: atalhos
 * Slug: resgata-tema/home-atalhos
 * Categories: resgata-tema
 * Keywords: atalhos, adoção, padrinho, doação
 * Description: Quatro atalhos logo abaixo dos banners: adotar, doar, apadrinhar e falar com a protetora. Os de adotar e apadrinhar somem com o módulo desligado, e a grade fica com uma coluna por atalho.
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata Pet (`resgata-pet/link`), resolvidas a cada exibição.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"wide","layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"14rem"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:group {"className":"is-style-resgata-icon-paw resgata-needs-animals","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-resgata-icon-paw resgata-needs-animals">
		<!-- wp:heading {"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Adoção responsável', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Dê um lar para quem precisa.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-resgata-arrow-link","metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"animals"}}}}} -->
			<div class="wp-block-button is-style-resgata-arrow-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Ver os animais', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"is-style-resgata-icon-heart","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-resgata-icon-heart">
		<!-- wp:heading {"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Faça uma doação', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Ajude a manter nossos resgatados.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-resgata-arrow-link","metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"donate"}}}}} -->
			<div class="wp-block-button is-style-resgata-arrow-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Doar por Pix', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"is-style-resgata-icon-hand-heart resgata-needs-sponsorship","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-resgata-icon-hand-heart resgata-needs-sponsorship">
		<!-- wp:heading {"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Seja um padrinho', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Contribua todo mês e faça a diferença.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-resgata-arrow-link","metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"sponsorship"}}}}} -->
			<div class="wp-block-button is-style-resgata-arrow-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Conhecer os planos', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"is-style-resgata-icon-message","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-resgata-icon-message">
		<!-- wp:heading {"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Fale com a gente', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Tire dúvidas pelo WhatsApp.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-resgata-arrow-link","metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"contact"}}}}} -->
			<div class="wp-block-button is-style-resgata-arrow-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Entrar em contato', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
