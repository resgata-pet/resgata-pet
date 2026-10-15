<?php
/**
 * Página do animal: fotos, resumo, história, saúde, outras formas
 * de ajudar e outros animais para adoção.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/animal-details.php`.
 *
 * @package ResgataPet
 *
 * @var array{
 *     wrapper_attributes: string,
 *     animal: ResgataPet\Modules\Animals\Animal,
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
 * } $resgata_args
 */

use ResgataPet\Core\Html;
use ResgataPet\Core\Icons;
use ResgataPet\Modules\Animals\Animal;

defined( 'ABSPATH' ) || exit;

$resgata_animal = $resgata_args['animal'];
$resgata_name   = $resgata_args['name'];
$resgata_has    = static fn( string $resgata_key ): bool => in_array( $resgata_key, $resgata_args['sections'], true );
$resgata_parts  = array();


if ( $resgata_has( 'breadcrumb' ) ) :
	ob_start();
	?>
	<nav class="resgata-breadcrumb" aria-label="<?php esc_attr_e( 'Você está em', 'resgata-pet' ); ?>">
		<ol class="resgata-breadcrumb__list">
			<li>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php echo wp_kses( Icons::svg( 'house' ), Html::svg() ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Início', 'resgata-pet' ); ?></span>
				</a>
			</li>
			<?php if ( '' !== $resgata_args['archive_url'] ) : ?>
				<li>
					<?php echo wp_kses( Icons::svg( 'chevron-right', 'resgata-breadcrumb__separator' ), Html::svg() ); ?>
					<a href="<?php echo esc_url( $resgata_args['archive_url'] ); ?>"><?php esc_html_e( 'Animais', 'resgata-pet' ); ?></a>
				</li>
			<?php endif; ?>
			<li aria-current="page">
				<?php echo wp_kses( Icons::svg( 'chevron-right', 'resgata-breadcrumb__separator' ), Html::svg() ); ?>
				<?php echo esc_html( $resgata_name ); ?>
			</li>
		</ol>
	</nav>
	<?php
	$resgata_parts['breadcrumb'] = (string) ob_get_clean();
endif;

if ( $resgata_has( 'gallery' ) && $resgata_args['photos'] ) :
	$resgata_photos = $resgata_args['photos'];
	$resgata_total  = count( $resgata_photos );
	ob_start();
	?>
	<section class="resgata-animal__section resgata-viewer" aria-label="<?php esc_attr_e( 'Fotos', 'resgata-pet' ); ?>" data-resgata-viewer>
		<div class="resgata-viewer__stage">
			<ul class="resgata-viewer__track" data-resgata-viewer-track tabindex="0">
				<?php foreach ( $resgata_photos as $resgata_index => $resgata_photo ) : ?>
					<li class="resgata-viewer__slide" id="resgata-foto-<?php echo esc_attr( (string) ( $resgata_index + 1 ) ); ?>" data-resgata-viewer-slide>
						<?php
						echo wp_get_attachment_image(
							$resgata_photo['id'],
							'large',
							false,
							array(
								'loading'       => 0 === $resgata_index ? 'eager' : 'lazy',
								'fetchpriority' => 0 === $resgata_index ? 'high' : 'auto',
								'sizes'         => '(min-width: 960px) 55vw, 100vw',
							)
						);
						?>
						<?php if ( 'before' === $resgata_photo['type'] ) : ?>
							<span class="resgata-viewer__caption"><?php esc_html_e( 'Antes do resgate', 'resgata-pet' ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<span class="resgata-badge resgata-badge--<?php echo esc_attr( $resgata_animal->status() ); ?> resgata-viewer__status">
				<?php echo wp_kses( Icons::svg( 'paw-print' ), Html::svg() ); ?>
				<?php echo esc_html( $resgata_animal->status_label() ); ?>
			</span>
			<?php if ( $resgata_total > 1 ) : ?>
				<?php // As setas só funcionam com o script: nascem escondidas e ele as mostra. ?>
				<button type="button" class="resgata-viewer__arrow resgata-viewer__arrow--prev" data-resgata-viewer-prev hidden>
					<?php echo wp_kses( Icons::svg( 'chevron-left' ), Html::svg() ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Foto anterior', 'resgata-pet' ); ?></span>
				</button>
				<button type="button" class="resgata-viewer__arrow resgata-viewer__arrow--next" data-resgata-viewer-next hidden>
					<?php echo wp_kses( Icons::svg( 'chevron-right' ), Html::svg() ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Próxima foto', 'resgata-pet' ); ?></span>
				</button>
			<?php endif; ?>
		</div>
		<?php if ( $resgata_total > 1 ) : ?>
			<ul class="resgata-viewer__thumbs">
				<?php foreach ( $resgata_photos as $resgata_index => $resgata_photo ) : ?>
					<li>
						<a
							class="resgata-viewer__thumb"
							href="#resgata-foto-<?php echo esc_attr( (string) ( $resgata_index + 1 ) ); ?>"
							data-resgata-viewer-thumb
							<?php echo 0 === $resgata_index ? 'aria-current="true"' : ''; ?>
						>
							<?php echo wp_get_attachment_image( $resgata_photo['id'], 'thumbnail', false, array( 'alt' => '' ) ); ?>
							<span class="screen-reader-text">
								<?php
								/* translators: 1: número da foto, 2: total de fotos. */
								echo esc_html( sprintf( __( 'Ver foto %1$d de %2$d', 'resgata-pet' ), $resgata_index + 1, $resgata_total ) );
								?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
	$resgata_parts['gallery'] = (string) ob_get_clean();
endif;

if ( $resgata_has( 'summary' ) ) :
	$resgata_temperaments = $resgata_animal->temperaments();
	ob_start();
	?>
	<section class="resgata-animal__section resgata-animal__summary">
		<?php if ( $resgata_args['show_title'] ) : ?>
			<h1 class="resgata-animal__name"><?php echo esc_html( $resgata_name ); ?></h1>
		<?php endif; ?>
		<?php if ( ! $resgata_has( 'gallery' ) || ! $resgata_args['photos'] ) : ?>
			<?php // Sem a foto ao lado, a situação aparece aqui. ?>
			<p class="resgata-animal__status">
				<span class="resgata-badge resgata-badge--<?php echo esc_attr( $resgata_animal->status() ); ?>"><?php echo esc_html( $resgata_animal->status_label() ); ?></span>
			</p>
		<?php endif; ?>
		<?php if ( '' !== $resgata_animal->tagline() ) : ?>
			<p class="resgata-animal__tagline"><?php echo esc_html( $resgata_animal->tagline() ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $resgata_animal->intro() ) : ?>
			<div class="resgata-animal__intro"><?php echo wp_kses_post( wpautop( esc_html( $resgata_animal->intro() ) ) ); ?></div>
		<?php endif; ?>

		<?php if ( $resgata_args['facts'] ) : ?>
			<dl class="resgata-facts">
				<?php foreach ( $resgata_args['facts'] as $resgata_fact ) : ?>
					<div class="resgata-facts__item">
						<dt><?php echo wp_kses( Icons::svg( $resgata_fact['icon'], 'resgata-facts__icon' ), Html::svg() ); ?><?php echo esc_html( $resgata_fact['label'] ); ?></dt>
						<dd><?php echo esc_html( $resgata_fact['value'] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>

		<?php if ( $resgata_temperaments ) : ?>
			<ul class="resgata-tags" aria-label="<?php esc_attr_e( 'Temperamento', 'resgata-pet' ); ?>">
				<?php foreach ( $resgata_temperaments as $resgata_temperament ) : ?>
					<li class="resgata-tag"><?php echo esc_html( $resgata_temperament ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( '' !== $resgata_args['adopt_url'] || '' !== $resgata_args['whatsapp_url'] ) : ?>
			<p class="resgata-animal__actions">
				<?php if ( '' !== $resgata_args['adopt_url'] ) : ?>
					<a class="resgata-button resgata-button--primary wp-element-button" href="<?php echo esc_url( $resgata_args['adopt_url'] ); ?>">
						<?php echo wp_kses( Icons::svg( 'paw-print' ), Html::svg() ); ?>
						<?php echo esc_html( $resgata_args['adopt_label'] ); ?>
					</a>
				<?php endif; ?>
				<?php if ( '' !== $resgata_args['whatsapp_url'] ) : ?>
					<a class="resgata-button resgata-button--outline" href="<?php echo esc_url( $resgata_args['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo wp_kses( Icons::whatsapp(), Html::svg() ); ?>
						<?php esc_html_e( 'Falar pelo WhatsApp', 'resgata-pet' ); ?>
					</a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</section>
	<?php
	$resgata_parts['summary'] = (string) ob_get_clean();
endif;

if ( $resgata_has( 'story' ) && '' !== $resgata_args['story'] ) :
	ob_start();
	?>
	<section class="resgata-animal__section resgata-panel resgata-animal__story">
		<h2 class="resgata-panel__title">
			<?php echo wp_kses( Icons::svg( 'file-text' ), Html::svg() ); ?>
			<?php echo esc_html( $resgata_args['story_title'] ); ?>
		</h2>
		<div class="resgata-animal__story-text">
			<?php echo wp_kses_post( $resgata_args['story'] ); ?>
		</div>
	</section>
	<?php
	$resgata_parts['story'] = (string) ob_get_clean();
endif;

if ( $resgata_has( 'health' ) && ( $resgata_args['health'] || $resgata_args['tests'] || $resgata_args['vaccines'] ) ) :
	ob_start();
	?>
	<section class="resgata-animal__section resgata-panel resgata-animal__health">
		<h2 class="resgata-panel__title">
			<?php echo wp_kses( Icons::svg( 'heart-pulse' ), Html::svg() ); ?>
			<?php esc_html_e( 'Saúde', 'resgata-pet' ); ?>
		</h2>
		<?php if ( $resgata_args['health'] ) : ?>
			<ul class="resgata-checklist">
				<?php foreach ( $resgata_args['health'] as $resgata_item ) : ?>
					<li class="resgata-checklist__item<?php echo $resgata_item['done'] ? '' : ' resgata-checklist__item--pending'; ?>">
						<?php echo wp_kses( Icons::svg( $resgata_item['done'] ? 'circle-check' : 'circle-dashed' ), Html::svg() ); ?>
						<?php echo esc_html( $resgata_item['label'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $resgata_args['tests'] ) : ?>
			<dl class="resgata-pairs">
				<?php foreach ( $resgata_args['tests'] as $resgata_label => $resgata_value ) : ?>
					<div class="resgata-pairs__item">
						<dt><?php echo esc_html( $resgata_label ); ?></dt>
						<dd><?php echo esc_html( $resgata_value ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
		<?php if ( $resgata_args['vaccines'] ) : ?>
			<h3 class="resgata-animal__subheading"><?php esc_html_e( 'Vacinas aplicadas', 'resgata-pet' ); ?></h3>
			<ul class="resgata-vaccine-list">
				<?php foreach ( $resgata_args['vaccines'] as $resgata_vaccine ) : ?>
					<li>
						<?php echo esc_html( $resgata_vaccine['name'] ); ?>
						<?php if ( '' !== $resgata_vaccine['date'] ) : ?>
							<span class="resgata-vaccine-list__date">(<?php echo esc_html( Animal::format_date( $resgata_vaccine['date'] ) ); ?>)</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
	$resgata_parts['health'] = (string) ob_get_clean();
endif;

if ( $resgata_has( 'help' ) && $resgata_args['help_links'] ) :
	ob_start();
	?>
	<section class="resgata-animal__section resgata-panel resgata-animal__help">
		<h2 class="resgata-panel__title">
			<?php echo wp_kses( Icons::svg( 'heart' ), Html::svg() ); ?>
			<?php esc_html_e( 'Não pode adotar?', 'resgata-pet' ); ?>
		</h2>
		<p class="resgata-animal__help-text"><?php esc_html_e( 'Você também pode ajudar de outras formas.', 'resgata-pet' ); ?></p>
		<p class="resgata-animal__help-actions">
			<?php foreach ( $resgata_args['help_links'] as $resgata_index => $resgata_link ) : ?>
				<a class="resgata-button <?php echo 0 === $resgata_index ? 'resgata-button--help wp-element-button' : 'resgata-button--help-outline'; ?>" href="<?php echo esc_url( $resgata_link['url'] ); ?>">
					<?php echo esc_html( $resgata_link['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</p>
	</section>
	<?php
	$resgata_parts['help'] = (string) ob_get_clean();
endif;

if ( $resgata_has( 'related' ) && $resgata_args['related'] ) :
	ob_start();
	?>
	<section class="resgata-animal__section resgata-panel resgata-related" aria-labelledby="resgata-outros-animais">
		<div class="resgata-related__header">
			<h2 class="resgata-panel__title" id="resgata-outros-animais">
				<?php echo wp_kses( Icons::svg( 'paw-print' ), Html::svg() ); ?>
				<?php esc_html_e( 'Outros animais para adoção', 'resgata-pet' ); ?>
			</h2>
			<?php if ( '' !== $resgata_args['archive_url'] ) : ?>
				<a class="resgata-related__all" href="<?php echo esc_url( $resgata_args['archive_url'] ); ?>">
					<?php esc_html_e( 'Ver todos', 'resgata-pet' ); ?>
					<?php echo wp_kses( Icons::svg( 'arrow-right' ), Html::svg() ); ?>
				</a>
			<?php endif; ?>
		</div>
		<div class="resgata-related__row" data-resgata-row>
		<ul class="resgata-related__list" data-resgata-row-track tabindex="0" aria-labelledby="resgata-outros-animais">
			<?php
			foreach ( $resgata_args['related'] as $resgata_related_id ) :
				$resgata_related = new Animal( $resgata_related_id );
				$resgata_meta    = array_filter( array( $resgata_related->species_label(), $resgata_related->age_label() ) );
				?>
				<li class="resgata-related__item">
					<a class="resgata-related__link" href="<?php echo esc_url( (string) get_permalink( $resgata_related_id ) ); ?>">
						<span class="resgata-related__media">
							<?php
							if ( has_post_thumbnail( $resgata_related_id ) ) {
								echo get_the_post_thumbnail( $resgata_related_id, 'medium', array( 'alt' => '' ) );
							}
							?>
						</span>
						<span class="resgata-related__name"><?php echo esc_html( get_the_title( $resgata_related_id ) ); ?></span>
						<?php if ( $resgata_meta ) : ?>
							<span class="resgata-related__meta"><?php echo esc_html( implode( ' • ', $resgata_meta ) ); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
			<?php // As setas só funcionam com o script: nascem escondidas e ele as mostra quando a fileira não cabe na tela. ?>
			<button type="button" class="resgata-viewer__arrow resgata-viewer__arrow--prev" data-resgata-row-prev hidden>
				<?php echo wp_kses( Icons::svg( 'chevron-left' ), Html::svg() ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Animais anteriores', 'resgata-pet' ); ?></span>
			</button>
			<button type="button" class="resgata-viewer__arrow resgata-viewer__arrow--next" data-resgata-row-next hidden>
				<?php echo wp_kses( Icons::svg( 'chevron-right' ), Html::svg() ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Próximos animais', 'resgata-pet' ); ?></span>
			</button>
		</div>
	</section>
	<?php
	$resgata_parts['related'] = (string) ob_get_clean();
endif;


// Sem nada para mostrar (ex.: seção "galeria" de um animal sem fotos), não imprime um bloco vazio.
if ( ! $resgata_parts ) {
	return;
}

$resgata_group = static function ( array $resgata_keys ) use ( $resgata_parts ): string {
	return implode( '', array_intersect_key( $resgata_parts, array_flip( $resgata_keys ) ) );
};

$resgata_top   = $resgata_group( array( 'gallery', 'summary' ) );
$resgata_cards = $resgata_group( array( 'story', 'health', 'help' ) );

?>
<div <?php echo wp_kses_data( $resgata_args['wrapper_attributes'] ); ?>>
	<?php
	echo wp_kses( $resgata_parts['breadcrumb'] ?? '', Html::allowed() );

	if ( $resgata_args['is_page'] ) {
		// Página inteira: fotos ao lado do resumo e, abaixo, os cartões lado a lado.
		if ( '' !== $resgata_top ) {
			echo '<div class="resgata-animal__top">' . wp_kses( $resgata_top, Html::allowed() ) . '</div>';
		}
		if ( '' !== $resgata_cards ) {
			echo '<div class="resgata-animal__cards">' . wp_kses( $resgata_cards, Html::allowed() ) . '</div>';
		}
	} else {
		echo wp_kses( $resgata_top . $resgata_cards, Html::allowed() );
	}

	echo wp_kses( $resgata_parts['related'] ?? '', Html::allowed() );

	/**
	 * Disparado no fim da página do animal.
	 *
	 * @param ResgataPet\Modules\Animals\Animal $resgata_animal Animal exibido.
	 * @param string[]                         $sections   Seções exibidas.
	 */
	do_action( 'resgata_animal_details_after', $resgata_animal, $resgata_args['sections'] );
	?>
</div>
<?php
