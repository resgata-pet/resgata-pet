<?php
/**
 * Blocos públicos do apadrinhamento.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

use ResgataWP\Core\TemplateLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Registra e renderiza os blocos `resgata-wp/sponsorship-plans` (planos e
 * contador de padrinhos) e `resgata-wp/sponsor-area` (área do padrinho).
 */
final class Blocks {

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		// Depois dos blocos de animais (20), que registram os handles de estilo e script, e antes de o
		// WordPress acrescentar os atributos de suporte (alinhamento, espaçamento), na prioridade 22.
		add_action( 'init', array( $this, 'register_blocks' ), 21 );
		add_filter( 'rwp_impact_items', array( $this, 'impact_item' ) );
	}

	/**
	 * Acrescenta os padrinhos ativos à faixa "Números da protetora".
	 *
	 * @param mixed $items Números já listados.
	 * @return array<int, mixed>
	 */
	public function impact_item( $items ): array {
		$items = is_array( $items ) ? $items : array();
		$total = Plans::counter()['total'];

		$items[] = array(
			'icon'  => 'hand-heart',
			'value' => $total,
			'label' => _n( 'Padrinho ativo', 'Padrinhos ativos', $total, 'resgata-wp' ),
			'tone'  => 'purple',
		);

		return $items;
	}

	/**
	 * Registra os blocos.
	 */
	public function register_blocks(): void {
		if ( ! wp_style_is( 'rwp-frontend', 'registered' ) ) {
			$src = current_theme_supports( 'resgata-wp' ) ? false : RWP_PLUGIN_URL . 'assets/css/frontend.css';
			wp_register_style( 'rwp-frontend', $src, array(), RWP_VERSION );
		}

		register_block_type( RWP_PLUGIN_DIR . 'blocks/sponsorship-plans' );
		register_block_type( RWP_PLUGIN_DIR . 'blocks/sponsor-area' );
	}

	/**
	 * HTML do bloco de planos.
	 *
	 * @param bool   $show_counter       Se mostra o contador de padrinhos.
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 * @return string HTML, ou string vazia se o site ainda não pode vender planos.
	 */
	public static function render_plans( bool $show_counter = true, string $wrapper_attributes = '' ): string {
		if ( ! Checkout::is_ready() ) {
			return '';
		}

		wp_enqueue_style( 'rwp-frontend' );

		$plans = array();
		foreach ( Plans::for_sale() as $plan ) {
			$plans[] = array(
				'price_id'    => $plan['price_id'],
				'amount'      => Plans::format_amount( $plan['amount'] ),
				'name'        => $plan['name'],
				'description' => $plan['description'],
				'benefits'    => $plan['benefits'],
				'featured'    => $plan['featured'],
			);
		}

		$counter = Plans::counter();

		return TemplateLoader::render(
			'sponsorship-plans.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'id="rwp-apadrinhar" class="rwp-sponsorship"',
				'plans'              => $plans,
				'show_counter'       => $show_counter,
				'counter_total'      => $counter['total'],
				'counter_updated'    => $counter['updated_at'] ? wp_date( get_option( 'date_format' ), $counter['updated_at'] ) : '',
				'thank_you'          => Checkout::is_thank_you(),
				'error'              => Checkout::error(),
			)
		);
	}

	/**
	 * HTML do bloco da área do padrinho.
	 *
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render_area( string $wrapper_attributes = '' ): string {
		if ( '' === Keys::secret_key() ) {
			return '';
		}

		wp_enqueue_style( 'rwp-frontend' );

		$error = MagicLink::error();

		return TemplateLoader::render(
			'sponsor-area.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'id="rwp-padrinho" class="rwp-sponsor-area"',
				// Com erro (link vencido, por exemplo) a pessoa volta ao pedido de um link novo.
				'token'              => '' === $error ? MagicLink::token_from_url() : '',
				'sent'               => MagicLink::was_sent(),
				'error'              => $error,
			)
		);
	}
}
