<?php
/**
 * Faixa de números da protetora.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/impact.php`.
 *
 * @package ResgataWP
 *
 * @var array{
 *     wrapper_attributes: string,
 *     items: array<int, array{icon: string, value: int, label: string, tone: string}>
 * } $rwp_args
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin; atributos do wrapper escapados na origem.
?>
<section <?php echo $rwp_args['wrapper_attributes']; ?> aria-label="<?php esc_attr_e( 'Nossos números', 'resgata-wp' ); ?>">
	<dl class="rwp-impact__list">
		<?php foreach ( $rwp_args['items'] as $rwp_item ) : ?>
			<div class="rwp-impact__item rwp-impact__item--<?php echo esc_attr( sanitize_html_class( (string) ( $rwp_item['tone'] ?? 'green' ) ) ); ?>">
				<dt class="rwp-impact__label"><?php echo esc_html( $rwp_item['label'] ); ?></dt>
				<dd class="rwp-impact__value">
					<span class="rwp-impact__icon"><?php echo Icons::svg( (string) ( $rwp_item['icon'] ?? 'paw-print' ) ); ?></span>
					<?php echo esc_html( number_format_i18n( (int) $rwp_item['value'] ) ); ?>
				</dd>
			</div>
		<?php endforeach; ?>
	</dl>
</section>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
