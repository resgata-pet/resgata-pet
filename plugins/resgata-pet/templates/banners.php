<?php
/**
 * Banners da página inicial: um por vez, rolando para o lado.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/banners.php`.
 *
 * @package ResgataPet
 *
 * @var array{
 *     wrapper_attributes: string,
 *     banners: array<int, array<string, mixed>>,
 *     first_is_h1: bool
 * } $resgata_args
 */

use ResgataPet\Core\Icons;

defined( 'ABSPATH' ) || exit;

$resgata_banners = $resgata_args['banners'];
$resgata_total   = count( $resgata_banners );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin; atributos do wrapper escapados na origem.
?>
<section <?php echo $resgata_args['wrapper_attributes']; ?> aria-label="<?php esc_attr_e( 'Destaques', 'resgata-pet' ); ?>" data-resgata-viewer>
	<ul class="resgata-banners__track" data-resgata-viewer-track<?php echo $resgata_total > 1 ? ' tabindex="0"' : ''; ?>>
		<?php
		foreach ( $resgata_banners as $resgata_index => $resgata_banner ) :
			$resgata_tag = 0 === $resgata_index && $resgata_args['first_is_h1'] ? 'h1' : 'h2';
			?>
			<li class="resgata-banner<?php echo $resgata_banner['image_id'] ? ' resgata-banner--has-image' : ''; ?>" id="resgata-banner-<?php echo esc_attr( (string) ( $resgata_index + 1 ) ); ?>" data-resgata-viewer-slide>
				<?php
				if ( $resgata_banner['image_id'] ) {
					echo wp_get_attachment_image(
						$resgata_banner['image_id'],
						'full',
						false,
						array(
							'class'         => 'resgata-banner__image',
							'alt'           => '',
							'sizes'         => '100vw',
							'loading'       => 0 === $resgata_index ? 'eager' : 'lazy',
							'fetchpriority' => 0 === $resgata_index ? 'high' : 'auto',
						)
					);
				}
				?>
				<div class="resgata-banner__content">
					<<?php echo $resgata_tag; ?> class="resgata-banner__title">
						<?php echo esc_html( $resgata_banner['title'] ); ?>
						<?php if ( '' !== $resgata_banner['highlight'] ) : ?>
							<span class="resgata-banner__highlight"><?php echo esc_html( $resgata_banner['highlight'] ); ?></span>
						<?php endif; ?>
					</<?php echo $resgata_tag; ?>>
					<?php if ( '' !== $resgata_banner['text'] ) : ?>
						<p class="resgata-banner__text"><?php echo esc_html( $resgata_banner['text'] ); ?></p>
					<?php endif; ?>
					<?php
					$resgata_has_button  = '' !== $resgata_banner['button_label'] && '' !== $resgata_banner['button_url'];
					$resgata_has_button2 = '' !== $resgata_banner['button2_label'] && '' !== $resgata_banner['button2_url'];
					?>
					<?php if ( $resgata_has_button || $resgata_has_button2 ) : ?>
						<p class="resgata-banner__actions">
							<?php if ( $resgata_has_button ) : ?>
								<a class="resgata-button resgata-button--primary wp-element-button" href="<?php echo esc_url( $resgata_banner['button_url'] ); ?>">
									<?php echo Icons::svg( 'heart' ); ?>
									<?php echo esc_html( $resgata_banner['button_label'] ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $resgata_has_button2 ) : ?>
								<a class="resgata-button resgata-button--outline" href="<?php echo esc_url( $resgata_banner['button2_url'] ); ?>">
									<?php echo esc_html( $resgata_banner['button2_label'] ); ?>
								</a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $resgata_total > 1 ) : ?>
		<?php // As setas só funcionam com o script: nascem escondidas e ele as mostra. ?>
		<button type="button" class="resgata-viewer__arrow resgata-viewer__arrow--prev" data-resgata-viewer-prev hidden>
			<?php echo Icons::svg( 'chevron-left' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Banner anterior', 'resgata-pet' ); ?></span>
		</button>
		<button type="button" class="resgata-viewer__arrow resgata-viewer__arrow--next" data-resgata-viewer-next hidden>
			<?php echo Icons::svg( 'chevron-right' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Próximo banner', 'resgata-pet' ); ?></span>
		</button>
		<ul class="resgata-banners__dots">
			<?php foreach ( $resgata_banners as $resgata_index => $resgata_banner ) : ?>
				<li>
					<a class="resgata-banners__dot" href="#resgata-banner-<?php echo esc_attr( (string) ( $resgata_index + 1 ) ); ?>" data-resgata-viewer-thumb <?php echo 0 === $resgata_index ? 'aria-current="true"' : ''; ?>>
						<span class="screen-reader-text">
							<?php
							/* translators: 1: número do banner, 2: total de banners. */
							echo esc_html( sprintf( __( 'Ver banner %1$d de %2$d', 'resgata-pet' ), $resgata_index + 1, $resgata_total ) );
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
