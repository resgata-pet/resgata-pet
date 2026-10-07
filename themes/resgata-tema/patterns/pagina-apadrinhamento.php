<?php
/**
 * Title: Página de apadrinhamento
 * Slug: resgata-tema/pagina-apadrinhamento
 * Categories: resgata-tema, call-to-action
 * Keywords: padrinho, apadrinhar, planos, doação mensal
 * Block Types: core/post-content
 * Post Types: page
 * Description: Página completa para apadrinhar: chamada, planos, doação única por Pix, números, como funciona, animais e área do padrinho. Use com o modelo "Página sem título".
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata WP (`resgata-wp/link`), resolvidas a cada exibição.
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
		<h1 class="wp-block-heading is-style-text-display"><?php esc_html_e( 'Seja um padrinho', 'resgata-tema' ); ?><br><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-accent-3-color"><?php esc_html_e( 'e faça parte desta história!', 'resgata-tema' ); ?></mark></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"large"} -->
		<p class="has-large-font-size"><?php esc_html_e( 'Ao se tornar padrinho, você ajuda a garantir alimentação, cuidados veterinários e um futuro melhor para os nossos animais resgatados. Com um pequeno gesto mensal, você transforma vidas.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-resgata-heart"} -->
			<div class="wp-block-button is-style-resgata-heart"><a class="wp-block-button__link wp-element-button" href="#rwp-apadrinhar"><?php esc_html_e( 'Quero ser padrinho', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#rwp-pix"><?php esc_html_e( 'Doar uma vez por Pix', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"13rem"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:group {"className":"is-style-resgata-icon-paw","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-resgata-icon-paw">
		<!-- wp:heading {"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Apoio direto', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Aos animais resgatados.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"is-style-resgata-icon-shield","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-resgata-icon-shield">
		<!-- wp:heading {"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Pagamento seguro', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Com cartão, pela Stripe.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"is-style-resgata-icon-lock","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-resgata-icon-lock">
		<!-- wp:heading {"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Sem fidelidade', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Cancele quando quiser.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"is-style-resgata-icon-hand-heart","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-resgata-icon-hand-heart">
		<!-- wp:heading {"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Você faz parte', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
		<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Dessa mudança.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:resgata-wp/sponsorship-plans {"align":"wide"} /-->

<!-- wp:resgata-wp/pix-once {"align":"wide"} /-->

<!-- wp:resgata-wp/impact {"align":"wide"} /-->

<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:heading {"className":"is-style-text-subtitle"} -->
	<h2 class="wp-block-heading is-style-text-subtitle"><?php esc_html_e( 'Como funciona?', 'resgata-tema' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"14rem"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"className":"is-style-resgata-step","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-resgata-step">
			<!-- wp:heading {"level":3,"fontSize":"medium"} -->
			<h3 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Escolha o valor', 'resgata-tema' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
			<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Selecione quanto você quer contribuir por mês.', 'resgata-tema' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"is-style-resgata-step","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-resgata-step">
			<!-- wp:heading {"level":3,"fontSize":"medium"} -->
			<h3 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Pagamento seguro', 'resgata-tema' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
			<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Você informa o cartão na página da Stripe. Nós não guardamos os dados do cartão.', 'resgata-tema' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"is-style-resgata-step","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-resgata-step">
			<!-- wp:heading {"level":3,"fontSize":"medium"} -->
			<h3 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Sua ajuda chega todo mês', 'resgata-tema' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
			<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'A contribuição garante ração, vacinas e cuidados veterinários.', 'resgata-tema' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"is-style-resgata-step","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-resgata-step">
			<!-- wp:heading {"level":3,"fontSize":"medium"} -->
			<h3 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Você no controle', 'resgata-tema' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"accent-4","fontSize":"small"} -->
			<p class="has-accent-4-color has-text-color has-small-font-size"><?php esc_html_e( 'Mude o valor, troque o cartão ou cancele quando quiser, na área do padrinho.', 'resgata-tema' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"style":{"spacing":{"blockGap":"0.35rem"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"className":"is-style-text-subtitle"} -->
			<h2 class="wp-block-heading is-style-text-subtitle"><?php esc_html_e( 'Quem a sua ajuda alcança', 'resgata-tema' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"accent-4"} -->
			<p class="has-accent-4-color has-text-color"><?php esc_html_e( 'O apadrinhamento cuida de todos os resgatados, inclusive dos que ainda estão em tratamento.', 'resgata-tema' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-resgata-arrow-link","metadata":{"bindings":{"url":{"source":"resgata-wp/link","args":{"key":"animals"}}}}} -->
			<div class="wp-block-button is-style-resgata-arrow-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Ver todos os animais', 'resgata-tema' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:resgata-wp/animals-list {"statuses":["treatment","available"],"perPage":4,"columns":4,"showFilters":false,"showPagination":false} /-->
</div>
<!-- /wp:group -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:heading {"className":"is-style-text-subtitle"} -->
		<h2 class="wp-block-heading is-style-text-subtitle"><?php esc_html_e( 'Já é padrinho?', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4"} -->
		<p class="has-accent-4-color has-text-color"><?php esc_html_e( 'Informe o seu e-mail para trocar o cartão, mudar o valor ou cancelar o apadrinhamento.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:resgata-wp/sponsor-area /-->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
