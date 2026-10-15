<?php
/**
 * Planos de apadrinhamento e contador de padrinhos.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Sponsorship;

defined( 'ABSPATH' ) || exit;

/**
 * Guarda os IDs dos preços criados na Stripe e o contador público de padrinhos.
 *
 * Valores em centavos, como na Stripe (R$ 15,00 = 1500).
 */
final class Plans {

	/**
	 * Option com os preços, a configuração do portal e o ajuste manual do contador.
	 */
	public const OPTION = 'resgata_sponsorship';

	/**
	 * Option com o número de padrinhos ativos e a data da última mudança.
	 */
	public const COUNTER_OPTION = 'resgata_sponsorship_counter';

	public const ADJUSTMENT_MIN = -9999;
	public const ADJUSTMENT_MAX = 9999;

	/**
	 * Valores mensais oferecidos por padrão, em centavos.
	 *
	 * @return int[]
	 */
	public static function default_amounts(): array {
		/**
		 * Filtra os valores mensais criados por `wp resgata stripe-setup`.
		 *
		 * @param int[] $amounts Valores em centavos.
		 */
		$amounts = (array) apply_filters( 'resgata_sponsorship_plan_amounts', array( 1500, 3000, 5000, 7500, 10000 ) );

		return array_values( array_unique( array_filter( array_map( 'absint', $amounts ) ) ) );
	}

	/**
	 * Limites dos textos de um plano.
	 */
	public const NAME_MAX        = 40;
	public const DESCRIPTION_MAX = 120;
	public const BENEFIT_MAX     = 80;
	public const BENEFITS_MAX    = 6;

	/**
	 * Valor mensal aceito, em centavos (a Stripe não cobra menos que R$ 0,50; o teto evita erro de digitação).
	 */
	public const AMOUNT_MIN = 100;
	public const AMOUNT_MAX = 500000;

	/**
	 * Quantidade máxima de planos cadastrados.
	 */
	public const MAX_PLANS = 12;

	/**
	 * Dados gravados do módulo.
	 *
	 * Cada plano é um produto da Stripe (o nome do plano) ligado ao preço mensal
	 * em vigor. Quando o valor muda, o preço antigo vai para `old_prices`: quem
	 * já assina continua nele e o site ainda sabe de que plano se trata.
	 *
	 * @return array{plans: array<int, array<string, mixed>>, portal_configuration: string, adjustment: int}
	 */
	public static function settings(): array {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		// Até a versão 0.5 os planos ficavam em `prices`, só com valor e IDs.
		$source = isset( $saved['plans'] ) ? $saved['plans'] : ( $saved['prices'] ?? array() );
		$plans  = array();

		foreach ( (array) $source as $plan ) {
			$plan = is_array( $plan ) ? self::sanitize_plan( $plan ) : null;

			if ( $plan ) {
				$plans[ $plan['id'] ] = $plan;
			}
		}

		return array(
			'plans'                => array_values( $plans ),
			'portal_configuration' => sanitize_text_field( (string) ( $saved['portal_configuration'] ?? '' ) ),
			'adjustment'           => self::clamp_adjustment( (int) ( $saved['adjustment'] ?? 0 ) ),
		);
	}

	/**
	 * Valida os dados de um plano. Devolve null se faltar o preço ou o valor.
	 *
	 * @param array<string, mixed> $plan Plano recebido.
	 * @return array{id: string, product_id: string, price_id: string, amount: int, name: string, description: string, benefits: string[], featured: bool, active: bool, old_prices: array<int, array{price_id: string, amount: int}>}|null
	 */
	public static function sanitize_plan( array $plan ): ?array {
		$price_id   = self::sanitize_id( $plan['price_id'] ?? '' );
		$product_id = self::sanitize_id( $plan['product_id'] ?? '' );
		$amount     = absint( $plan['amount'] ?? 0 );

		if ( '' === $price_id || ! $amount ) {
			return null;
		}

		$old_prices = array();
		foreach ( (array) ( $plan['old_prices'] ?? array() ) as $old ) {
			$old_id = is_array( $old ) ? self::sanitize_id( $old['price_id'] ?? '' ) : '';

			if ( '' !== $old_id && $old_id !== $price_id ) {
				$old_prices[ $old_id ] = array(
					'price_id' => $old_id,
					'amount'   => absint( $old['amount'] ?? 0 ),
				);
			}
		}

		return array(
			// O produto identifica o plano; planos antigos, sem produto gravado, usam o preço.
			'id'          => '' !== $product_id ? $product_id : $price_id,
			'product_id'  => $product_id,
			'price_id'    => $price_id,
			'amount'      => $amount,
			'name'        => self::sanitize_text( $plan['name'] ?? '', self::NAME_MAX ),
			'description' => self::sanitize_text( $plan['description'] ?? '', self::DESCRIPTION_MAX ),
			'benefits'    => self::sanitize_benefits( $plan['benefits'] ?? array() ),
			'featured'    => ! empty( $plan['featured'] ),
			'active'      => ! isset( $plan['active'] ) || ! empty( $plan['active'] ),
			'old_prices'  => array_values( $old_prices ),
		);
	}

	/**
	 * ID da Stripe (`prod_...`, `price_...`): só letras, números e sublinhado.
	 *
	 * @param mixed $value Valor recebido.
	 */
	public static function sanitize_id( $value ): string {
		return is_scalar( $value ) ? (string) preg_replace( '/[^A-Za-z0-9_]/', '', (string) $value ) : '';
	}

	/**
	 * Texto de uma linha, sem HTML, cortado no tamanho máximo.
	 *
	 * @param mixed $value Valor recebido.
	 * @param int   $max   Tamanho máximo.
	 */
	public static function sanitize_text( $value, int $max ): string {
		return mb_substr( is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '', 0, $max );
	}

	/**
	 * Lista de benefícios: linhas de texto, sem vazias, até o limite.
	 *
	 * @param mixed $value Valor recebido.
	 * @return string[]
	 */
	public static function sanitize_benefits( $value ): array {
		$benefits = array();

		foreach ( is_array( $value ) ? $value : array() as $benefit ) {
			$benefit = self::sanitize_text( $benefit, self::BENEFIT_MAX );

			if ( '' !== $benefit && count( $benefits ) < self::BENEFITS_MAX ) {
				$benefits[] = $benefit;
			}
		}

		return $benefits;
	}

	/**
	 * Grava parte dos dados do módulo.
	 *
	 * @param array<string, mixed> $changes Campos a alterar.
	 */
	public static function update( array $changes ): void {
		$data = array_merge( self::settings(), $changes );

		// O formato antigo não é mais gravado.
		unset( $data['prices'] );

		update_option( self::OPTION, $data, false );
	}

	/**
	 * Todos os planos cadastrados (inclusive os escondidos do site), do menor para o maior valor.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		$plans = self::settings()['plans'];

		usort( $plans, static fn( array $a, array $b ) => $a['amount'] <=> $b['amount'] );

		return $plans;
	}

	/**
	 * Planos à venda no site.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_sale(): array {
		return array_values( array_filter( self::all(), static fn( array $plan ): bool => $plan['active'] ) );
	}

	/**
	 * Plano pelo seu identificador.
	 *
	 * @param string $id Identificador do plano.
	 * @return array<string, mixed>|null
	 */
	public static function get( string $id ): ?array {
		foreach ( self::all() as $plan ) {
			if ( $plan['id'] === $id ) {
				return $plan;
			}
		}

		return null;
	}

	/**
	 * Plano que pode ser comprado agora com este preço (plano à venda, preço em vigor).
	 *
	 * @param string $price_id ID do preço.
	 * @return array<string, mixed>|null
	 */
	public static function find_for_sale( string $price_id ): ?array {
		foreach ( self::for_sale() as $plan ) {
			if ( $plan['price_id'] === $price_id ) {
				return $plan;
			}
		}

		return null;
	}

	/**
	 * Plano de um preço da Stripe, em vigor ou antigo, ou null se o preço não for de um plano deste site.
	 *
	 * O `amount` devolvido é o do preço pedido (quem assinou um valor antigo continua pagando esse valor).
	 *
	 * @param string $price_id ID do preço.
	 * @return array<string, mixed>|null
	 */
	public static function find( string $price_id ): ?array {
		if ( '' === $price_id ) {
			return null;
		}

		foreach ( self::all() as $plan ) {
			if ( $plan['price_id'] === $price_id ) {
				return $plan;
			}

			foreach ( $plan['old_prices'] as $old ) {
				if ( $old['price_id'] === $price_id ) {
					return array_merge( $plan, array( 'amount' => $old['amount'] ? $old['amount'] : $plan['amount'] ) );
				}
			}
		}

		return null;
	}

	/**
	 * Todos os preços (em vigor e antigos) de um plano.
	 *
	 * @param array<string, mixed> $plan Plano.
	 * @return string[]
	 */
	public static function price_ids( array $plan ): array {
		return array_merge( array( $plan['price_id'] ), wp_list_pluck( $plan['old_prices'], 'price_id' ) );
	}

	/**
	 * Grava (cria ou substitui) um plano.
	 *
	 * @param array<string, mixed> $plan Plano.
	 */
	public static function save( array $plan ): void {
		$plan = self::sanitize_plan( $plan );

		if ( ! $plan ) {
			return;
		}

		$plans = array();
		foreach ( self::settings()['plans'] as $current ) {
			$plans[ $current['id'] ] = $current;
		}

		$plans[ $plan['id'] ] = $plan;

		self::update( array( 'plans' => array_values( $plans ) ) );
	}

	/**
	 * Remove um plano do cadastro do site.
	 *
	 * @param string $id Identificador do plano.
	 */
	public static function remove( string $id ): void {
		self::update( array( 'plans' => array_values( array_filter( self::settings()['plans'], static fn( array $plan ): bool => $plan['id'] !== $id ) ) ) );
	}

	/**
	 * Nome para mostrar: o nome dado no painel ou, sem ele, "R$ 15 por mês".
	 *
	 * @param array<string, mixed> $plan Plano.
	 */
	public static function label( array $plan ): string {
		if ( '' !== $plan['name'] ) {
			return $plan['name'];
		}

		/* translators: %s: valor, ex.: R$ 15. */
		return sprintf( __( '%s por mês', 'resgata-pet' ), self::format_amount( (int) $plan['amount'] ) );
	}

	/**
	 * Valor em reais, para mostrar: "R$ 15" ou "R$ 12,50".
	 *
	 * @param int $amount Valor em centavos.
	 */
	public static function format_amount( int $amount ): string {
		return 'R$ ' . number_format_i18n( $amount / 100, 0 === $amount % 100 ? 0 : 2 );
	}

	/**
	 * Mantém o ajuste manual dentro do limite aceito.
	 *
	 * @param int $value Ajuste informado.
	 */
	public static function clamp_adjustment( int $value ): int {
		return max( self::ADJUSTMENT_MIN, min( self::ADJUSTMENT_MAX, $value ) );
	}

	/**
	 * Contador de padrinhos.
	 *
	 * @return array{active: int, adjustment: int, total: int, updated_at: int} `updated_at` é um timestamp (0 = nunca).
	 */
	public static function counter(): array {
		$saved      = get_option( self::COUNTER_OPTION, array() );
		$saved      = is_array( $saved ) ? $saved : array();
		$active     = absint( $saved['active'] ?? 0 );
		$adjustment = self::settings()['adjustment'];

		return array(
			'active'     => $active,
			'adjustment' => $adjustment,
			// O ajuste pode ser negativo, mas o site nunca mostra menos que zero.
			'total'      => max( 0, $active + $adjustment ),
			'updated_at' => absint( $saved['updated_at'] ?? 0 ),
		);
	}

	/**
	 * Reconta as assinaturas ativas. A data "atualizado em" só muda se o número mudar.
	 */
	public static function refresh_counter(): void {
		$active = Sponsors::count_active();

		if ( self::counter()['active'] !== $active || false === get_option( self::COUNTER_OPTION ) ) {
			self::store_counter( $active );
		}
	}

	/**
	 * Grava o ajuste manual (padrinhos de fora da Stripe, por exemplo) e marca a data.
	 *
	 * @param int $adjustment Quantos padrinhos somar (ou tirar) do número calculado.
	 */
	public static function set_adjustment( int $adjustment ): void {
		$adjustment = self::clamp_adjustment( $adjustment );

		if ( self::settings()['adjustment'] === $adjustment ) {
			return;
		}

		self::update( array( 'adjustment' => $adjustment ) );
		self::store_counter( self::counter()['active'] );
	}

	/**
	 * Grava o número de ativos com a data de agora.
	 *
	 * @param int $active Assinaturas ativas.
	 */
	private static function store_counter( int $active ): void {
		update_option(
			self::COUNTER_OPTION,
			array(
				'active'     => $active,
				'updated_at' => time(),
			),
			false
		);
	}
}
