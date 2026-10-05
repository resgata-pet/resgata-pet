<?php
/**
 * Caixas de cadastro do animal no painel.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\Admin;

use ResgataWP\Modules\Animals\Animal;
use ResgataWP\Modules\Animals\Meta;
use ResgataWP\Modules\Animals\Options;
use ResgataWP\Modules\Animals\PostType;
use ResgataWP\Modules\Animals\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Ficha, saúde e galeria de fotos na tela de edição do animal.
 */
final class MetaBoxes {

	private const NONCE_ACTION = 'rwp_save_animal';
	private const NONCE_NAME   = 'rwp_animal_nonce';

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . PostType::SLUG, array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . PostType::SLUG, array( $this, 'save' ) );
		add_action( 'edit_form_after_title', array( $this, 'render_story_label' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Adiciona as caixas.
	 */
	public function add_meta_boxes(): void {
		// A escolha de "modelo" do tema só confunde quem está cadastrando um animal.
		remove_meta_box( 'pageparentdiv', PostType::SLUG, 'side' );

		add_meta_box( 'rwp-animal-profile', __( 'Ficha do animal', 'resgata-wp' ), array( $this, 'render_profile' ), PostType::SLUG, 'normal', 'high' );
		add_meta_box( 'rwp-animal-health', __( 'Saúde', 'resgata-wp' ), array( $this, 'render_health' ), PostType::SLUG, 'normal', 'high' );
		add_meta_box( 'rwp-animal-gallery', __( 'Galeria de fotos (antes e depois)', 'resgata-wp' ), array( $this, 'render_gallery' ), PostType::SLUG, 'normal', 'high' );
	}

	/**
	 * Título acima do editor de texto, explicando para que ele serve.
	 *
	 * @param \WP_Post $post Post em edição.
	 */
	public function render_story_label( \WP_Post $post ): void {
		if ( PostType::SLUG !== $post->post_type ) {
			return;
		}

		printf(
			'<div class="rwp-story-label"><h2>%s</h2><p class="description">%s</p></div>',
			esc_html__( 'História do resgate', 'resgata-wp' ),
			esc_html__( 'Conte onde o animal foi encontrado, como estava e como foi a recuperação. Esse texto aparece na página do animal.', 'resgata-wp' )
		);
	}

	/**
	 * Carrega CSS e JS somente na tela de edição do animal.
	 *
	 * @param string $hook Tela atual.
	 */
	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || PostType::SLUG !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'rwp-admin-animal', RWP_PLUGIN_URL . 'assets/css/admin-animal.css', array(), RWP_VERSION );
		wp_enqueue_script( 'rwp-admin-animal', RWP_PLUGIN_URL . 'assets/js/admin-animal.js', array( 'jquery' ), RWP_VERSION, true );
		wp_localize_script(
			'rwp-admin-animal',
			'rwpAdminAnimal',
			array(
				'mediaTitle'  => __( 'Escolher fotos do animal', 'resgata-wp' ),
				'mediaButton' => __( 'Adicionar à galeria', 'resgata-wp' ),
			)
		);
	}

	/**
	 * Campo de escolha (select).
	 *
	 * @param string                $name     Nome do campo.
	 * @param array<string, string> $choices  Opções (valor => rótulo).
	 * @param string                $selected Valor selecionado.
	 * @param string                $blank    Rótulo da opção vazia (vazio = sem opção vazia).
	 */
	private function select( string $name, array $choices, string $selected, string $blank = '' ): void {
		printf( '<select id="%1$s" name="%1$s">', esc_attr( $name ) );
		if ( '' !== $blank ) {
			printf( '<option value="">%s</option>', esc_html( $blank ) );
		}
		foreach ( $choices as $value => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $selected, (string) $value, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Caixa "Ficha do animal".
	 *
	 * @param \WP_Post $post Animal em edição.
	 */
	public function render_profile( \WP_Post $post ): void {
		$animal  = new Animal( $post->ID );
		$species = $animal->species();
		$age     = $animal->age();

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="rwp-fields">
			<p class="rwp-field">
				<label for="rwp_status"><?php esc_html_e( 'Situação', 'resgata-wp' ); ?></label>
				<?php $this->select( 'rwp_status', Options::statuses(), $animal->status() ); ?>
				<span class="description"><?php esc_html_e( 'Só os animais "Disponível para adoção" e "Reservado" aparecem na lista pública de adoção.', 'resgata-wp' ); ?></span>
			</p>
			<p class="rwp-field">
				<label for="rwp_species"><?php esc_html_e( 'Espécie', 'resgata-wp' ); ?></label>
				<?php $this->select( 'rwp_species', Taxonomies::species_options(), $species ? $species->slug : '', __( '— Escolha —', 'resgata-wp' ) ); ?>
			</p>
			<p class="rwp-field">
				<label for="rwp_sex"><?php esc_html_e( 'Sexo', 'resgata-wp' ); ?></label>
				<?php $this->select( 'rwp_sex', Options::sexes(), $animal->sex(), __( 'Não informado', 'resgata-wp' ) ); ?>
			</p>
			<p class="rwp-field">
				<label for="rwp_size"><?php esc_html_e( 'Porte', 'resgata-wp' ); ?></label>
				<?php $this->select( 'rwp_size', Options::sizes(), $animal->size(), __( 'Não informado', 'resgata-wp' ) ); ?>
			</p>
			<p class="rwp-field">
				<label for="rwp_age_years"><?php esc_html_e( 'Idade estimada', 'resgata-wp' ); ?></label>
				<span class="rwp-inline">
					<input type="number" id="rwp_age_years" name="rwp_age_years" min="0" max="40" step="1" class="small-text" value="<?php echo esc_attr( $age ? (string) $age['years'] : '' ); ?>">
					<?php esc_html_e( 'anos e', 'resgata-wp' ); ?>
					<input type="number" id="rwp_age_months" name="rwp_age_months" min="0" max="11" step="1" class="small-text" aria-label="<?php esc_attr_e( 'Meses', 'resgata-wp' ); ?>" value="<?php echo esc_attr( $age ? (string) $age['months'] : '' ); ?>">
					<?php esc_html_e( 'meses', 'resgata-wp' ); ?>
				</span>
				<span class="description"><?php esc_html_e( 'Informe a idade de hoje. O site atualiza a idade sozinho com o passar do tempo.', 'resgata-wp' ); ?></span>
			</p>
			<p class="rwp-field">
				<label for="rwp_color"><?php esc_html_e( 'Cor / pelagem', 'resgata-wp' ); ?></label>
				<input type="text" id="rwp_color" name="rwp_color" class="regular-text" value="<?php echo esc_attr( $animal->color() ); ?>" placeholder="<?php esc_attr_e( 'Ex.: tigrado, preto e branco', 'resgata-wp' ); ?>">
			</p>
			<p class="rwp-field">
				<label for="rwp_rescue_date"><?php esc_html_e( 'Data do resgate', 'resgata-wp' ); ?></label>
				<input type="date" id="rwp_rescue_date" name="rwp_rescue_date" value="<?php echo esc_attr( $animal->rescue_date() ); ?>" max="<?php echo esc_attr( current_datetime()->format( 'Y-m-d' ) ); ?>">
			</p>
		</div>
		<p class="description"><?php esc_html_e( 'A foto principal e o temperamento ficam na coluna da direita.', 'resgata-wp' ); ?></p>
		<?php
	}

	/**
	 * Caixa "Saúde".
	 *
	 * @param \WP_Post $post Animal em edição.
	 */
	public function render_health( \WP_Post $post ): void {
		$animal   = new Animal( $post->ID );
		$vaccines = $animal->vaccines();
		?>
		<div class="rwp-fields">
			<p class="rwp-field">
				<label for="rwp_neutered"><?php esc_html_e( 'Castrado(a)', 'resgata-wp' ); ?></label>
				<?php $this->select( 'rwp_neutered', Options::yes_no(), $animal->neutered(), __( 'Não informado', 'resgata-wp' ) ); ?>
			</p>
			<p class="rwp-field">
				<label for="rwp_dewormed_date"><?php esc_html_e( 'Último vermífugo', 'resgata-wp' ); ?></label>
				<input type="date" id="rwp_dewormed_date" name="rwp_dewormed_date" value="<?php echo esc_attr( $animal->dewormed_date() ); ?>">
			</p>
			<p class="rwp-field">
				<label for="rwp_fiv"><?php esc_html_e( 'Teste de FIV (gatos)', 'resgata-wp' ); ?></label>
				<?php $this->select( 'rwp_fiv', Options::test_results(), $animal->fiv(), __( 'Não testado', 'resgata-wp' ) ); ?>
			</p>
			<p class="rwp-field">
				<label for="rwp_felv"><?php esc_html_e( 'Teste de FeLV (gatos)', 'resgata-wp' ); ?></label>
				<?php $this->select( 'rwp_felv', Options::test_results(), $animal->felv(), __( 'Não testado', 'resgata-wp' ) ); ?>
			</p>
		</div>

		<h3 class="rwp-subtitle"><?php esc_html_e( 'Vacinas', 'resgata-wp' ); ?></h3>
		<div class="rwp-vaccines" data-next-index="<?php echo esc_attr( (string) count( $vaccines ) ); ?>">
			<div class="rwp-vaccines__rows">
				<?php
				foreach ( $vaccines as $index => $vaccine ) {
					$this->render_vaccine_row( (string) $index, $vaccine['name'], $vaccine['date'] );
				}
				?>
			</div>
			<template class="rwp-vaccines__template"><?php $this->render_vaccine_row( '__i__', '', '' ); ?></template>
			<button type="button" class="button rwp-vaccines__add"><?php esc_html_e( 'Adicionar vacina', 'resgata-wp' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Linha de vacina (nome + data).
	 *
	 * @param string $index Índice da linha.
	 * @param string $name  Nome da vacina.
	 * @param string $date  Data de aplicação.
	 */
	private function render_vaccine_row( string $index, string $name, string $date ): void {
		?>
		<div class="rwp-vaccines__row">
			<input type="text" name="rwp_vaccines[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Ex.: V4, antirrábica', 'resgata-wp' ); ?>" aria-label="<?php esc_attr_e( 'Nome da vacina', 'resgata-wp' ); ?>">
			<input type="date" name="rwp_vaccines[<?php echo esc_attr( $index ); ?>][date]" value="<?php echo esc_attr( $date ); ?>" aria-label="<?php esc_attr_e( 'Data da aplicação', 'resgata-wp' ); ?>">
			<button type="button" class="button-link button-link-delete rwp-remove-row"><?php esc_html_e( 'Remover', 'resgata-wp' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Caixa "Galeria de fotos".
	 *
	 * @param \WP_Post $post Animal em edição.
	 */
	public function render_gallery( \WP_Post $post ): void {
		$gallery = ( new Animal( $post->ID ) )->gallery();
		?>
		<p class="description"><?php esc_html_e( 'Mostre a transformação: marque cada foto como "Antes" (quando foi resgatado) ou "Depois" (recuperado).', 'resgata-wp' ); ?></p>
		<div class="rwp-gallery" data-next-index="<?php echo esc_attr( (string) count( $gallery ) ); ?>">
			<ul class="rwp-gallery__items">
				<?php
				foreach ( $gallery as $index => $photo ) {
					$this->render_gallery_item( (string) $index, $photo['id'], $photo['type'], (string) wp_get_attachment_image_url( $photo['id'], 'thumbnail' ) );
				}
				?>
			</ul>
			<template class="rwp-gallery__template"><?php $this->render_gallery_item( '__i__', 0, 'after', '' ); ?></template>
			<button type="button" class="button rwp-gallery__add"><?php esc_html_e( 'Adicionar fotos', 'resgata-wp' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Item da galeria (miniatura + tipo).
	 *
	 * @param string $index Índice do item.
	 * @param int    $id    ID do anexo.
	 * @param string $type  Tipo da foto (`before` ou `after`).
	 * @param string $src   URL da miniatura.
	 */
	private function render_gallery_item( string $index, int $id, string $type, string $src ): void {
		?>
		<li class="rwp-gallery__item">
			<img src="<?php echo esc_url( $src ); ?>" alt="">
			<input type="hidden" class="rwp-gallery__id" name="rwp_gallery[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( (string) $id ); ?>">
			<select name="rwp_gallery[<?php echo esc_attr( $index ); ?>][type]" aria-label="<?php esc_attr_e( 'Tipo da foto', 'resgata-wp' ); ?>">
				<?php foreach ( Options::photo_types() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button-link button-link-delete rwp-remove-row"><?php esc_html_e( 'Remover', 'resgata-wp' ); ?></button>
		</li>
		<?php
	}

	/**
	 * Salva os dados do animal.
	 *
	 * @param int $post_id ID do animal.
	 */
	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Campos de texto/escolha: a validação de cada um está em Meta (sanitize_callback).
		$fields = array(
			'rwp_status'        => Meta::STATUS,
			'rwp_sex'           => Meta::SEX,
			'rwp_size'          => Meta::SIZE,
			'rwp_color'         => Meta::COLOR,
			'rwp_rescue_date'   => Meta::RESCUE_DATE,
			'rwp_dewormed_date' => Meta::DEWORMED_DATE,
			'rwp_neutered'      => Meta::NEUTERED,
			'rwp_fiv'           => Meta::FIV,
			'rwp_felv'          => Meta::FELV,
		);

		foreach ( $fields as $field => $meta_key ) {
			$value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
			update_post_meta( $post_id, $meta_key, wp_slash( $value ) );
		}

		// Idade estimada → data de nascimento estimada.
		$years  = isset( $_POST['rwp_age_years'] ) ? sanitize_text_field( wp_unslash( $_POST['rwp_age_years'] ) ) : '';
		$months = isset( $_POST['rwp_age_months'] ) ? sanitize_text_field( wp_unslash( $_POST['rwp_age_months'] ) ) : '';
		$this->save_age( $post_id, $years, $months );

		// Espécie.
		$species = isset( $_POST['rwp_species'] ) ? sanitize_key( wp_unslash( $_POST['rwp_species'] ) ) : '';
		$term    = '' !== $species ? get_term_by( 'slug', $species, Taxonomies::SPECIES ) : false;
		wp_set_object_terms( $post_id, $term ? array( (int) $term->term_id ) : array(), Taxonomies::SPECIES );

		// Vacinas e galeria: cada item é validado em Meta::sanitize_vaccines / sanitize_gallery.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$vaccines = isset( $_POST['rwp_vaccines'] ) && is_array( $_POST['rwp_vaccines'] ) ? Meta::sanitize_vaccines( wp_unslash( $_POST['rwp_vaccines'] ) ) : array();
		$gallery  = isset( $_POST['rwp_gallery'] ) && is_array( $_POST['rwp_gallery'] ) ? Meta::sanitize_gallery( wp_unslash( $_POST['rwp_gallery'] ) ) : array();
		// phpcs:enable

		update_post_meta( $post_id, Meta::VACCINES, wp_slash( $vaccines ) );
		update_post_meta( $post_id, Meta::GALLERY, $gallery );
	}

	/**
	 * Converte a idade informada em data de nascimento estimada.
	 *
	 * A data só muda quando a idade informada é diferente da calculada hoje,
	 * para não "rejuvenescer" o animal a cada vez que o cadastro é salvo.
	 *
	 * @param int    $post_id ID do animal.
	 * @param string $years   Anos informados (pode estar vazio).
	 * @param string $months  Meses informados (pode estar vazio).
	 */
	private function save_age( int $post_id, string $years, string $months ): void {
		if ( '' === $years && '' === $months ) {
			delete_post_meta( $post_id, Meta::BIRTH_DATE );
			return;
		}

		$years  = min( 40, absint( $years ) );
		$months = min( 11, absint( $months ) );

		$current = ( new Animal( $post_id ) )->age();
		if ( $current && $current['years'] === $years && $current['months'] === $months ) {
			return;
		}

		update_post_meta( $post_id, Meta::BIRTH_DATE, Animal::birth_date_from_age( $years, $months ) );
	}
}
