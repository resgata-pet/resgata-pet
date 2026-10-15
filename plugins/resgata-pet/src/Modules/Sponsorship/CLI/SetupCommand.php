<?php
/**
 * Comando WP-CLI que prepara a conta Stripe para o apadrinhamento.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Sponsorship\CLI;

use ResgataPet\Modules\Sponsorship\Gateway;
use ResgataPet\Modules\Sponsorship\Keys;
use ResgataPet\Modules\Sponsorship\PlanService;
use ResgataPet\Modules\Sponsorship\Plans;
use ResgataPet\Modules\Sponsorship\WebhookController;
use ResgataPet\Modules\Sponsorship\WebhookHandler;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

defined( 'ABSPATH' ) || exit;

/**
 * Cria na Stripe os planos mensais e a configuração do portal do cliente.
 */
final class SetupCommand {

	/**
	 * Prefixo da `lookup_key` dos preços: é por ela que o comando reconhece o que já criou.
	 */
	private const LOOKUP_PREFIX = 'resgata_apadrinhamento_';

	/**
	 * Cria (ou reaproveita) os planos mensais e o portal do cliente na Stripe.
	 *
	 * Pode rodar quantas vezes for preciso: planos que já existem não são duplicados.
	 * O site guarda só os IDs dos preços.
	 *
	 * A chave usada é a da variável de ambiente RESGATA_STRIPE_SETUP_KEY, se existir, ou
	 * a constante RESGATA_STRIPE_SECRET_KEY. Ela precisa de permissão de escrita em
	 * Produtos, Preços e Configurações do portal do cliente (a chave do dia a dia do
	 * site não precisa disso).
	 *
	 * ## OPTIONS
	 *
	 * [--amounts=<valores>]
	 * : Valores mensais em reais, separados por vírgula. Padrão: 15,30,50,75,100.
	 *
	 * [--price-ids=<ids>]
	 * : Em vez de criar, usa preços mensais em BRL já criados no Dashboard (IDs separados por vírgula).
	 *
	 * [--skip-portal]
	 * : Não cria nem altera a configuração do portal do cliente.
	 *
	 * ## EXAMPLES
	 *
	 *     wp resgata stripe-setup
	 *     wp resgata stripe-setup --amounts=20,40,80
	 *     wp resgata stripe-setup --price-ids=price_123,price_456
	 *
	 * @param string[]              $args       Argumentos posicionais.
	 * @param array<string, string> $assoc_args Argumentos nomeados.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$setup_key = getenv( 'RESGATA_STRIPE_SETUP_KEY' );
		$api_key   = is_string( $setup_key ) && '' !== trim( $setup_key ) ? trim( $setup_key ) : Keys::secret_key();
		$client    = Gateway::client( $api_key );

		if ( ! $client ) {
			\WP_CLI::error( 'Informe a chave da Stripe no painel ou defina RESGATA_STRIPE_SECRET_KEY no wp-config.php (ou a variável de ambiente RESGATA_STRIPE_SETUP_KEY) antes de rodar este comando.' );
		}

		if ( str_contains( $api_key, '_live_' ) ) {
			\WP_CLI::confirm( 'A chave é de PRODUÇÃO (live). Criar os planos na conta real?', $assoc_args );
		}

		try {
			$prices = isset( $assoc_args['price-ids'] )
				? $this->import_prices( $client, wp_parse_list( $assoc_args['price-ids'] ) )
				: $this->ensure_prices( $client, $this->amounts( $assoc_args ) );

			// Guarda os planos sem perder nomes e textos já cadastrados no painel.
			PlanService::import( $prices );

			if ( ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-portal', false ) ) {
				$portal = Gateway::sync_portal( Plans::for_sale(), Plans::settings()['portal_configuration'], $client );

				if ( is_wp_error( $portal ) ) {
					\WP_CLI::error( $portal->get_error_message() );
				}

				Plans::update( array( 'portal_configuration' => $portal ) );
				\WP_CLI::log( 'Portal do cliente configurado (trocar cartão, mudar valor e cancelar).' );
			}
		} catch ( ApiErrorException $error ) {
			\WP_CLI::error( 'A Stripe recusou a operação: ' . $error->getMessage() );
		}

		Plans::refresh_counter();

		\WP_CLI::log( '' );
		\WP_CLI::log( 'Cadastre este endereço de webhook na Stripe (em produção):' );
		\WP_CLI::log( '  ' . WebhookController::url() );
		\WP_CLI::log( 'Eventos: ' . implode( ', ', WebhookHandler::event_types() ) );
		\WP_CLI::success( sprintf( '%d plano(s) pronto(s).', count( $prices ) ) );
	}

	/**
	 * Valores pedidos na linha de comando (em reais), convertidos para centavos.
	 *
	 * @param array<string, string> $assoc_args Argumentos nomeados.
	 * @return int[]
	 */
	private function amounts( array $assoc_args ): array {
		if ( ! isset( $assoc_args['amounts'] ) ) {
			return Plans::default_amounts();
		}

		$amounts = array();

		foreach ( wp_parse_list( $assoc_args['amounts'] ) as $value ) {
			$cents = (int) round( (float) str_replace( ',', '.', (string) $value ) * 100 );

			// A Stripe não cobra menos que R$ 0,50; o teto evita erro de digitação.
			if ( $cents < 100 || $cents > 500000 ) {
				\WP_CLI::error( "Valor inválido: $value. Use valores entre 1 e 5000 reais." );
			}

			$amounts[] = $cents;
		}

		return array_values( array_unique( $amounts ) );
	}

	/**
	 * Garante um produto e um preço mensal para cada valor.
	 *
	 * @param StripeClient $client  Cliente da API.
	 * @param int[]        $amounts Valores em centavos.
	 * @return array<int, array{amount: int, price_id: string, product_id: string}>
	 * @throws ApiErrorException Se a Stripe recusar alguma chamada.
	 */
	private function ensure_prices( StripeClient $client, array $amounts ): array {
		$keys     = array_map( static fn( int $amount ) => self::LOOKUP_PREFIX . $amount, $amounts );
		$existing = array();

		foreach ( array_chunk( $keys, 10 ) as $chunk ) {
			$found = $client->prices->all(
				array(
					'lookup_keys' => $chunk,
					'active'      => true,
					'limit'       => 100,
				)
			);

			foreach ( $found->data as $price ) {
				$existing[ (string) $price->lookup_key ] = $price;
			}
		}

		$prices = array();

		foreach ( $amounts as $amount ) {
			$key   = self::LOOKUP_PREFIX . $amount;
			$label = Plans::format_amount( $amount );

			if ( isset( $existing[ $key ] ) ) {
				$price = $existing[ $key ];
				\WP_CLI::log( "Plano de $label por mês: já existe ({$price->id})." );
			} else {
				// Um produto por plano: é o nome do produto que aparece no Checkout e nas faturas.
				$price = $client->prices->create(
					array(
						'currency'     => 'brl',
						'unit_amount'  => $amount,
						'recurring'    => array( 'interval' => 'month' ),
						'lookup_key'   => $key,
						'product_data' => array( 'name' => sprintf( 'Apadrinhamento %s por mês', $label ) ),
					)
				);
				\WP_CLI::log( "Plano de $label por mês: criado ({$price->id})." );
			}

			$prices[] = array(
				'amount'     => $amount,
				'price_id'   => (string) $price->id,
				'product_id' => is_string( $price->product ) ? $price->product : (string) $price->product->id,
			);
		}

		return $prices;
	}

	/**
	 * Usa preços criados no Dashboard, conferindo se são mensais e em reais.
	 *
	 * @param StripeClient $client    Cliente da API.
	 * @param string[]     $price_ids IDs dos preços.
	 * @return array<int, array{amount: int, price_id: string, product_id: string}>
	 * @throws ApiErrorException Se a Stripe recusar alguma chamada.
	 */
	private function import_prices( StripeClient $client, array $price_ids ): array {
		$prices = array();

		foreach ( $price_ids as $price_id ) {
			$price = $client->prices->retrieve( sanitize_text_field( (string) $price_id ) );

			if ( ! $price->active || 'brl' !== $price->currency || ! $price->recurring || 'month' !== $price->recurring->interval || 1 !== (int) $price->recurring->interval_count || ! $price->unit_amount ) {
				\WP_CLI::error( "O preço {$price->id} precisa estar ativo e ser mensal, em reais (BRL), com valor fixo." );
			}

			$prices[] = array(
				'amount'     => (int) $price->unit_amount,
				'price_id'   => (string) $price->id,
				'product_id' => is_string( $price->product ) ? $price->product : (string) $price->product->id,
			);

			\WP_CLI::log( 'Plano de ' . Plans::format_amount( (int) $price->unit_amount ) . " por mês: usando {$price->id}." );
		}

		if ( ! $prices ) {
			\WP_CLI::error( 'Informe ao menos um ID de preço em --price-ids.' );
		}

		return $prices;
	}
}
