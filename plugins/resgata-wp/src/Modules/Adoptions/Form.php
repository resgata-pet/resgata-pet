<?php
/**
 * Formulário público de adoção.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Adoptions;

use ResgataWP\Core\TemplateLoader;
use ResgataWP\Modules\Animals\Meta;
use ResgataWP\Modules\Animals\PostType as AnimalPostType;
use ResgataWP\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Bloco `resgata-wp/adoption-form`: o questionário de adoção, com o consentimento (LGPD).
 */
final class Form {

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		// Depois dos blocos de animais (prioridade 20), que registram os handles de estilo e script.
		add_action( 'init', array( $this, 'register_block' ), 25 );
	}

	/**
	 * Registra o bloco.
	 */
	public function register_block(): void {
		register_block_type( RWP_PLUGIN_DIR . 'blocks/adoption-form' );
	}

	/**
	 * Texto de consentimento mostrado no formulário (e guardado junto com o pedido).
	 */
	public static function consent_text(): string {
		$organization = Organization::get();
		$name         = '' !== $organization['name'] ? $organization['name'] : get_bloginfo( 'name' );

		if ( '' !== $organization['privacy_text'] ) {
			return $organization['privacy_text'];
		}

		return sprintf(
			/* translators: %s: nome da protetora. */
			__( 'Autorizo %s a usar os dados deste formulário somente para avaliar o meu pedido de adoção e entrar em contato comigo. Posso pedir a exclusão dos meus dados a qualquer momento.', 'resgata-wp' ),
			$name
		);
	}

	/**
	 * Animais disponíveis para adoção (ID => nome), para o formulário fora da página do animal.
	 *
	 * @return array<int, string>
	 */
	private static function adoptable_animals(): array {
		$posts = get_posts(
			array(
				'post_type'      => AnimalPostType::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Volume pequeno (animais de uma protetora).
				'meta_key'       => Meta::STATUS,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'     => 'available',
			)
		);

		return wp_list_pluck( $posts, 'post_title', 'ID' );
	}

	/**
	 * HTML do formulário (ou da confirmação, logo depois do envio).
	 *
	 * @param int    $context_post_id    Post em que o bloco está (a página do animal, normalmente).
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render( int $context_post_id, string $wrapper_attributes = '' ): string {
		$wrapper = '' !== $wrapper_attributes ? $wrapper_attributes : 'id="rwp-adocao" class="rwp-adoption"';

		wp_enqueue_style( 'rwp-frontend' );

		$confirmed = Submission::confirmed_request_id();
		if ( $confirmed ) {
			$animal_id = (int) get_post_meta( $confirmed, PostType::META_ANIMAL, true );

			return TemplateLoader::render(
				'adoption-success.php',
				array(
					'wrapper_attributes' => $wrapper,
					'animal_name'        => $animal_id ? get_the_title( $animal_id ) : '',
					'whatsapp_url'       => Messages::whatsapp_url( $confirmed ),
					'request_id'         => $confirmed,
				)
			);
		}

		$on_animal_page = AnimalPostType::SLUG === get_post_type( $context_post_id );
		$animals        = array();

		if ( $on_animal_page ) {
			// Na página de um animal que não está disponível, o formulário não aparece.
			if ( ! Submission::is_adoptable( $context_post_id ) ) {
				return '';
			}
			$animal_id = $context_post_id;
		} else {
			$animals = self::adoptable_animals();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Só pré-seleciona um animal da lista pública.
			$animal_id = isset( $_GET['rwp_animal'] ) ? absint( wp_unslash( $_GET['rwp_animal'] ) ) : 0;
			$animal_id = isset( $animals[ $animal_id ] ) ? $animal_id : 0;
		}

		$old = Submission::old();
		if ( isset( $old['animal_id'] ) && ! $on_animal_page ) {
			$animal_id = isset( $animals[ absint( $old['animal_id'] ) ] ) ? absint( $old['animal_id'] ) : $animal_id;
		}

		return TemplateLoader::render(
			'adoption-form.php',
			array(
				'wrapper_attributes' => $wrapper,
				'fields'             => Questionnaire::fields(),
				'animal_id'          => $animal_id,
				'animal_name'        => $animal_id ? get_the_title( $animal_id ) : '',
				'animals'            => $animals,
				'show_animal_select' => ! $on_animal_page,
				'consent_text'       => self::consent_text(),
				'errors'             => Submission::errors(),
				'old'                => $old,
				'adoption_rules'     => Organization::get()['adoption_rules'],
				'adoption_fee'       => Organization::get()['adoption_fee'],
			)
		);
	}
}
