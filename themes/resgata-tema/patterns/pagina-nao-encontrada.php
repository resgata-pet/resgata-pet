<?php
/**
 * Title: Página não encontrada
 * Slug: resgata-tema/pagina-nao-encontrada
 * Categories: resgata-tema
 * Keywords: 404, erro, não encontrada
 * Description: Aviso de página não encontrada, com ilustração e atalhos para os animais e para o início.
 * Viewport Width: 1400
 *
 * Marcação de blocos pura: os links dos botões vêm das ligações de blocos do
 * plugin Resgata Pet (`resgata-pet/link`), resolvidas a cada exibição.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"is-style-resgata-art-lost-puppy","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-resgata-art-lost-puppy">
	<!-- wp:heading {"textAlign":"center","level":1,"className":"is-style-text-subtitle"} -->
	<h1 class="wp-block-heading has-text-align-center is-style-text-subtitle"><?php esc_html_e( 'Ops! Esta página se perdeu', 'resgata-tema' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center"><?php esc_html_e( 'O endereço pode ter mudado ou a página não existe mais. Que tal conhecer os animais que esperam por um lar?', 'resgata-tema' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"is-style-resgata-paw","metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"animals"}}}}} -->
		<div class="wp-block-button is-style-resgata-paw"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Ver os animais', 'resgata-tema' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline","metadata":{"bindings":{"url":{"source":"resgata-pet/link","args":{"key":"home"}}}}} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Voltar ao início', 'resgata-tema' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
