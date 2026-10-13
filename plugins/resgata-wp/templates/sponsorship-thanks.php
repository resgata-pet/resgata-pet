<?php
/**
 * Agradecimento ao padrinho, mostrado depois do pagamento.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/sponsorship-thanks.php`.
 *
 * @package ResgataWP
 *
 * @var array{wrapper_attributes: string, image_url: string, title: string, text: string, area_url: string, animals_url: string} $resgata_args
 */

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo $resgata_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<?php if ( '' !== $resgata_args['image_url'] ) : ?>
		<img class="resgata-sponsorship-thanks__image" src="<?php echo esc_url( $resgata_args['image_url'] ); ?>" alt="" decoding="async">
	<?php endif; ?>

	<h2 class="resgata-sponsorship__title"><?php echo esc_html( $resgata_args['title'] ); ?></h2>

	<div class="resgata-sponsorship-thanks__text">
		<?php echo wp_kses_post( wpautop( esc_html( $resgata_args['text'] ) ) ); ?>
	</div>

	<?php if ( '' !== $resgata_args['area_url'] || '' !== $resgata_args['animals_url'] ) : ?>
		<p class="resgata-sponsorship-thanks__actions">
			<?php if ( '' !== $resgata_args['animals_url'] ) : ?>
				<a class="resgata-button wp-element-button" href="<?php echo esc_url( $resgata_args['animals_url'] ); ?>"><?php esc_html_e( 'Ver os animais', 'resgata-wp' ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $resgata_args['area_url'] ) : ?>
				<a class="resgata-sponsorship-thanks__link" href="<?php echo esc_url( $resgata_args['area_url'] ); ?>"><?php esc_html_e( 'Abrir a área do padrinho', 'resgata-wp' ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>
