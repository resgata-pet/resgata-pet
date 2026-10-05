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

defined( 'ABSPATH' ) || exit;

$rwp_animal    = $rwp_args['animal'];
$rwp_permalink = get_permalink( $rwp_animal->id() );
$rwp_name      = get_the_title( $rwp_animal->id() );
$rwp_summary   = $rwp_animal->summary();
?>
<li class="rwp-card rwp-card--<?php echo esc_attr( $rwp_animal->status() ); ?>">
	<a class="rwp-card__media" href="<?php echo esc_url( $rwp_permalink ); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail( $rwp_animal->id() ) ) {
			echo get_the_post_thumbnail( $rwp_animal->id(), 'medium_large', array( 'alt' => '' ) );
		} else {
			echo '<span class="rwp-card__placeholder"></span>';
		}
		?>
		<?php if ( 'available' !== $rwp_animal->status() ) : ?>
			<span class="rwp-badge rwp-badge--<?php echo esc_attr( $rwp_animal->status() ); ?>"><?php echo esc_html( $rwp_animal->status_label() ); ?></span>
		<?php endif; ?>
	</a>
	<div class="rwp-card__body">
		<h3 class="rwp-card__name"><a href="<?php echo esc_url( $rwp_permalink ); ?>"><?php echo esc_html( $rwp_name ); ?></a></h3>
		<?php if ( $rwp_summary ) : ?>
			<p class="rwp-card__meta"><?php echo esc_html( implode( ' · ', $rwp_summary ) ); ?></p>
		<?php endif; ?>
	</div>
</li>
