<?php
/**
 * Cartão de um animal na listagem.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/animal-card.php`.
 *
 * @package ResgataWP
 *
 * @var array{animal: ResgataWP\Modules\Animals\Animal} $rwp_args
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

$rwp_animal    = $rwp_args['animal'];
$rwp_permalink = get_permalink( $rwp_animal->id() );
$rwp_name      = get_the_title( $rwp_animal->id() );
$rwp_summary   = $rwp_animal->card_summary();
// No cartão cabem até três temperamentos; os demais aparecem na página do animal.
$rwp_tags = array_slice( $rwp_animal->temperaments(), 0, 3 );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin, com a classe já escapada.
?>
<li class="rwp-card rwp-card--<?php echo esc_attr( $rwp_animal->status() ); ?>">
	<div class="rwp-card__media">
		<?php
		if ( has_post_thumbnail( $rwp_animal->id() ) ) {
			echo get_the_post_thumbnail( $rwp_animal->id(), 'medium_large', array( 'alt' => '' ) );
		} else {
			echo '<span class="rwp-card__placeholder">' . Icons::svg( 'paw-print' ) . '</span>';
		}
		?>
		<span class="rwp-badge rwp-badge--<?php echo esc_attr( $rwp_animal->status() ); ?>"><?php echo esc_html( $rwp_animal->status_label() ); ?></span>
	</div>
	<div class="rwp-card__body">
		<?php // O link do nome cobre o cartão inteiro (ver CSS); o "Ver perfil" é só visual. ?>
		<h3 class="rwp-card__name"><a href="<?php echo esc_url( $rwp_permalink ); ?>"><?php echo esc_html( $rwp_name ); ?></a></h3>
		<?php if ( $rwp_summary ) : ?>
			<p class="rwp-card__meta">
				<?php echo Icons::svg( 'paw-print' ); ?>
				<?php echo esc_html( implode( ' • ', $rwp_summary ) ); ?>
			</p>
		<?php endif; ?>
		<?php if ( $rwp_tags ) : ?>
			<ul class="rwp-tags" aria-label="<?php esc_attr_e( 'Temperamento', 'resgata-wp' ); ?>">
				<?php foreach ( $rwp_tags as $rwp_tag ) : ?>
					<li class="rwp-tag"><?php echo esc_html( $rwp_tag ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<span class="rwp-card__cta" aria-hidden="true"><?php esc_html_e( 'Ver perfil', 'resgata-wp' ); ?></span>
	</div>
</li>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
