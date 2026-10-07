<?php
/**
 * Banners da página inicial: um por vez, rolando para o lado.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/banners.php`.
 *
 * @package ResgataWP
 *
 * @var array{
 *     wrapper_attributes: string,
 *     banners: array<int, array<string, mixed>>,
 *     first_is_h1: bool
 * } $rwp_args
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

$rwp_banners = $rwp_args['banners'];
$rwp_total   = count( $rwp_banners );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin; atributos do wrapper escapados na origem.
?>
<section <?php echo $rwp_args['wrapper_attributes']; ?> aria-label="<?php esc_attr_e( 'Destaques', 'resgata-wp' ); ?>" data-rwp-viewer>
	<ul class="rwp-banners__track" data-rwp-viewer-track<?php echo $rwp_total > 1 ? ' tabindex="0"' : ''; ?>>
		<?php
		foreach ( $rwp_banners as $rwp_index => $rwp_banner ) :
			$rwp_tag = 0 === $rwp_index && $rwp_args['first_is_h1'] ? 'h1' : 'h2';
			?>
			<li class="rwp-banner<?php echo $rwp_banner['image_id'] ? ' rwp-banner--has-image' : ''; ?>" id="rwp-banner-<?php echo esc_attr( (string) ( $rwp_index + 1 ) ); ?>" data-rwp-viewer-slide>
				<?php
				if ( $rwp_banner['image_id'] ) {
					echo wp_get_attachment_image(
						$rwp_banner['image_id'],
						'full',
						false,
						array(
							'class'         => 'rwp-banner__image',
							'alt'           => '',
							'sizes'         => '100vw',
							'loading'       => 0 === $rwp_index ? 'eager' : 'lazy',
							'fetchpriority' => 0 === $rwp_index ? 'high' : 'auto',
						)
					);
				}
				?>
				<div class="rwp-banner__content">
					<<?php echo $rwp_tag; ?> class="rwp-banner__title">
						<?php echo esc_html( $rwp_banner['title'] ); ?>
						<?php if ( '' !== $rwp_banner['highlight'] ) : ?>
							<span class="rwp-banner__highlight"><?php echo esc_html( $rwp_banner['highlight'] ); ?></span>
						<?php endif; ?>
					</<?php echo $rwp_tag; ?>>
					<?php if ( '' !== $rwp_banner['text'] ) : ?>
						<p class="rwp-banner__text"><?php echo esc_html( $rwp_banner['text'] ); ?></p>
					<?php endif; ?>
					<?php
					$rwp_has_button  = '' !== $rwp_banner['button_label'] && '' !== $rwp_banner['button_url'];
					$rwp_has_button2 = '' !== $rwp_banner['button2_label'] && '' !== $rwp_banner['button2_url'];
					?>
					<?php if ( $rwp_has_button || $rwp_has_button2 ) : ?>
						<p class="rwp-banner__actions">
							<?php if ( $rwp_has_button ) : ?>
								<a class="rwp-button rwp-button--primary wp-element-button" href="<?php echo esc_url( $rwp_banner['button_url'] ); ?>">
									<?php echo Icons::svg( 'heart' ); ?>
									<?php echo esc_html( $rwp_banner['button_label'] ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $rwp_has_button2 ) : ?>
								<a class="rwp-button rwp-button--outline" href="<?php echo esc_url( $rwp_banner['button2_url'] ); ?>">
									<?php echo esc_html( $rwp_banner['button2_label'] ); ?>
								</a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $rwp_total > 1 ) : ?>
		<?php // As setas só funcionam com o script: nascem escondidas e ele as mostra. ?>
		<button type="button" class="rwp-viewer__arrow rwp-viewer__arrow--prev" data-rwp-viewer-prev hidden>
			<?php echo Icons::svg( 'chevron-left' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Banner anterior', 'resgata-wp' ); ?></span>
		</button>
		<button type="button" class="rwp-viewer__arrow rwp-viewer__arrow--next" data-rwp-viewer-next hidden>
			<?php echo Icons::svg( 'chevron-right' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Próximo banner', 'resgata-wp' ); ?></span>
		</button>
		<ul class="rwp-banners__dots">
			<?php foreach ( $rwp_banners as $rwp_index => $rwp_banner ) : ?>
				<li>
					<a class="rwp-banners__dot" href="#rwp-banner-<?php echo esc_attr( (string) ( $rwp_index + 1 ) ); ?>" data-rwp-viewer-thumb <?php echo 0 === $rwp_index ? 'aria-current="true"' : ''; ?>>
						<span class="screen-reader-text">
							<?php
							/* translators: 1: número do banner, 2: total de banners. */
							echo esc_html( sprintf( __( 'Ver banner %1$d de %2$d', 'resgata-wp' ), $rwp_index + 1, $rwp_total ) );
							?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
