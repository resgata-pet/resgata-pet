<?php
/**
 * Agradecimento ao padrinho, mostrado depois do pagamento.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/sponsorship-thanks.php`.
 *
 * @package ResgataWP
 *
 * @var array{wrapper_attributes: string, image_url: string, title: string, text: string, area_url: string, animals_url: string} $rwp_args
 */

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<?php if ( '' !== $rwp_args['image_url'] ) : ?>
		<img class="rwp-sponsorship-thanks__image" src="<?php echo esc_url( $rwp_args['image_url'] ); ?>" alt="" decoding="async">
	<?php endif; ?>

	<h2 class="rwp-sponsorship__title"><?php echo esc_html( $rwp_args['title'] ); ?></h2>

	<div class="rwp-sponsorship-thanks__text">
		<?php echo wp_kses_post( wpautop( esc_html( $rwp_args['text'] ) ) ); ?>
	</div>

	<?php if ( '' !== $rwp_args['area_url'] || '' !== $rwp_args['animals_url'] ) : ?>
		<p class="rwp-sponsorship-thanks__actions">
			<?php if ( '' !== $rwp_args['animals_url'] ) : ?>
				<a class="rwp-button wp-element-button" href="<?php echo esc_url( $rwp_args['animals_url'] ); ?>"><?php esc_html_e( 'Ver os animais', 'resgata-wp' ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $rwp_args['area_url'] ) : ?>
				<a class="rwp-sponsorship-thanks__link" href="<?php echo esc_url( $rwp_args['area_url'] ); ?>"><?php esc_html_e( 'Abrir a área do padrinho', 'resgata-wp' ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>
