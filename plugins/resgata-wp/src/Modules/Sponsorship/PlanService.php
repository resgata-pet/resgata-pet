<?php
/**
 * Cadastro dos planos de apadrinhamento.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

defined( 'ABSPATH' ) || exit;

/**
 * Cria, altera e exclui planos: fala com a Stripe (pelo `Gateway`), grava o
 * resultado no site (`Plans`) e mantém o portal do padrinho com os planos à venda.
 *
 * Usado pelo painel e pelo comando `wp resgata stripe-setup`. Quem chama é
 * responsável por conferir a permissão antes.
 */
final class PlanService {

	/**
	 * Mensagem da última falha ao atualizar o portal do padrinho (vazia = deu certo).
	 *
	 * O plano já foi gravado quando isso acontece; o painel só avisa.
	 *
	 * @var string
	 */
	private static string $portal_warning = '';

	/**
	 * Aviso da última operação, para o painel mostrar.
	 */
	public static function portal_warning(): string {
		return self::$portal_warning;
	}

	/**
	 * Nome do produto na Stripe (aparece no Checkout e nas faturas).
	 *
	 * @param string $name   Nome dado no painel (pode ser vazio).
	 * @param int    $amount Valor mensal em centavos.
	 */
	public static function product_name( string $name, int $amount ): string {
		$label = Plans::format_amount( $amount );

		return '' !== $name
			/* translators: %s: nome do plano. */
			? sprintf( __( 'Apadrinhamento %s', 'resgata-wp' ), $name )
			/* translators: %s: valor, ex.: R$ 15. */
			: sprintf( __( 'Apadrinhamento %s por mês', 'resgata-wp' ), $label );
	}

	/**
	 * Confere se o valor mensal está dentro do aceito.
	 *
	 * @param int $amount Valor em centavos.
	 */
	private static function valid_amount( int $amount ): bool {
		return $amount >= Plans::AMOUNT_MIN && $amount <= Plans::AMOUNT_MAX;
	}

	/**
	 * Erro de valor fora do aceito.
	 */
	private static function invalid_amount(): \WP_Error {
		return new \WP_Error(
			'resgata_plan_invalid_amount',
			sprintf(
				/* translators: 1: valor mínimo, 2: valor máximo. */
				__( 'Informe um valor mensal entre %1$s e %2$s.', 'resgata-wp' ),
				Plans::format_amount( Plans::AMOUNT_MIN ),
				Plans::format_amount( Plans::AMOUNT_MAX )
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Cria um plano novo.
	 *
	 * @param array<string, mixed> $data `amount` (centavos), `name`, `description`, `benefits`, `featured`.
	 * @return array<string, mixed>|\WP_Error O plano criado.
	 */
	public static function create( array $data ) {
		$amount = absint( $data['amount'] ?? 0 );
		$name   = Plans::sanitize_text( $data['name'] ?? '', Plans::NAME_MAX );

		if ( ! self::valid_amount( $amount ) ) {
			return self::invalid_amount();
		}

		if ( count( Plans::all() ) >= Plans::MAX_PLANS ) {
			return new \WP_Error( 'resgata_plan_limit', __( 'O limite de planos foi atingido. Exclua um plano que não é mais usado antes de criar outro.', 'resgata-wp' ), array( 'status' => 400 ) );
		}

		$created = Gateway::create_plan( self::product_name( $name, $amount ), $amount );

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		$plan = array(
			'product_id'  => $created['product_id'],
			'price_id'    => $created['price_id'],
			'amount'      => $amount,
			'name'        => $name,
			'description' => $data['description'] ?? '',
			'benefits'    => $data['benefits'] ?? array(),
			'featured'    => ! empty( $data['featured'] ),
			'active'      => true,
		);

		Plans::save( $plan );

		$plan = Plans::find( $created['price_id'] );

		if ( $plan && $plan['featured'] ) {
			self::keep_one_featured( $plan['id'] );
		}

		self::sync_portal();

		return (array) $plan;
	}

	/**
	 * Altera um plano. Só os campos enviados mudam.
	 *
	 * @param string               $id      Identificador do plano.
	 * @param array<string, mixed> $changes `amount`, `name`, `description`, `benefits`, `featured`, `active`.
	 * @return array<string, mixed>|\WP_Error O plano alterado.
	 */
	public static function update( string $id, array $changes ) {
		$plan = Plans::get( $id );

		if ( ! $plan ) {
			return self::not_found();
		}

		$resync = false;

		if ( array_key_exists( 'amount', $changes ) && absint( $changes['amount'] ) !== $plan['amount'] ) {
			$amount = absint( $changes['amount'] );

			if ( ! self::valid_amount( $amount ) ) {
				return self::invalid_amount();
			}

			if ( '' === $plan['product_id'] ) {
				return new \WP_Error( 'resgata_plan_legacy', __( 'Este plano foi criado em uma versão antiga e não aceita mudança de valor. Crie um plano novo e esconda este.', 'resgata-wp' ), array( 'status' => 400 ) );
			}

			$price_id = Gateway::replace_price( $plan['product_id'], $amount, $plan['price_id'] );

			if ( is_wp_error( $price_id ) ) {
				return $price_id;
			}

			// Quem já assina continua no preço antigo: ele fica guardado para o site reconhecer.
			$plan['old_prices'][] = array(
				'price_id' => $plan['price_id'],
				'amount'   => $plan['amount'],
			);
			$plan['price_id']     = $price_id;
			$plan['amount']       = $amount;
			$resync               = true;
		}

		if ( array_key_exists( 'name', $changes ) ) {
			$name = Plans::sanitize_text( $changes['name'], Plans::NAME_MAX );

			if ( $name !== $plan['name'] && '' !== $plan['product_id'] ) {
				$renamed = Gateway::rename_product( $plan['product_id'], self::product_name( $name, $plan['amount'] ) );

				if ( is_wp_error( $renamed ) ) {
					return $renamed;
				}
			}

			$plan['name'] = $name;
		}

		foreach ( array( 'description', 'benefits' ) as $field ) {
			if ( array_key_exists( $field, $changes ) ) {
				$plan[ $field ] = $changes[ $field ];
			}
		}

		if ( array_key_exists( 'featured', $changes ) ) {
			$plan['featured'] = ! empty( $changes['featured'] );
		}

		if ( array_key_exists( 'active', $changes ) && ! empty( $changes['active'] ) !== $plan['active'] ) {
			$plan['active'] = ! empty( $changes['active'] );
			$resync         = true;
		}

		Plans::save( $plan );

		if ( $plan['featured'] ) {
			self::keep_one_featured( $plan['id'] );
		}

		if ( $resync ) {
			self::sync_portal();
		}

		return (array) Plans::get( $plan['id'] );
	}

	/**
	 * Exclui um plano que ninguém assina. Com padrinhos, o caminho é escondê-lo do site.
	 *
	 * @param string $id Identificador do plano.
	 * @return true|\WP_Error
	 */
	public static function delete( string $id ) {
		$plan = Plans::get( $id );

		if ( ! $plan ) {
			return self::not_found();
		}

		if ( self::subscribers( $plan ) > 0 ) {
			return new \WP_Error( 'resgata_plan_in_use', __( 'Este plano tem padrinhos, por isso não pode ser excluído. Esconda-o do site: quem já apadrinha continua normalmente.', 'resgata-wp' ), array( 'status' => 409 ) );
		}

		$archived = Gateway::archive_plan( $plan['product_id'], Plans::price_ids( $plan ) );

		if ( is_wp_error( $archived ) ) {
			return $archived;
		}

		Plans::remove( $id );
		self::sync_portal();

		return true;
	}

	/**
	 * Quantos padrinhos (em qualquer situação) estão ligados a um plano.
	 *
	 * @param array<string, mixed> $plan Plano.
	 */
	public static function subscribers( array $plan ): int {
		$counts = Sponsors::count_by_price();
		$total  = 0;

		foreach ( Plans::price_ids( $plan ) as $price_id ) {
			$total += $counts[ $price_id ] ?? 0;
		}

		return $total;
	}

	/**
	 * Grava os planos vindos do comando de setup, sem perder nomes e textos já cadastrados.
	 *
	 * Planos que não vieram na lista deixam de ser vendidos, mas continuam
	 * cadastrados (quem já assina segue reconhecido).
	 *
	 * @param array<int, array{amount: int, price_id: string, product_id: string}> $prices Preços criados ou importados.
	 */
	public static function import( array $prices ): void {
		$kept = array();

		foreach ( $prices as $price ) {
			$existing = Plans::find( $price['price_id'] );
			$existing = $existing ?? ( '' !== $price['product_id'] ? Plans::get( $price['product_id'] ) : null );
			$plan     = $existing ? $existing : array();

			if ( $existing && $existing['price_id'] !== $price['price_id'] ) {
				$plan['old_prices'][] = array(
					'price_id' => $existing['price_id'],
					'amount'   => $existing['amount'],
				);
			}

			$plan['price_id']   = $price['price_id'];
			$plan['product_id'] = $price['product_id'];
			$plan['amount']     = $price['amount'];
			$plan['active']     = true;

			Plans::save( $plan );

			$saved = Plans::find( $price['price_id'] );
			if ( $saved ) {
				$kept[] = $saved['id'];
			}
		}

		foreach ( Plans::all() as $plan ) {
			if ( $plan['active'] && ! in_array( $plan['id'], $kept, true ) ) {
				$plan['active'] = false;
				Plans::save( $plan );
			}
		}
	}

	/**
	 * Atualiza o portal do padrinho com os planos à venda. Não interrompe a operação se falhar.
	 */
	public static function sync_portal(): void {
		self::$portal_warning = '';

		$result = Gateway::sync_portal( Plans::for_sale(), Plans::settings()['portal_configuration'] );

		if ( is_wp_error( $result ) ) {
			self::$portal_warning = __( 'O plano foi salvo, mas não foi possível atualizar a área do padrinho na Stripe. Salve de novo em alguns minutos.', 'resgata-wp' );
			return;
		}

		Plans::update( array( 'portal_configuration' => Plans::sanitize_id( $result ) ) );
	}

	/**
	 * Só um plano fica em destaque: tira o destaque dos outros.
	 *
	 * @param string $id Plano que fica em destaque.
	 */
	private static function keep_one_featured( string $id ): void {
		foreach ( Plans::all() as $plan ) {
			if ( $plan['featured'] && $plan['id'] !== $id ) {
				$plan['featured'] = false;
				Plans::save( $plan );
			}
		}
	}

	/**
	 * Erro de plano inexistente.
	 */
	private static function not_found(): \WP_Error {
		return new \WP_Error( 'resgata_plan_not_found', __( 'Plano não encontrado. Atualize a página e tente de novo.', 'resgata-wp' ), array( 'status' => 404 ) );
	}
}
