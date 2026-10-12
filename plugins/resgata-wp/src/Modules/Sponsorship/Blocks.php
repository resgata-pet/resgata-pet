<?php
/**
 * Blocos públicos do apadrinhamento.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

use ResgataWP\Core\Links;
use ResgataWP\Core\Schema;
use ResgataWP\Core\TemplateLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Registra e renderiza os blocos `resgata-wp/sponsorship-plans` (planos e
 * contador de padrinhos), `resgata-wp/sponsor-area` (área do padrinho) e
 * `resgata-wp/sponsorship-thanks` (agradecimento depois do pagamento).
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
		register_block_type( RWP_PLUGIN_DIR . 'blocks/sponsorship-thanks' );
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
	 * HTML do bloco de agradecimento.
	 *
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render_thanks( string $wrapper_attributes = '' ): string {
		wp_enqueue_style( 'rwp-frontend' );

		return TemplateLoader::render(
			'sponsorship-thanks.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'class="rwp-sponsorship-thanks"',
				'image_url'          => ThanksPage::image_url(),
				'title'              => ThanksPage::title(),
				'text'               => ThanksPage::text(),
				'area_url'           => Links::sponsor_area(),
				'animals_url'        => Links::animals(),
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

		$error   = MagicLink::error();
		$sponsor = MagicLink::sponsor();

		return TemplateLoader::render(
			'sponsor-area.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'id="rwp-padrinho" class="rwp-sponsor-area"',
				// Com erro (link vencido, por exemplo) a pessoa volta ao pedido de um link novo.
				'token'              => '' === $error ? MagicLink::token_from_url() : '',
				'sent'               => MagicLink::was_sent(),
				'error'              => $error,
				'summary'            => $sponsor ? self::summary( $sponsor ) : null,
			)
		);
	}

	/**
	 * Resumo do apadrinhamento, pronto para o template (textos ainda sem escape).
	 *
	 * @param array<string, mixed> $sponsor Padrinho identificado pelo link.
	 * @return array<string, mixed>
	 */
	private static function summary( array $sponsor ): array {
		$format   = (string) get_option( 'date_format' );
		$date     = static fn( $utc ): int => $utc ? (int) strtotime( $utc . ' UTC' ) : 0;
		$statuses = Sponsors::statuses();
		$totals   = Payments::totals( (int) $sponsor['id'] );
		$canceled = 'canceled' === $sponsor['status'];
		$ends_at  = $canceled ? 0 : $date( $sponsor['cancel_at'] );
		$next_at  = Sponsors::STATUS_ACTIVE === $sponsor['status'] && ! $ends_at ? $date( $sponsor['period_end'] ) : 0;
		// Quem já era padrinho antes de o site registrar os pagamentos vê desde quando o total conta.
		$since = (int) get_option( Schema::PAYMENTS_SINCE, 0 );
		$since = $since && $date( $sponsor['created_at'] ) < $since ? $since : 0;

		return array(
			'first_name'     => (string) strtok( (string) $sponsor['name'], ' ' ),
			'plan'           => Notifications::plan( $sponsor ),
			'status'         => $statuses[ $sponsor['status'] ] ?? '',
			'active'         => Sponsors::STATUS_ACTIVE === $sponsor['status'],
			'payment_failed' => ! $canceled && (bool) $sponsor['payment_failed'],
			'total'          => $totals['count'] ? Plans::format_amount( $totals['total'] ) : '',
			'payments'       => $totals['count'],
			'total_since'    => $since ? wp_date( $format, $since ) : '',
			'since'          => wp_date( $format, $date( $sponsor['created_at'] ) ),
			'last_paid'      => $date( $sponsor['last_paid_at'] ) ? wp_date( $format, $date( $sponsor['last_paid_at'] ) ) : '',
			'next_charge'    => $next_at > time() ? wp_date( $format, $next_at ) : '',
			'ends_at'        => $ends_at ? wp_date( $format, $ends_at ) : '',
			// Código novo, de uso único, para o botão que abre o portal da Stripe.
			'token'          => MagicLink::create_token( (int) $sponsor['id'] ),
		);
	}
}
