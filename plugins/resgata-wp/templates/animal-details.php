<?php
/**
 * Ficha do animal: dados, saúde e galeria de fotos.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/animal-details.php`.
 *
 * @package ResgataWP
 *
 * @var array{wrapper_attributes: string, animal: ResgataWP\Modules\Animals\Animal, sections: string[]} $rwp_args
 */

use ResgataWP\Modules\Animals\Animal;
use ResgataWP\Modules\Animals\Options;

defined( 'ABSPATH' ) || exit;

$rwp_animal = $rwp_args['animal'];

ob_start();

if ( in_array( 'facts', $rwp_args['sections'], true ) ) :
	$rwp_facts = array_filter(
		array(
			__( 'Espécie', 'resgata-wp' )        => $rwp_animal->species_label(),
			__( 'Sexo', 'resgata-wp' )           => $rwp_animal->sex_label(),
			__( 'Idade estimada', 'resgata-wp' ) => $rwp_animal->age_label(),
			__( 'Porte', 'resgata-wp' )          => $rwp_animal->size_label(),
			__( 'Cor / pelagem', 'resgata-wp' )  => $rwp_animal->color(),
			__( 'Temperamento', 'resgata-wp' )   => implode( ', ', $rwp_animal->temperaments() ),
			__( 'Resgatado em', 'resgata-wp' )   => Animal::format_date( $rwp_animal->rescue_date() ),
		)
	);
	?>
	<section class="rwp-animal__section rwp-animal__facts">
		<p class="rwp-animal__status">
			<span class="rwp-badge rwp-badge--<?php echo esc_attr( $rwp_animal->status() ); ?>"><?php echo esc_html( $rwp_animal->status_label() ); ?></span>
		</p>
		<?php if ( $rwp_facts ) : ?>
			<dl class="rwp-facts">
				<?php foreach ( $rwp_facts as $rwp_label => $rwp_value ) : ?>
					<div class="rwp-facts__item">
						<dt><?php echo esc_html( $rwp_label ); ?></dt>
						<dd><?php echo esc_html( $rwp_value ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</section>
	<?php
endif;

if ( in_array( 'health', $rwp_args['sections'], true ) ) :
	$rwp_tests    = Options::test_results();
	$rwp_yes_no   = Options::yes_no();
	$rwp_vaccines = $rwp_animal->vaccines();
	$rwp_health   = array_filter(
		array(
			__( 'Castrado(a)', 'resgata-wp' )      => $rwp_yes_no[ $rwp_animal->neutered() ] ?? '',
			__( 'Último vermífugo', 'resgata-wp' ) => Animal::format_date( $rwp_animal->dewormed_date() ),
			__( 'Teste de FIV', 'resgata-wp' )     => $rwp_tests[ $rwp_animal->fiv() ] ?? '',
			__( 'Teste de FeLV', 'resgata-wp' )    => $rwp_tests[ $rwp_animal->felv() ] ?? '',
		)
	);

	if ( $rwp_health || $rwp_vaccines ) :
		?>
		<section class="rwp-animal__section rwp-animal__health">
			<h2 class="rwp-animal__heading"><?php esc_html_e( 'Saúde', 'resgata-wp' ); ?></h2>
			<?php if ( $rwp_health ) : ?>
				<dl class="rwp-facts">
					<?php foreach ( $rwp_health as $rwp_label => $rwp_value ) : ?>
						<div class="rwp-facts__item">
							<dt><?php echo esc_html( $rwp_label ); ?></dt>
							<dd><?php echo esc_html( $rwp_value ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( $rwp_vaccines ) : ?>
				<h3 class="rwp-animal__subheading"><?php esc_html_e( 'Vacinas', 'resgata-wp' ); ?></h3>
				<ul class="rwp-vaccine-list">
					<?php foreach ( $rwp_vaccines as $rwp_vaccine ) : ?>
						<li>
							<?php echo esc_html( $rwp_vaccine['name'] ); ?>
							<?php if ( '' !== $rwp_vaccine['date'] ) : ?>
								<span class="rwp-vaccine-list__date">(<?php echo esc_html( Animal::format_date( $rwp_vaccine['date'] ) ); ?>)</span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php
	endif;
endif;

if ( in_array( 'gallery', $rwp_args['sections'], true ) && $rwp_animal->gallery() ) :
	$rwp_groups = array(
		'before' => __( 'Antes', 'resgata-wp' ),
		'after'  => __( 'Depois', 'resgata-wp' ),
	);
	?>
	<section class="rwp-animal__section rwp-animal__gallery">
		<h2 class="rwp-animal__heading"><?php esc_html_e( 'Fotos', 'resgata-wp' ); ?></h2>
		<?php
		foreach ( $rwp_groups as $rwp_type => $rwp_group_label ) :
			$rwp_photos = $rwp_animal->gallery( $rwp_type );
			if ( ! $rwp_photos ) {
				continue;
			}
			?>
			<div class="rwp-gallery rwp-gallery--<?php echo esc_attr( $rwp_type ); ?>">
				<h3 class="rwp-animal__subheading"><?php echo esc_html( $rwp_group_label ); ?></h3>
				<ul class="rwp-gallery__list">
					<?php foreach ( $rwp_photos as $rwp_photo ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) wp_get_attachment_image_url( $rwp_photo['id'], 'large' ) ); ?>">
								<?php echo wp_get_attachment_image( $rwp_photo['id'], 'medium' ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</section>
	<?php
endif;

$rwp_inner = trim( (string) ob_get_clean() );

// Sem nada para mostrar (ex.: animal sem fotos na seção "galeria"), não imprime um bloco vazio.
if ( '' === $rwp_inner ) {
	return;
}
?>
<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Montado acima, com cada valor escapado.
	echo $rwp_inner;

	/**
	 * Disparado no fim da ficha do animal (ex.: botão "Quero adotar" do módulo de adoção).
	 *
	 * @param ResgataWP\Modules\Animals\Animal $rwp_animal Animal exibido.
	 * @param string[]                         $sections   Seções exibidas.
	 */
	do_action( 'rwp_animal_details_after', $rwp_animal, $rwp_args['sections'] );
	?>
</div>
