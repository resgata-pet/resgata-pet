<?php
/**
 * API do painel: apadrinhamento.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel\Rest;

use ResgataWP\Core\Capabilities;
use ResgataWP\Core\Encryption;
use ResgataWP\Modules\Sponsorship\Gateway;
use ResgataWP\Modules\Sponsorship\Keys;
use ResgataWP\Modules\Sponsorship\PlanService;
use ResgataWP\Modules\Sponsorship\Plans;
use ResgataWP\Modules\Sponsorship\Sponsors;
use ResgataWP\Modules\Sponsorship\WebhookController;

defined( 'ABSPATH' ) || exit;

/**
 * Rotas `resgata-wp/v1/sponsorship` (resumo e contador) e `/sponsors` (lista de padrinhos).
 *
 * Os padrinhos têm dados pessoais: todas as rotas exigem `rwp_manage_sponsorship`
 * (Administrador e Protetor admin). As chaves da Stripe nunca saem daqui: a
 * resposta só diz se cada uma está definida. Só o Administrador grava as chaves
 * (`/sponsorship/keys`), porque trocar a chave é trocar a conta que recebe.
 */
final class SponsorshipController extends BaseController {

	private const PER_PAGE = array( 25, 50, 75, 100 );

	private const KEY_MAX = 255;

	/**
	 * Registra as rotas.
	 */
	public function register_routes(): void {
		$permission = static fn() => current_user_can( Capabilities::SPONSORSHIP );

		$this->route( '/sponsorship', \WP_REST_Server::READABLE, array( $this, 'get_overview' ), $permission );

		$this->route(
			'/sponsorship',
			\WP_REST_Server::EDITABLE,
			array( $this, 'update_overview' ),
			$permission,
			array(
				'adjustment' => array(
					'type'    => 'integer',
					'minimum' => Plans::ADJUSTMENT_MIN,
					'maximum' => Plans::ADJUSTMENT_MAX,
				),
			)
		);

		$this->route( '/sponsorship/permissions', \WP_REST_Server::READABLE, array( $this, 'get_permissions' ), $permission );

		$this->route(
			'/sponsorship/keys',
			\WP_REST_Server::EDITABLE,
			array( $this, 'update_keys' ),
			static fn() => current_user_can( 'manage_options' ),
			array(
				Keys::SECRET_KEY     => array(
					'type'      => 'string',
					'maxLength' => self::KEY_MAX,
				),
				Keys::WEBHOOK_SECRET => array(
					'type'      => 'string',
					'maxLength' => self::KEY_MAX,
				),
			)
		);

		$this->route( '/sponsorship/plans', \WP_REST_Server::CREATABLE, array( $this, 'create_plan' ), $permission, $this->plan_args( true ) );

		$plan_id = array(
			'id' => array(
				'type'    => 'string',
				'pattern' => '^[A-Za-z0-9_]+$',
			),
		);

		$this->route( '/sponsorship/plans/(?P<id>[A-Za-z0-9_]+)', \WP_REST_Server::EDITABLE, array( $this, 'update_plan' ), $permission, $plan_id + $this->plan_args( false ) );
		$this->route( '/sponsorship/plans/(?P<id>[A-Za-z0-9_]+)', \WP_REST_Server::DELETABLE, array( $this, 'delete_plan' ), $permission, $plan_id );

		$this->route(
			'/sponsors',
			\WP_REST_Server::READABLE,
			array( $this, 'list_sponsors' ),
			$permission,
			array(
				'page'     => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'enum'    => self::PER_PAGE,
					'default' => 25,
				),
				'status'   => array(
					'type'    => 'array',
					'default' => array(),
					'items'   => array(
						'type' => 'string',
						'enum' => array_keys( Sponsors::statuses() ),
					),
				),
				'search'   => array(
					'type'      => 'string',
					'maxLength' => 100,
					'default'   => '',
				),
			)
		);
	}

	/**
	 * GET /sponsorship.
	 */
	public function get_overview(): \WP_REST_Response {
		return new \WP_REST_Response( $this->overview() );
	}

	/**
	 * PUT/PATCH /sponsorship: grava o ajuste manual do contador.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function update_overview( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $request->has_param( 'adjustment' ) ) {
			Plans::set_adjustment( (int) $request['adjustment'] );
		}

		return new \WP_REST_Response( $this->overview() );
	}

	/**
	 * GET /sponsorship/permissions: o que a chave em uso consegue fazer na Stripe.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_permissions() {
		if ( '' === Keys::secret_key() ) {
			return new \WP_REST_Response(
				array(
					'checked'     => false,
					'permissions' => array_fill_keys( Gateway::PERMISSIONS, false ),
				)
			);
		}

		$permissions = Gateway::permissions();

		if ( is_wp_error( $permissions ) ) {
			return $permissions;
		}

		return new \WP_REST_Response(
			array(
				'checked'     => true,
				'permissions' => $permissions,
			)
		);
	}

	/**
	 * PUT/PATCH /sponsorship/keys: grava (cifradas) as chaves enviadas; valor vazio apaga a que estava salva.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_keys( \WP_REST_Request $request ) {
		$formats = array(
			Keys::SECRET_KEY     => array( '/^rk_(test|live)_[A-Za-z0-9]{10,}$/', __( 'A chave restrita da Stripe começa com "rk_live_" (ou "rk_test_", nos testes). Copie a chave inteira, sem espaços.', 'resgata-wp' ) ),
			Keys::WEBHOOK_SECRET => array( '/^whsec_[A-Za-z0-9+\/=_-]{10,}$/', __( 'O segredo do aviso de pagamentos começa com "whsec_". Copie o segredo inteiro, sem espaços.', 'resgata-wp' ) ),
		);
		$values  = array();

		foreach ( $formats as $field => list( $pattern, $message ) ) {
			if ( ! $request->has_param( $field ) ) {
				continue;
			}

			$value = trim( (string) $request[ $field ] );

			if ( 'constant' === Keys::source( $field ) ) {
				return new \WP_Error( 'rwp_stripe_key_locked', __( 'Esta chave está definida no arquivo wp-config.php do site e só pode ser trocada lá.', 'resgata-wp' ), array( 'status' => 409 ) );
			}

			if ( Keys::SECRET_KEY === $field && str_starts_with( $value, 'sk_' ) ) {
				return new \WP_Error( 'rwp_stripe_key_full', __( 'Esta é a chave secreta completa ("sk_"), que dá acesso total à conta da Stripe, e por isso não é aceita. Crie uma chave restrita ("rk_") com as permissões da lista e cole aqui.', 'resgata-wp' ), array( 'status' => 400 ) );
			}

			if ( '' !== $value && ! preg_match( $pattern, $value ) ) {
				return new \WP_Error( 'rwp_stripe_key_format', $message, array( 'status' => 400 ) );
			}

			$values[ $field ] = $value;
		}

		if ( array() !== array_filter( $values ) && ! Encryption::available() ) {
			return new \WP_Error( 'rwp_encryption_unavailable', __( 'Este site não consegue guardar a chave com segurança. Peça a quem cuida da hospedagem para definir as chaves no arquivo wp-config.php.', 'resgata-wp' ), array( 'status' => 500 ) );
		}

		if ( '' !== ( $values[ Keys::SECRET_KEY ] ?? '' ) ) {
			$verified = Gateway::verify_key( $values[ Keys::SECRET_KEY ] );

			if ( is_wp_error( $verified ) ) {
				return $verified;
			}
		}

		foreach ( $values as $field => $value ) {
			if ( ! Keys::store( $field, $value ) ) {
				return new \WP_Error( 'rwp_stripe_key_not_saved', __( 'Não foi possível guardar a chave. Tente de novo.', 'resgata-wp' ), array( 'status' => 500 ) );
			}
		}

		return new \WP_REST_Response( $this->overview() );
	}

	/**
	 * Campos de um plano aceitos na criação e na edição.
	 *
	 * @param bool $creating Se é a rota de criação (valor obrigatório).
	 * @return array<string, array<string, mixed>>
	 */
	private function plan_args( bool $creating ): array {
		$args = array(
			'amount'      => array(
				'type'     => 'integer',
				'minimum'  => Plans::AMOUNT_MIN,
				'maximum'  => Plans::AMOUNT_MAX,
				'required' => $creating,
			),
			'name'        => array(
				'type'      => 'string',
				'maxLength' => Plans::NAME_MAX,
			),
			'description' => array(
				'type'      => 'string',
				'maxLength' => Plans::DESCRIPTION_MAX,
			),
			'benefits'    => array(
				'type'     => 'array',
				'maxItems' => Plans::BENEFITS_MAX,
				'items'    => array(
					'type'      => 'string',
					'maxLength' => Plans::BENEFIT_MAX,
				),
			),
			'featured'    => array( 'type' => 'boolean' ),
		);

		if ( ! $creating ) {
			$args['active'] = array( 'type' => 'boolean' );
		}

		return $args;
	}

	/**
	 * Separa, da requisição, só os campos de plano que foram enviados.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return array<string, mixed>
	 */
	private function plan_data( \WP_REST_Request $request ): array {
		$data = array();

		foreach ( array( 'amount', 'name', 'description', 'benefits', 'featured', 'active' ) as $field ) {
			if ( $request->has_param( $field ) ) {
				$data[ $field ] = $request[ $field ];
			}
		}

		return $data;
	}

	/**
	 * POST /sponsorship/plans: cria o plano na Stripe e no site.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_plan( \WP_REST_Request $request ) {
		$plan = PlanService::create( $this->plan_data( $request ) );

		return is_wp_error( $plan ) ? $plan : new \WP_REST_Response( $this->overview(), 201 );
	}

	/**
	 * PUT/PATCH /sponsorship/plans/<id>: altera os campos enviados.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_plan( \WP_REST_Request $request ) {
		$plan = PlanService::update( (string) $request['id'], $this->plan_data( $request ) );

		return is_wp_error( $plan ) ? $plan : new \WP_REST_Response( $this->overview() );
	}

	/**
	 * DELETE /sponsorship/plans/<id>: exclui um plano sem padrinhos.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_plan( \WP_REST_Request $request ) {
		$deleted = PlanService::delete( (string) $request['id'] );

		return is_wp_error( $deleted ) ? $deleted : new \WP_REST_Response( $this->overview() );
	}

	/**
	 * GET /sponsors: lista paginada, mais recentes primeiro.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function list_sponsors( \WP_REST_Request $request ): \WP_REST_Response {
		$per_page = (int) $request['per_page'];
		$result   = Sponsors::query(
			(int) $request['page'],
			$per_page,
			array_map( 'sanitize_key', (array) $request['status'] ),
			sanitize_text_field( (string) $request['search'] )
		);

		return new \WP_REST_Response(
			array(
				'items'       => array_map( array( $this, 'prepare' ), $result['items'] ),
				'total'       => $result['total'],
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			)
		);
	}

	/**
	 * Resumo do módulo: estado das chaves, planos e contador.
	 *
	 * @return array<string, mixed>
	 */
	private function overview(): array {
		$plans = array();

		$counts = Sponsors::count_by_price();

		foreach ( Plans::all() as $plan ) {
			$subscribers = 0;
			foreach ( Plans::price_ids( $plan ) as $price_id ) {
				$subscribers += $counts[ $price_id ] ?? 0;
			}

			$plans[] = array(
				'id'          => $plan['id'],
				'price_id'    => $plan['price_id'],
				'amount'      => $plan['amount'],
				'label'       => Plans::format_amount( $plan['amount'] ),
				'name'        => $plan['name'],
				'description' => $plan['description'],
				'benefits'    => $plan['benefits'],
				'featured'    => $plan['featured'],
				'active'      => $plan['active'],
				// Planos criados antes do painel não têm produto gravado e não aceitam mudança de valor.
				'can_reprice' => '' !== $plan['product_id'],
				'subscribers' => $subscribers,
			);
		}

		$counter = Plans::counter();

		return array(
			'stripe'      => array(
				// Só "definida" ou "não definida": o valor das chaves nunca é enviado ao navegador.
				'secret_key'     => '' !== Keys::secret_key(),
				'webhook_secret' => '' !== Keys::webhook_secret(),
				'mode'           => Keys::mode(),
				// De onde vem cada chave: "constant" (wp-config.php), "panel" (salva aqui) ou vazio.
				'sources'        => array(
					Keys::SECRET_KEY     => Keys::source( Keys::SECRET_KEY ),
					Keys::WEBHOOK_SECRET => Keys::source( Keys::WEBHOOK_SECRET ),
				),
				// Só o começo e os 4 últimos caracteres, para reconhecer a chave em uso.
				'hint'           => Keys::hint(),
				// Chaves salvas que não puderam ser lidas e precisam ser informadas de novo.
				'unreadable'     => Keys::unreadable(),
				'can_store'      => Encryption::available(),
			),
			'webhook_url' => WebhookController::url(),
			'plans'       => $plans,
			'limits'      => array(
				'plans'       => Plans::MAX_PLANS,
				'amount_min'  => Plans::AMOUNT_MIN,
				'amount_max'  => Plans::AMOUNT_MAX,
				'name'        => Plans::NAME_MAX,
				'description' => Plans::DESCRIPTION_MAX,
				'benefit'     => Plans::BENEFIT_MAX,
				'benefits'    => Plans::BENEFITS_MAX,
			),
			// Preenchido quando o plano foi salvo, mas a área do padrinho não pôde ser atualizada na Stripe.
			'warning'     => PlanService::portal_warning(),
			'counter'     => array(
				'active'     => $counter['active'],
				'adjustment' => $counter['adjustment'],
				'total'      => $counter['total'],
				'updated_at' => $counter['updated_at'] ? gmdate( DATE_ATOM, $counter['updated_at'] ) : '',
			),
		);
	}

	/**
	 * Padrinho no formato do painel.
	 *
	 * @param array<string, mixed> $row Linha da tabela.
	 * @return array<string, mixed>
	 */
	private function prepare( array $row ): array {
		$plan = Plans::find( (string) $row['price_id'] );

		return array(
			'id'             => (int) $row['id'],
			'name'           => (string) $row['name'],
			'email'          => (string) $row['email'],
			'status'         => (string) $row['status'],
			// Nome do plano e valor que a pessoa paga (quem assinou um valor antigo continua nele).
			'plan'           => $plan ? $plan['name'] : '',
			'plan_amount'    => $plan ? Plans::format_amount( $plan['amount'] ) : '',
			'payment_failed' => (bool) $row['payment_failed'],
			'last_paid_at'   => $this->iso_date( (string) ( $row['last_paid_at'] ?? '' ) ),
			'created_at'     => $this->iso_date( (string) $row['created_at'] ),
		);
	}

	/**
	 * Converte uma data gravada em UTC para ISO 8601, para o navegador mostrar a hora certa.
	 *
	 * @param string $utc_date Data no formato do banco (UTC).
	 */
	private function iso_date( string $utc_date ): string {
		$date = '' !== $utc_date ? date_create_immutable( $utc_date, new \DateTimeZone( 'UTC' ) ) : false;

		return $date ? $date->format( DATE_ATOM ) : '';
	}
}
