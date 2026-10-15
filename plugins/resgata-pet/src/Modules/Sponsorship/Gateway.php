<?php
/**
 * Chamadas à API da Stripe.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Sponsorship;

use ResgataPet\Modules\Settings\Organization;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

defined( 'ABSPATH' ) || exit;

/**
 * Único ponto do plugin que fala com a API da Stripe.
 *
 * O pagamento acontece sempre nas páginas da própria Stripe (Checkout e
 * portal do cliente): o site nunca recebe nem mostra campo de cartão.
 */
final class Gateway {

	/**
	 * Rótulo das sessões de Checkout deste fluxo no Dashboard da Stripe.
	 */
	private const INTEGRATION_IDENTIFIER = 'resgata-pet-apadrinhamento-kqzmvtha';

	/**
	 * Marca, nos metadados da sessão, que ela nasceu neste módulo (a conta pode vender outras coisas).
	 */
	public const ORIGIN_KEY   = 'resgata_origin';
	public const ORIGIN_VALUE = 'sponsorship';

	/**
	 * Permissões de escrita que a chave restrita precisa ter na Stripe.
	 */
	public const PERMISSIONS = array( 'products', 'prices', 'checkout', 'portal_config', 'portal_session' );

	/**
	 * Cliente da API, ou null se a chave não estiver definida.
	 *
	 * @param string $api_key Chave a usar; por padrão, a do site (`Keys`).
	 */
	public static function client( string $api_key = '' ): ?StripeClient {
		$api_key = '' !== $api_key ? $api_key : Keys::secret_key();

		if ( '' === $api_key ) {
			return null;
		}

		return new StripeClient(
			array(
				'api_key'        => $api_key,
				'stripe_version' => \Stripe\Util\ApiVersion::CURRENT,
			)
		);
	}

	/**
	 * Cria a sessão de Checkout de uma assinatura mensal e devolve o endereço dela.
	 *
	 * @param string $price_id   ID do preço do plano.
	 * @param string $return_url Página do site para onde a pessoa volta.
	 * @return string|\WP_Error
	 */
	public static function checkout_url( string $price_id, string $return_url ) {
		$client = self::client();

		if ( ! $client ) {
			return self::unavailable();
		}

		/**
		 * Filtra as formas de pagamento escondidas no Checkout da assinatura.
		 *
		 * Por padrão a assinatura aceita só cartão: o boleto fica de fora. As demais
		 * formas são ligadas e desligadas no Dashboard da Stripe.
		 *
		 * @param string[] $types Tipos de forma de pagamento da Stripe.
		 */
		$excluded = array_values( array_filter( (array) apply_filters( 'resgata_sponsorship_excluded_payment_methods', array( 'boleto' ) ) ) );

		// Depois de pagar, a pessoa cai na página de agradecimento; sem ela, volta para os planos.
		$thanks = ThanksPage::url();

		$params = array(
			'mode'                   => 'subscription',
			'line_items'             => array(
				array(
					'price'    => $price_id,
					'quantity' => 1,
				),
			),
			'locale'                 => 'pt-BR',
			'success_url'            => '' !== $thanks ? $thanks : add_query_arg( Checkout::RESULT_PARAM, 'obrigado', $return_url ) . '#resgata-apadrinhar',
			'cancel_url'             => $return_url . '#resgata-apadrinhar',
			'integration_identifier' => self::INTEGRATION_IDENTIFIER,
			'metadata'               => array( self::ORIGIN_KEY => self::ORIGIN_VALUE ),
		);

		if ( $excluded ) {
			$params['excluded_payment_method_types'] = $excluded;
		}

		try {
			$session = $client->checkout->sessions->create( $params );
		} catch ( ApiErrorException $error ) {
			return self::failed( $error );
		}

		return is_string( $session->url ) && '' !== $session->url ? $session->url : self::unavailable();
	}

	/**
	 * Cria uma sessão do portal do cliente (trocar cartão, mudar valor, cancelar) e devolve o endereço dela.
	 *
	 * @param string $customer_id ID do cliente na Stripe.
	 * @param string $return_url  Página do site para onde a pessoa volta.
	 * @return string|\WP_Error
	 */
	public static function portal_url( string $customer_id, string $return_url ) {
		$client = self::client();

		if ( ! $client ) {
			return self::unavailable();
		}

		$params = array(
			'customer'   => $customer_id,
			'return_url' => $return_url,
			'locale'     => 'pt-BR',
		);

		$configuration = Plans::settings()['portal_configuration'];
		if ( '' !== $configuration ) {
			$params['configuration'] = $configuration;
		}

		try {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Nome da propriedade na biblioteca da Stripe.
			$session = $client->billingPortal->sessions->create( $params );
		} catch ( ApiErrorException $error ) {
			return self::failed( $error );
		}

		return is_string( $session->url ) && '' !== $session->url ? $session->url : self::unavailable();
	}

	/**
	 * Deixa um teste ou outra integração responder no lugar da Stripe nas operações de planos.
	 *
	 * @param string               $operation `verify_key`, `permissions`, `create_plan`, `rename_product`, `replace_price`, `archive_plan` ou `sync_portal`.
	 * @param array<string, mixed> $params    Dados da operação.
	 * @return mixed Null para seguir com a chamada real.
	 */
	private static function pre( string $operation, array $params ) {
		/**
		 * Filtra o resultado de uma operação de planos antes de chamar a Stripe.
		 *
		 * Devolva algo diferente de null para pular a chamada (usado nos testes).
		 *
		 * @param mixed                $result    Null por padrão.
		 * @param string               $operation Nome da operação.
		 * @param array<string, mixed> $params    Dados da operação.
		 */
		return apply_filters( 'resgata_sponsorship_pre_stripe', null, $operation, $params );
	}

	/**
	 * Confere, na Stripe, se uma chave é reconhecida (as permissões são conferidas em `permissions()`).
	 *
	 * @param string $api_key Chave a conferir, ainda não gravada.
	 * @return true|\WP_Error
	 */
	public static function verify_key( string $api_key ) {
		// A chave não vai para o filtro: ela não pode sair daqui.
		$pre = self::pre( __FUNCTION__, array() );
		if ( null !== $pre ) {
			return $pre;
		}

		$client = self::client( $api_key );
		if ( ! $client ) {
			return self::not_configured();
		}

		try {
			$client->products->all( array( 'limit' => 1 ) );
		} catch ( \Stripe\Exception\PermissionException $error ) {
			// A chave existe; o que falta liberar aparece na lista de permissões do painel.
			return true;
		} catch ( ApiErrorException $error ) {
			return self::admin_failed( $error );
		}

		return true;
	}

	/**
	 * Confere, uma a uma, as permissões que a chave do site precisa ter na Stripe.
	 *
	 * Cada conferência é um pedido de escrita sobre um item que não existe: nada é
	 * criado nem alterado na conta. Com a permissão, a Stripe responde "não
	 * encontrado"; sem ela, responde "sem permissão".
	 *
	 * @return array<string, bool>|\WP_Error Permissão => liberada, na ordem de `PERMISSIONS`.
	 */
	public static function permissions() {
		$pre = self::pre( __FUNCTION__, array() );
		if ( null !== $pre ) {
			return $pre;
		}

		$client = self::client();
		if ( ! $client ) {
			return self::not_configured();
		}

		$missing = 'resgata_verificacao';
		$probes  = array(
			'products'       => static fn() => $client->products->update( 'prod_' . $missing, array() ),
			'prices'         => static fn() => $client->prices->update( 'price_' . $missing, array() ),
			'checkout'       => static fn() => $client->checkout->sessions->expire( 'cs_' . $missing ),
			'portal_config'  => static fn() => $client->billingPortal->configurations->update( 'bpc_' . $missing, array() ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Nome da biblioteca da Stripe.
			'portal_session' => static fn() => $client->billingPortal->sessions->create( array( 'customer' => 'cus_' . $missing ) ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Nome da biblioteca da Stripe.
		);
		$result  = array();

		foreach ( self::PERMISSIONS as $permission ) {
			try {
				$probes[ $permission ]();
				$result[ $permission ] = true;
			} catch ( \Stripe\Exception\PermissionException $error ) {
				$result[ $permission ] = false;
			} catch ( \Stripe\Exception\InvalidRequestException $error ) {
				// "Não encontrado": o pedido passou pela conferência de permissão.
				$result[ $permission ] = true;
			} catch ( ApiErrorException $error ) {
				return self::admin_failed( $error );
			}
		}

		return $result;
	}

	/**
	 * Cria na Stripe o produto (nome do plano) e o preço mensal dele.
	 *
	 * @param string            $name   Nome do produto, mostrado no Checkout e nas faturas.
	 * @param int               $amount Valor mensal em centavos.
	 * @param StripeClient|null $client Cliente a usar; por padrão, o da chave do site.
	 * @return array{product_id: string, price_id: string}|\WP_Error
	 */
	public static function create_plan( string $name, int $amount, ?StripeClient $client = null ) {
		$pre = self::pre( __FUNCTION__, compact( 'name', 'amount' ) );
		if ( null !== $pre ) {
			return $pre;
		}

		$client = $client ?? self::client();
		if ( ! $client ) {
			return self::not_configured();
		}

		try {
			$price = $client->prices->create(
				array(
					'currency'     => 'brl',
					'unit_amount'  => $amount,
					'recurring'    => array( 'interval' => 'month' ),
					'product_data' => array(
						'name'     => $name,
						'metadata' => array( self::ORIGIN_KEY => self::ORIGIN_VALUE ),
					),
				)
			);
		} catch ( ApiErrorException $error ) {
			return self::admin_failed( $error );
		}

		return array(
			'product_id' => is_string( $price->product ) ? $price->product : (string) $price->product->id,
			'price_id'   => (string) $price->id,
		);
	}

	/**
	 * Troca o nome do produto de um plano.
	 *
	 * @param string $product_id ID do produto.
	 * @param string $name       Nome novo.
	 * @return true|\WP_Error
	 */
	public static function rename_product( string $product_id, string $name ) {
		$pre = self::pre( __FUNCTION__, compact( 'product_id', 'name' ) );
		if ( null !== $pre ) {
			return $pre;
		}

		$client = self::client();
		if ( ! $client ) {
			return self::not_configured();
		}

		try {
			$client->products->update( $product_id, array( 'name' => $name ) );
		} catch ( ApiErrorException $error ) {
			return self::admin_failed( $error );
		}

		return true;
	}

	/**
	 * Cria o preço novo de um plano e tira o antigo de venda.
	 *
	 * Na Stripe o valor de um preço não muda: cria-se outro no mesmo produto.
	 * Quem já assina o preço antigo continua pagando o valor antigo.
	 *
	 * @param string $product_id   ID do produto do plano.
	 * @param int    $amount       Valor mensal novo, em centavos.
	 * @param string $old_price_id Preço que sai de venda.
	 * @return string|\WP_Error ID do preço novo.
	 */
	public static function replace_price( string $product_id, int $amount, string $old_price_id ) {
		$pre = self::pre( __FUNCTION__, compact( 'product_id', 'amount', 'old_price_id' ) );
		if ( null !== $pre ) {
			return $pre;
		}

		$client = self::client();
		if ( ! $client ) {
			return self::not_configured();
		}

		try {
			$price = $client->prices->create(
				array(
					'currency'    => 'brl',
					'unit_amount' => $amount,
					'recurring'   => array( 'interval' => 'month' ),
					'product'     => $product_id,
				)
			);

			// Arquivar só impede vendas novas; as assinaturas existentes seguem normalmente.
			$client->prices->update( $old_price_id, array( 'active' => false ) );
		} catch ( ApiErrorException $error ) {
			return self::admin_failed( $error );
		}

		return (string) $price->id;
	}

	/**
	 * Tira de venda, na Stripe, o produto e os preços de um plano excluído.
	 *
	 * @param string   $product_id ID do produto (vazio em planos antigos, sem produto gravado).
	 * @param string[] $price_ids  Preços do plano.
	 * @return true|\WP_Error
	 */
	public static function archive_plan( string $product_id, array $price_ids ) {
		$pre = self::pre( __FUNCTION__, compact( 'product_id', 'price_ids' ) );
		if ( null !== $pre ) {
			return $pre;
		}

		$client = self::client();
		if ( ! $client ) {
			return self::not_configured();
		}

		try {
			foreach ( $price_ids as $price_id ) {
				$client->prices->update( $price_id, array( 'active' => false ) );
			}

			if ( '' !== $product_id ) {
				$client->products->update( $product_id, array( 'active' => false ) );
			}
		} catch ( ApiErrorException $error ) {
			return self::admin_failed( $error );
		}

		return true;
	}

	/**
	 * Cria ou atualiza a configuração do portal do cliente (área do padrinho) com os planos à venda.
	 *
	 * @param array<int, array<string, mixed>> $plans   Planos à venda.
	 * @param string                           $current ID da configuração gravada (vazio = criar).
	 * @param StripeClient|null                $client  Cliente a usar; por padrão, o da chave do site.
	 * @return string|\WP_Error ID da configuração.
	 */
	public static function sync_portal( array $plans, string $current, ?StripeClient $client = null ) {
		$pre = self::pre( __FUNCTION__, compact( 'plans', 'current' ) );
		if ( null !== $pre ) {
			return $pre;
		}

		$client = $client ?? self::client();
		if ( ! $client ) {
			return self::not_configured();
		}

		$products = array();
		foreach ( $plans as $plan ) {
			if ( '' !== $plan['product_id'] ) {
				$products[ $plan['product_id'] ]['product']  = $plan['product_id'];
				$products[ $plan['product_id'] ]['prices'][] = $plan['price_id'];
			}
		}

		$name   = Organization::get()['name'];
		$update = array( 'enabled' => false );

		if ( $products ) {
			$update = array(
				'enabled'                 => true,
				'default_allowed_updates' => array( 'price' ),
				'products'                => array_values( $products ),
				// O valor novo vale a partir da próxima cobrança, sem cobrança proporcional.
				'proration_behavior'      => 'none',
			);
		}

		$params = array(
			'business_profile' => array(
				'headline' => sprintf( 'Apadrinhamento – %s', '' !== $name ? $name : get_bloginfo( 'name' ) ),
			),
			'features'         => array(
				'payment_method_update' => array( 'enabled' => true ),
				'invoice_history'       => array( 'enabled' => true ),
				'subscription_cancel'   => array(
					'enabled' => true,
					// O padrinho segue ativo até o fim do mês que já pagou.
					'mode'    => 'at_period_end',
				),
				'subscription_update'   => $update,
			),
		);

		try {
			if ( '' !== $current ) {
				try {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Nome da propriedade na biblioteca da Stripe.
					return (string) $client->billingPortal->configurations->update( $current, $params )->id;
				} catch ( \Stripe\Exception\InvalidRequestException $error ) {
					// A configuração gravada é de outra conta ou ambiente: cria uma nova abaixo.
					unset( $error );
				}
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Nome da propriedade na biblioteca da Stripe.
			return (string) $client->billingPortal->configurations->create( $params )->id;
		} catch ( ApiErrorException $error ) {
			return self::admin_failed( $error );
		}
	}

	/**
	 * Erro para o painel quando a chave ainda não foi definida.
	 */
	private static function not_configured(): \WP_Error {
		return new \WP_Error( 'resgata_stripe_not_configured', __( 'A chave da Stripe ainda não foi definida. Peça ao administrador do site para informar a chave em Configurações, na aba de pagamentos.', 'resgata-pet' ), array( 'status' => 503 ) );
	}

	/**
	 * Erro para o painel: diz o que fazer, sem repetir a mensagem da Stripe (que pode trazer um trecho da chave).
	 *
	 * @param ApiErrorException $error Erro da API.
	 */
	private static function admin_failed( ApiErrorException $error ): \WP_Error {
		self::log( $error );

		if ( $error instanceof \Stripe\Exception\PermissionException ) {
			return new \WP_Error(
				'resgata_stripe_permission',
				__( 'A chave da Stripe usada no site não tem permissão para mexer nos planos. Na Stripe, edite a chave restrita e libere a escrita nos itens marcados como pendentes em Configurações, na aba de pagamentos.', 'resgata-pet' ),
				array( 'status' => 502 )
			);
		}

		if ( $error instanceof \Stripe\Exception\AuthenticationException ) {
			return new \WP_Error( 'resgata_stripe_auth', __( 'A Stripe não reconheceu a chave. Confira se ela foi copiada inteira e se ainda está ativa.', 'resgata-pet' ), array( 'status' => 502 ) );
		}

		return new \WP_Error( 'resgata_stripe_failed', __( 'A Stripe não aceitou a operação. Tente de novo em alguns minutos.', 'resgata-pet' ), array( 'status' => 502 ) );
	}

	/**
	 * Erro mostrado ao visitante quando a Stripe não está configurada ou não respondeu.
	 */
	private static function unavailable(): \WP_Error {
		return new \WP_Error( 'resgata_stripe_unavailable', __( 'Não foi possível abrir o pagamento agora. Tente de novo em alguns minutos.', 'resgata-pet' ) );
	}

	/**
	 * Registra a falha no log e devolve o erro genérico.
	 *
	 * Vão para o log só o tipo e os códigos do erro: a mensagem da Stripe pode
	 * trazer um trecho da chave ou dados de quem está pagando.
	 *
	 * @param ApiErrorException $error Erro da API.
	 */
	private static function failed( ApiErrorException $error ): \WP_Error {
		self::log( $error );

		return self::unavailable();
	}

	/**
	 * Registra no log de depuração o tipo e os códigos de um erro da Stripe (nunca a mensagem).
	 *
	 * @param ApiErrorException $error Erro da API.
	 */
	private static function log( ApiErrorException $error ): void {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Só com o log de depuração ligado.
			error_log( sprintf( 'Resgata Pet (Stripe): %s, HTTP %d, código "%s"', get_class( $error ), (int) $error->getHttpStatus(), (string) $error->getStripeCode() ) );
		}
	}
}
