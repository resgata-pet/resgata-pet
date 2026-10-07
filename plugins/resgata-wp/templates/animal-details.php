<?php
/**
 * Página do animal: fotos, resumo, história, saúde, outras formas
 * de ajudar e outros animais para adoção.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/animal-details.php`.
 *
 * @package ResgataWP
 *
 * @var array{
 *     wrapper_attributes: string,
 *     animal: ResgataWP\Modules\Animals\Animal,
 *     sections: string[],
 *     is_page: bool,
 *     show_title: bool,
 *     name: string,
 *     archive_url: string,
 *     photos: array<int, array{id: int, type: string}>,
 *     facts: array<int, array{icon: string, label: string, value: string}>,
 *     adopt_url: string,
 *     adopt_label: string,
 *     whatsapp_url: string,
 *     story: string,
 *     story_title: string,
 *     health: array<int, array{label: string, done: bool}>,
 *     tests: array<string, string>,
 *     vaccines: array<int, array{name: string, date: string}>,
 *     help_links: array<int, array{label: string, url: string, icon: string}>,
 *     related: int[]
 * } $rwp_args
 */

use ResgataWP\Core\Icons;
use ResgataWP\Modules\Animals\Animal;

defined( 'ABSPATH' ) || exit;

$rwp_animal = $rwp_args['animal'];
$rwp_name   = $rwp_args['name'];
$rwp_has    = static fn( string $rwp_key ): bool => in_array( $rwp_key, $rwp_args['sections'], true );
$rwp_parts  = array();

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin, com a classe já escapada.

if ( $rwp_has( 'breadcrumb' ) ) :
	ob_start();
	?>
	<nav class="rwp-breadcrumb" aria-label="<?php esc_attr_e( 'Você está em', 'resgata-wp' ); ?>">
		<ol class="rwp-breadcrumb__list">
			<li>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php echo Icons::svg( 'house' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Início', 'resgata-wp' ); ?></span>
				</a>
			</li>
			<?php if ( '' !== $rwp_args['archive_url'] ) : ?>
				<li>
					<?php echo Icons::svg( 'chevron-right', 'rwp-breadcrumb__separator' ); ?>
					<a href="<?php echo esc_url( $rwp_args['archive_url'] ); ?>"><?php esc_html_e( 'Animais', 'resgata-wp' ); ?></a>
				</li>
			<?php endif; ?>
			<li aria-current="page">
				<?php echo Icons::svg( 'chevron-right', 'rwp-breadcrumb__separator' ); ?>
				<?php echo esc_html( $rwp_name ); ?>
			</li>
		</ol>
	</nav>
	<?php
	$rwp_parts['breadcrumb'] = (string) ob_get_clean();
endif;

if ( $rwp_has( 'gallery' ) && $rwp_args['photos'] ) :
	$rwp_photos = $rwp_args['photos'];
	$rwp_total  = count( $rwp_photos );
	ob_start();
	?>
	<section class="rwp-animal__section rwp-viewer" aria-label="<?php esc_attr_e( 'Fotos', 'resgata-wp' ); ?>" data-rwp-viewer>
		<div class="rwp-viewer__stage">
			<ul class="rwp-viewer__track" data-rwp-viewer-track tabindex="0">
				<?php foreach ( $rwp_photos as $rwp_index => $rwp_photo ) : ?>
					<li class="rwp-viewer__slide" id="rwp-foto-<?php echo esc_attr( (string) ( $rwp_index + 1 ) ); ?>" data-rwp-viewer-slide>
						<?php
						echo wp_get_attachment_image(
							$rwp_photo['id'],
							'large',
							false,
							array(
								'loading'       => 0 === $rwp_index ? 'eager' : 'lazy',
								'fetchpriority' => 0 === $rwp_index ? 'high' : 'auto',
								'sizes'         => '(min-width: 960px) 55vw, 100vw',
							)
						);
						?>
						<?php if ( 'before' === $rwp_photo['type'] ) : ?>
							<span class="rwp-viewer__caption"><?php esc_html_e( 'Antes do resgate', 'resgata-wp' ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<span class="rwp-badge rwp-badge--<?php echo esc_attr( $rwp_animal->status() ); ?> rwp-viewer__status">
				<?php echo Icons::svg( 'paw-print' ); ?>
				<?php echo esc_html( $rwp_animal->status_label() ); ?>
			</span>
			<?php if ( $rwp_total > 1 ) : ?>
				<?php // As setas só funcionam com o script: nascem escondidas e ele as mostra. ?>
				<button type="button" class="rwp-viewer__arrow rwp-viewer__arrow--prev" data-rwp-viewer-prev hidden>
					<?php echo Icons::svg( 'chevron-left' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Foto anterior', 'resgata-wp' ); ?></span>
				</button>
				<button type="button" class="rwp-viewer__arrow rwp-viewer__arrow--next" data-rwp-viewer-next hidden>
					<?php echo Icons::svg( 'chevron-right' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Próxima foto', 'resgata-wp' ); ?></span>
				</button>
			<?php endif; ?>
		</div>
		<?php if ( $rwp_total > 1 ) : ?>
			<ul class="rwp-viewer__thumbs">
				<?php foreach ( $rwp_photos as $rwp_index => $rwp_photo ) : ?>
					<li>
						<a
							class="rwp-viewer__thumb"
							href="#rwp-foto-<?php echo esc_attr( (string) ( $rwp_index + 1 ) ); ?>"
							data-rwp-viewer-thumb
							<?php echo 0 === $rwp_index ? 'aria-current="true"' : ''; ?>
						>
							<?php echo wp_get_attachment_image( $rwp_photo['id'], 'thumbnail', false, array( 'alt' => '' ) ); ?>
							<span class="screen-reader-text">
								<?php
								/* translators: 1: número da foto, 2: total de fotos. */
								echo esc_html( sprintf( __( 'Ver foto %1$d de %2$d', 'resgata-wp' ), $rwp_index + 1, $rwp_total ) );
								?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
	$rwp_parts['gallery'] = (string) ob_get_clean();
endif;

if ( $rwp_has( 'summary' ) ) :
	$rwp_temperaments = $rwp_animal->temperaments();
	ob_start();
	?>
	<section class="rwp-animal__section rwp-animal__summary">
		<?php if ( $rwp_args['show_title'] ) : ?>
			<h1 class="rwp-animal__name"><?php echo esc_html( $rwp_name ); ?></h1>
		<?php endif; ?>
		<?php if ( ! $rwp_has( 'gallery' ) || ! $rwp_args['photos'] ) : ?>
			<?php // Sem a foto ao lado, a situação aparece aqui. ?>
			<p class="rwp-animal__status">
				<span class="rwp-badge rwp-badge--<?php echo esc_attr( $rwp_animal->status() ); ?>"><?php echo esc_html( $rwp_animal->status_label() ); ?></span>
			</p>
		<?php endif; ?>
		<?php if ( '' !== $rwp_animal->tagline() ) : ?>
			<p class="rwp-animal__tagline"><?php echo esc_html( $rwp_animal->tagline() ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $rwp_animal->intro() ) : ?>
			<div class="rwp-animal__intro"><?php echo wp_kses_post( wpautop( esc_html( $rwp_animal->intro() ) ) ); ?></div>
		<?php endif; ?>

		<?php if ( $rwp_args['facts'] ) : ?>
			<dl class="rwp-facts">
				<?php foreach ( $rwp_args['facts'] as $rwp_fact ) : ?>
					<div class="rwp-facts__item">
						<dt><?php echo Icons::svg( $rwp_fact['icon'], 'rwp-facts__icon' ); ?><?php echo esc_html( $rwp_fact['label'] ); ?></dt>
						<dd><?php echo esc_html( $rwp_fact['value'] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>

		<?php if ( $rwp_temperaments ) : ?>
			<ul class="rwp-tags" aria-label="<?php esc_attr_e( 'Temperamento', 'resgata-wp' ); ?>">
				<?php foreach ( $rwp_temperaments as $rwp_temperament ) : ?>
					<li class="rwp-tag"><?php echo esc_html( $rwp_temperament ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( '' !== $rwp_args['adopt_url'] || '' !== $rwp_args['whatsapp_url'] ) : ?>
			<p class="rwp-animal__actions">
				<?php if ( '' !== $rwp_args['adopt_url'] ) : ?>
					<a class="rwp-button rwp-button--primary wp-element-button" href="<?php echo esc_url( $rwp_args['adopt_url'] ); ?>">
						<?php echo Icons::svg( 'paw-print' ); ?>
						<?php echo esc_html( $rwp_args['adopt_label'] ); ?>
					</a>
				<?php endif; ?>
				<?php if ( '' !== $rwp_args['whatsapp_url'] ) : ?>
					<a class="rwp-button rwp-button--outline" href="<?php echo esc_url( $rwp_args['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo Icons::whatsapp(); ?>
						<?php esc_html_e( 'Falar pelo WhatsApp', 'resgata-wp' ); ?>
					</a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</section>
	<?php
	$rwp_parts['summary'] = (string) ob_get_clean();
endif;

if ( $rwp_has( 'story' ) && '' !== $rwp_args['story'] ) :
	ob_start();
	?>
	<section class="rwp-animal__section rwp-panel rwp-animal__story">
		<h2 class="rwp-panel__title">
			<?php echo Icons::svg( 'file-text' ); ?>
			<?php echo esc_html( $rwp_args['story_title'] ); ?>
		</h2>
		<div class="rwp-animal__story-text">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Conteúdo do post, já passado pelos filtros de `the_content`.
			echo $rwp_args['story'];
			?>
		</div>
	</section>
	<?php
	$rwp_parts['story'] = (string) ob_get_clean();
endif;

if ( $rwp_has( 'health' ) && ( $rwp_args['health'] || $rwp_args['tests'] || $rwp_args['vaccines'] ) ) :
	ob_start();
	?>
	<section class="rwp-animal__section rwp-panel rwp-animal__health">
		<h2 class="rwp-panel__title">
			<?php echo Icons::svg( 'heart-pulse' ); ?>
			<?php esc_html_e( 'Saúde', 'resgata-wp' ); ?>
		</h2>
		<?php if ( $rwp_args['health'] ) : ?>
			<ul class="rwp-checklist">
				<?php foreach ( $rwp_args['health'] as $rwp_item ) : ?>
					<li class="rwp-checklist__item<?php echo $rwp_item['done'] ? '' : ' rwp-checklist__item--pending'; ?>">
						<?php echo Icons::svg( $rwp_item['done'] ? 'circle-check' : 'circle-dashed' ); ?>
						<?php echo esc_html( $rwp_item['label'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $rwp_args['tests'] ) : ?>
			<dl class="rwp-pairs">
				<?php foreach ( $rwp_args['tests'] as $rwp_label => $rwp_value ) : ?>
					<div class="rwp-pairs__item">
						<dt><?php echo esc_html( $rwp_label ); ?></dt>
						<dd><?php echo esc_html( $rwp_value ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
		<?php if ( $rwp_args['vaccines'] ) : ?>
			<h3 class="rwp-animal__subheading"><?php esc_html_e( 'Vacinas aplicadas', 'resgata-wp' ); ?></h3>
			<ul class="rwp-vaccine-list">
				<?php foreach ( $rwp_args['vaccines'] as $rwp_vaccine ) : ?>
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
	$rwp_parts['health'] = (string) ob_get_clean();
endif;

if ( $rwp_has( 'help' ) && $rwp_args['help_links'] ) :
	ob_start();
	?>
	<section class="rwp-animal__section rwp-panel rwp-animal__help">
		<h2 class="rwp-panel__title">
			<?php echo Icons::svg( 'heart' ); ?>
			<?php esc_html_e( 'Não pode adotar?', 'resgata-wp' ); ?>
		</h2>
		<p class="rwp-animal__help-text"><?php esc_html_e( 'Você também pode ajudar de outras formas.', 'resgata-wp' ); ?></p>
		<p class="rwp-animal__help-actions">
			<?php foreach ( $rwp_args['help_links'] as $rwp_index => $rwp_link ) : ?>
				<a class="rwp-button <?php echo 0 === $rwp_index ? 'rwp-button--help wp-element-button' : 'rwp-button--help-outline'; ?>" href="<?php echo esc_url( $rwp_link['url'] ); ?>">
					<?php echo esc_html( $rwp_link['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</p>
	</section>
	<?php
	$rwp_parts['help'] = (string) ob_get_clean();
endif;

if ( $rwp_has( 'related' ) && $rwp_args['related'] ) :
	ob_start();
	?>
	<section class="rwp-animal__section rwp-panel rwp-related" aria-labelledby="rwp-outros-animais">
		<div class="rwp-related__header">
			<h2 class="rwp-panel__title" id="rwp-outros-animais">
				<?php echo Icons::svg( 'paw-print' ); ?>
				<?php esc_html_e( 'Outros animais para adoção', 'resgata-wp' ); ?>
			</h2>
			<?php if ( '' !== $rwp_args['archive_url'] ) : ?>
				<a class="rwp-related__all" href="<?php echo esc_url( $rwp_args['archive_url'] ); ?>">
					<?php esc_html_e( 'Ver todos', 'resgata-wp' ); ?>
					<?php echo Icons::svg( 'arrow-right' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<ul class="rwp-related__list" tabindex="0" aria-labelledby="rwp-outros-animais">
			<?php
			foreach ( $rwp_args['related'] as $rwp_related_id ) :
				$rwp_related = new Animal( $rwp_related_id );
				$rwp_meta    = array_filter( array( $rwp_related->species_label(), $rwp_related->age_label() ) );
				?>
				<li class="rwp-related__item">
					<a class="rwp-related__link" href="<?php echo esc_url( (string) get_permalink( $rwp_related_id ) ); ?>">
						<span class="rwp-related__media">
							<?php
							if ( has_post_thumbnail( $rwp_related_id ) ) {
								echo get_the_post_thumbnail( $rwp_related_id, 'medium', array( 'alt' => '' ) );
							}
							?>
						</span>
						<span class="rwp-related__name"><?php echo esc_html( get_the_title( $rwp_related_id ) ); ?></span>
						<?php if ( $rwp_meta ) : ?>
							<span class="rwp-related__meta"><?php echo esc_html( implode( ' • ', $rwp_meta ) ); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
	$rwp_parts['related'] = (string) ob_get_clean();
endif;

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

// Sem nada para mostrar (ex.: seção "galeria" de um animal sem fotos), não imprime um bloco vazio.
if ( ! $rwp_parts ) {
	return;
}

$rwp_group = static function ( array $rwp_keys ) use ( $rwp_parts ): string {
	return implode( '', array_intersect_key( $rwp_parts, array_flip( $rwp_keys ) ) );
};

$rwp_top   = $rwp_group( array( 'gallery', 'summary' ) );
$rwp_cards = $rwp_group( array( 'story', 'health', 'help' ) );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Seções montadas acima, com cada valor escapado; atributos do wrapper escapados na origem.
?>
<div <?php echo $rwp_args['wrapper_attributes']; ?>>
	<?php
	echo $rwp_parts['breadcrumb'] ?? '';

	if ( $rwp_args['is_page'] ) {
		// Página inteira: fotos ao lado do resumo e, abaixo, os cartões lado a lado.
		if ( '' !== $rwp_top ) {
			echo '<div class="rwp-animal__top">' . $rwp_top . '</div>';
		}
		if ( '' !== $rwp_cards ) {
			echo '<div class="rwp-animal__cards">' . $rwp_cards . '</div>';
		}
	} else {
		echo $rwp_top . $rwp_cards;
	}

	echo $rwp_parts['related'] ?? '';

	/**
	 * Disparado no fim da página do animal.
	 *
	 * @param ResgataWP\Modules\Animals\Animal $rwp_animal Animal exibido.
	 * @param string[]                         $sections   Seções exibidas.
	 */
	do_action( 'rwp_animal_details_after', $rwp_animal, $rwp_args['sections'] );
	?>
</div>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
