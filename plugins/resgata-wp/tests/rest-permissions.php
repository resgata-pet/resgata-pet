<?php
/**
 * Teste de permissões da API do painel (`resgata-wp/v1`).
 *
 * Roda com `make test` (WP-CLI: `wp eval-file`), no site de desenvolvimento.
 * Descobre sozinho todas as rotas do namespace, então rotas de módulos novos
 * entram no teste sem mudar este arquivo.
 *
 * Para cada rota e método confere que:
 * - visitante recebe 401;
 * - assinante (logado, sem acesso ao painel) recebe 403;
 * - Protetor admin e Administrador recebem 2xx.
 *
 * Cria usuários e um animal temporários e apaga tudo no final.
 *
 * @package ResgataWP
 */

use ResgataWP\Core\Capabilities;
use ResgataWP\Modules\Panel\Rest\BaseController;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/user.php';

// Nenhum e-mail sai durante o teste.
add_filter( 'pre_wp_mail', '__return_true' );

// Opções que as rotas de configurações podem alterar: guardadas aqui e devolvidas no final.
$rwp_saved_options = array(
	'rwp_organization'        => get_option( 'rwp_organization', null ),
	'rwp_contact_page_id'     => get_option( 'rwp_contact_page_id', null ),
	'rwp_help_page_id'        => get_option( 'rwp_help_page_id', null ),
	'rwp_banners'             => get_option( 'rwp_banners', null ),
	'rwp_sponsorship'         => get_option( 'rwp_sponsorship', null ),
	'rwp_sponsorship_counter' => get_option( 'rwp_sponsorship_counter', null ),
	'rwp_stripe_keys'         => get_option( 'rwp_stripe_keys', null ),
);

// Página "Como ajudar" que já existia antes do teste (ela pode ser só adotada, não criada).
$rwp_help_before = ResgataWP\Core\Links::help_page_id();

$rwp_failures = array();
$rwp_checks   = 0;
$rwp_created  = array();

$rwp_make_user = static function ( string $role ) {
	$user_id = wp_insert_user(
		array(
			'user_login' => 'rwp_teste_' . $role . '_' . wp_generate_password( 6, false ),
			'user_pass'  => wp_generate_password(),
			'role'       => $role,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		WP_CLI::error( $user_id->get_error_message() );
	}

	return $user_id;
};

$rwp_users = array(
	'visitante'      => 0,
	'assinante'      => $rwp_make_user( 'subscriber' ),
	'protetor admin' => $rwp_make_user( Capabilities::PANEL_ROLE ),
	'administrador'  => $rwp_make_user( 'administrator' ),
);

// Limpeza: roda no fim e também se o teste for interrompido por um erro, para não deixar
// usuários, animais e opções de teste no site.
$rwp_cleanup = static function () use ( &$rwp_created, &$rwp_users, $rwp_saved_options, $rwp_help_before ): void {
	static $rwp_done = false;

	if ( $rwp_done ) {
		return;
	}

	$rwp_done = true;

	$rwp_new_contact_page = (int) get_option( 'rwp_contact_page_id', 0 );
	if ( $rwp_new_contact_page && (int) $rwp_saved_options['rwp_contact_page_id'] !== $rwp_new_contact_page ) {
		$rwp_created[] = $rwp_new_contact_page; // Página de contato criada durante o teste.
	}
	$rwp_new_help_page = (int) get_option( 'rwp_help_page_id', 0 );
	if ( $rwp_new_help_page && (int) $rwp_saved_options['rwp_help_page_id'] !== $rwp_new_help_page && $rwp_help_before !== $rwp_new_help_page ) {
		$rwp_created[] = $rwp_new_help_page; // Página "Como ajudar" criada durante o teste.
	}
	foreach ( $rwp_saved_options as $rwp_option => $rwp_value ) {
		if ( null === $rwp_value ) {
			delete_option( $rwp_option );
		} else {
			update_option( $rwp_option, $rwp_value, false );
		}
	}

	wp_set_current_user( 0 );
	foreach ( array_unique( array_filter( $rwp_created ) ) as $rwp_post_id ) {
		wp_delete_post( (int) $rwp_post_id, true );
	}
	foreach ( array_filter( $rwp_users ) as $rwp_user_id ) {
		wp_delete_user( $rwp_user_id );
	}
};

register_shutdown_function( $rwp_cleanup );

$rwp_species = get_terms(
	array(
		'taxonomy'   => 'rwp_species',
		'hide_empty' => false,
		'fields'     => 'slugs',
		'number'     => 1,
	)
);

$rwp_new_animal = static function () use ( &$rwp_created ): int {
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'rwp_animal',
			'post_status' => 'draft',
			'post_title'  => 'Animal de teste',
		)
	);

	$rwp_created[] = $post_id;

	return (int) $post_id;
};

$rwp_new_request = static function () use ( &$rwp_created, $rwp_new_animal ): int {
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'rwp_adoption',
			'post_status' => 'private',
			'post_title'  => 'Pessoa de teste',
		)
	);

	update_post_meta( $post_id, 'rwp_adoption_status', 'new' );
	update_post_meta( $post_id, 'rwp_adoption_animal_id', $rwp_new_animal() );
	update_post_meta(
		$post_id,
		'rwp_adoption_answers',
		array(
			'name'     => 'Pessoa de teste',
			'whatsapp' => '11987654321',
		)
	);

	$rwp_created[] = $post_id;

	return (int) $post_id;
};

$rwp_assert = static function ( bool $ok, string $message ) use ( &$rwp_failures, &$rwp_checks ): void {
	++$rwp_checks;
	if ( ! $ok ) {
		$rwp_failures[] = $message;
	}
};

// As rotas de planos falariam com a Stripe: aqui ela é substituída por respostas fixas.
$rwp_fake_ids = 0;
add_filter(
	'rwp_sponsorship_pre_stripe',
	static function ( $result, string $operation ) use ( &$rwp_fake_ids ) {
		++$rwp_fake_ids;

		switch ( $operation ) {
			case 'create_plan':
				return array(
					'product_id' => 'prod_teste' . $rwp_fake_ids,
					'price_id'   => 'price_teste' . $rwp_fake_ids,
				);
			case 'replace_price':
				return 'price_teste' . $rwp_fake_ids;
			case 'sync_portal':
				return 'bpc_teste';
			case 'permissions':
				return array_fill_keys( ResgataWP\Modules\Sponsorship\Gateway::PERMISSIONS, true );
			default:
				return true;
		}
	},
	10,
	2
);

$rwp_new_plan = static function ( string $name = 'Plano de teste' ): string {
	// Apaga os planos de teste anteriores, para não bater no limite de planos.
	foreach ( ResgataWP\Modules\Sponsorship\Plans::all() as $plan ) {
		if ( str_starts_with( (string) $plan['price_id'], 'price_teste' ) ) {
			ResgataWP\Modules\Sponsorship\Plans::remove( (string) $plan['id'] );
		}
	}

	$plan = ResgataWP\Modules\Sponsorship\PlanService::create(
		array(
			'name'   => $name,
			'amount' => 1500,
		)
	);

	return is_wp_error( $plan ) ? '' : (string) $plan['id'];
};

$rwp_server = rest_get_server();
$rwp_prefix = '/' . BaseController::REST_NAMESPACE;
$rwp_routes = 0;

foreach ( $rwp_server->get_routes( BaseController::REST_NAMESPACE ) as $rwp_route => $rwp_handlers ) {
	if ( $rwp_prefix === $rwp_route ) {
		continue; // Índice do namespace (gerado pelo WordPress, só lista as rotas).
	}

	// Única rota pública do namespace: quem chama é a Stripe, e a autenticação é a assinatura do evento.
	if ( $rwp_prefix . '/stripe/webhook' === $rwp_route ) {
		wp_set_current_user( 0 );
		$rwp_status = $rwp_server->dispatch( new WP_REST_Request( 'POST', $rwp_route ) )->get_status();
		$rwp_assert( in_array( $rwp_status, array( 400, 503 ), true ), "POST $rwp_route sem assinatura: HTTP $rwp_status (esperado 400 ou 503)" );
		continue;
	}

	++$rwp_routes;

	foreach ( $rwp_handlers as $rwp_handler ) {
		$rwp_assert( isset( $rwp_handler['permission_callback'] ) && is_callable( $rwp_handler['permission_callback'] ), "$rwp_route: rota sem permission_callback" );

		foreach ( array_keys( array_filter( $rwp_handler['methods'] ) ) as $rwp_method ) {
			foreach ( $rwp_users as $rwp_label => $rwp_user_id ) {
				wp_set_current_user( $rwp_user_id );

				// Cada chamada usa um animal novo, para o DELETE de um perfil não afetar o seguinte.
				if ( str_contains( $rwp_route, '/sponsorship/plans' ) ) {
					$rwp_item_id = $rwp_new_plan();
				} else {
					$rwp_item_id = str_contains( $rwp_route, '/adoptions' ) ? $rwp_new_request() : $rwp_new_animal();
				}
				$rwp_path    = preg_replace( '/\(\?P<id>[^)]+\)/', (string) $rwp_item_id, $rwp_route );
				$rwp_request = new WP_REST_Request( $rwp_method, $rwp_path );
				$rwp_request->set_header( 'content-type', 'application/json' );

				if ( in_array( $rwp_method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
					// Nas configurações o corpo vai vazio, para não alterar os dados cadastrados no site.
					$rwp_body = str_starts_with( $rwp_route, $rwp_prefix . '/settings' ) || str_contains( $rwp_route, '/adoptions' ) || str_contains( $rwp_route, '/sponsor' )
						? new stdClass()
						: array(
							'name'    => 'Animal de teste',
							'species' => $rwp_species[0] ?? '',
						);

					if ( $rwp_prefix . '/sponsorship/plans' === $rwp_route ) {
						$rwp_body = array(
							'name'   => 'Plano de teste',
							'amount' => 2000,
						);
					}

					$rwp_request->set_body( wp_json_encode( $rwp_body ) );
				}

				$rwp_response = $rwp_server->dispatch( $rwp_request );
				$rwp_status   = $rwp_response->get_status();
				$rwp_data     = $rwp_response->get_data();

				if ( in_array( $rwp_status, array( 200, 201 ), true ) && is_array( $rwp_data ) && isset( $rwp_data['id'] ) ) {
					$rwp_created[] = (int) $rwp_data['id'];
				}

				$rwp_where = "$rwp_method $rwp_route como $rwp_label: HTTP $rwp_status";

				if ( 'visitante' === $rwp_label ) {
					$rwp_assert( 401 === $rwp_status, "$rwp_where (esperado 401)" );
				} elseif ( 'assinante' === $rwp_label ) {
					$rwp_assert( 403 === $rwp_status, "$rwp_where (esperado 403)" );
				} elseif ( $rwp_prefix . '/sponsorship/keys' === $rwp_route && 'administrador' !== $rwp_label ) {
					// Trocar a chave da Stripe é trocar a conta que recebe: só o Administrador.
					$rwp_assert( 403 === $rwp_status, "$rwp_where (esperado 403)" );
				} else {
					$rwp_assert( $rwp_status >= 200 && $rwp_status < 300, "$rwp_where (esperado 2xx)" );
				}
			}
		}
	}
}

// Casos específicos: ID que não é um animal e HTML perigoso na história.
wp_set_current_user( $rwp_users['protetor admin'] );

$rwp_page_id   = wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_status' => 'draft',
		'post_title'  => 'Página de teste',
	)
);
$rwp_created[] = $rwp_page_id;

foreach ( array( 'GET', 'PUT', 'DELETE' ) as $rwp_method ) {
	$rwp_response = $rwp_server->dispatch( new WP_REST_Request( $rwp_method, "$rwp_prefix/animals/$rwp_page_id" ) );
	$rwp_assert( 403 === $rwp_response->get_status(), "$rwp_method em um post que não é animal: HTTP " . $rwp_response->get_status() . ' (esperado 403)' );
}

$rwp_animal_id = $rwp_new_animal();
$rwp_request   = new WP_REST_Request( 'PUT', "$rwp_prefix/animals/$rwp_animal_id" );
$rwp_request->set_header( 'content-type', 'application/json' );
$rwp_request->set_body(
	wp_json_encode(
		array(
			'story'     => '<p>Oi</p><script>alert(1)</script><img src=x onerror=alert(1)>',
			'status'    => 'reserved',
			'color'     => '<b>Preto</b>',
			'tagline'   => '<i>Um amigão</i>',
			'intro'     => "Linha 1\n<script>alert(1)</script>Linha 2",
			'weight'    => 18.46,
			'microchip' => 'yes',
		)
	)
);
$rwp_data = $rwp_server->dispatch( $rwp_request )->get_data();
$rwp_assert( is_array( $rwp_data ) && 'Um amigão' === $rwp_data['tagline'] && ! str_contains( $rwp_data['intro'], '<' ) && str_contains( $rwp_data['intro'], "\n" ), 'Frase e resumo deveriam ficar sem HTML, e o resumo manter as quebras de linha' );
$rwp_assert( is_array( $rwp_data ) && 18.5 === $rwp_data['weight'] && 'yes' === $rwp_data['microchip'], 'O peso deveria ser arredondado para uma casa e o microchip gravado' );
$rwp_assert( is_array( $rwp_data ) && ! str_contains( $rwp_data['story'], '<script' ) && ! str_contains( $rwp_data['story'], 'onerror' ), 'A história deveria ter o HTML perigoso removido' );
$rwp_assert( is_array( $rwp_data ) && 'Preto' === $rwp_data['color'] && 'reserved' === $rwp_data['status'], 'Campos de texto deveriam ser limpos e a situação gravada' );

$rwp_request = new WP_REST_Request( 'PUT', "$rwp_prefix/animals/$rwp_animal_id" );
$rwp_request->set_header( 'content-type', 'application/json' );
$rwp_request->set_body( wp_json_encode( array( 'status' => 'inexistente' ) ) );
$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'Situação inválida deveria ser recusada com 400' );

foreach ( array( array( 'weight' => 500 ), array( 'tagline' => str_repeat( 'a', 121 ) ), array( 'vaccinated' => 'talvez' ) ) as $rwp_invalid ) {
	$rwp_request = new WP_REST_Request( 'PUT', "$rwp_prefix/animals/$rwp_animal_id" );
	$rwp_request->set_header( 'content-type', 'application/json' );
	$rwp_request->set_body( wp_json_encode( $rwp_invalid ) );
	$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'Valor inválido em "' . key( $rwp_invalid ) . '" deveria ser recusado com 400' );
}

$rwp_request = new WP_REST_Request( 'GET', "$rwp_prefix/animals" );
$rwp_request->set_query_params( array( 'per_page' => 1000 ) );
$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'per_page fora da lista deveria ser recusado com 400' );

// Filtros da listagem.
$rwp_request = new WP_REST_Request( 'GET', "$rwp_prefix/animals" );
$rwp_request->set_query_params(
	array(
		'status'      => 'reserved',
		'post_status' => 'draft',
		'search'      => 'Animal de teste',
		'per_page'    => 100,
	)
);
$rwp_data = $rwp_server->dispatch( $rwp_request )->get_data();
$rwp_ids  = wp_list_pluck( $rwp_data['items'] ?? array(), 'id' );
$rwp_assert( in_array( $rwp_animal_id, $rwp_ids, true ), 'O filtro deveria encontrar o animal de teste (reservado, rascunho)' );
$rwp_assert( array( 'reserved' ) === array_values( array_unique( wp_list_pluck( $rwp_data['items'] ?? array(), 'status' ) ) ), 'O filtro por situação deveria trazer só animais reservados' );

$rwp_request = new WP_REST_Request( 'GET', "$rwp_prefix/animals" );
$rwp_request->set_query_params( array( 'status' => 'reserved,inexistente' ) );
$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'Filtro com situação inválida deveria ser recusado com 400' );

$rwp_request = new WP_REST_Request( 'GET', "$rwp_prefix/animals" );
$rwp_request->set_query_params( array( 'rescue_from' => "2026-01-01' OR 1=1" ) );
$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'Data inválida no filtro deveria ser recusada com 400' );

// Banners: textos limpos, links perigosos recusados, limite de itens e imagem inexistente ignorada.
$rwp_put_banners = static function ( array $banners ) use ( $rwp_server, $rwp_prefix ) {
	$request = new WP_REST_Request( 'PUT', "$rwp_prefix/banners" );
	$request->set_header( 'content-type', 'application/json' );
	$request->set_body( wp_json_encode( array( 'banners' => $banners ) ) );

	return $rwp_server->dispatch( $request );
};

$rwp_response = $rwp_put_banners(
	array(
		array(
			'title'      => '<b>Juntos</b><script>alert(1)</script>',
			'button_url' => '/animais/',
			'image_id'   => 99999999,
			'active'     => true,
		),
	)
);
$rwp_data     = $rwp_response->get_data();
$rwp_banner   = $rwp_data['banners'][0] ?? array();
$rwp_assert( 200 === $rwp_response->get_status() && 'Juntos' === ( $rwp_banner['title'] ?? '' ) && '/animais/' === ( $rwp_banner['button_url'] ?? '' ) && 0 === ( $rwp_banner['image_id'] ?? -1 ), 'Banner deveria ser gravado sem HTML, com o caminho do site e sem a imagem inexistente' );

foreach ( array( 'javascript:alert(1)', '//outro-site.example/x', 'data:text/html,x' ) as $rwp_bad_url ) {
	$rwp_assert(
		400 === $rwp_put_banners(
			array(
				array(
					'title'      => 'x',
					'button_url' => $rwp_bad_url,
				),
			)
		)->get_status(),
		"Link de banner \"$rwp_bad_url\" deveria ser recusado com 400"
	);
}

$rwp_assert( 400 === $rwp_put_banners( array_fill( 0, 6, array( 'title' => 'x' ) ) )->get_status(), 'Mais banners que o limite deveria ser recusado com 400' );

// Ligações de blocos: o link nunca fica vazio, chave desconhecida cai no início e os textos saem sem HTML.
$rwp_assert( home_url( '/' ) === ResgataWP\Core\Bindings::link_value( array( 'key' => 'inexistente' ) ), 'Ligação de link desconhecida deveria cair na página inicial' );
$rwp_assert( '' !== ResgataWP\Core\Bindings::link_value( array( 'key' => 'help' ) ) && '' !== ResgataWP\Core\Bindings::link_value( array( 'key' => 'sponsorship' ) ), 'Ligações de link deveriam sempre devolver um endereço' );
$rwp_assert( null === ResgataWP\Core\Bindings::organization_value( array( 'key' => 'adoption_email' ) ), 'A ligação de dados da protetora não deveria expor campos fora da lista (ex.: e-mail interno)' );
$rwp_assert( ! str_contains( (string) ResgataWP\Core\Bindings::organization_value( array( 'key' => 'copyright' ) ), '<' ), 'O texto de direitos deveria sair sem HTML' );
$rwp_assert( block_has_support( WP_Block_Type_Registry::get_instance()->get_registered( 'resgata-wp/banners' ), 'align' ) && isset( WP_Block_Type_Registry::get_instance()->get_registered( 'resgata-wp/banners' )->attributes['align'] ), 'Os blocos do plugin deveriam ser registrados antes dos atributos de suporte (senão a prévia no editor falha)' );

// Início: os números batem com os cadastros e cada área só vem para quem tem acesso.
$rwp_summary        = $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/dashboard" ) )->get_data();
$rwp_counts         = wp_count_posts( 'rwp_animal' );
$rwp_expected_total = (int) $rwp_counts->publish + (int) $rwp_counts->draft;
$rwp_assert( ( $rwp_summary['animals']['total'] ?? -1 ) === $rwp_expected_total, 'O total de animais do Início deveria ser a soma de publicados e rascunhos' );
$rwp_assert( array_sum( $rwp_summary['animals']['by_status'] ?? array() ) === ( $rwp_summary['animals']['total'] ?? -1 ), 'A soma por situação deveria bater com o total de animais' );
$rwp_expected_drafts = (int) $rwp_counts->draft;
$rwp_assert( ( $rwp_summary['animals']['drafts'] ?? -1 ) === $rwp_expected_drafts, 'O Início deveria contar os rascunhos' );

$rwp_editor_id = $rwp_make_user( 'editor' );
wp_set_current_user( $rwp_editor_id );
$rwp_summary = $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/dashboard" ) )->get_data();
$rwp_assert( is_array( $rwp_summary['animals'] ?? null ) && null === $rwp_summary['adoptions'] && null === $rwp_summary['sponsorship'], 'Editor comum deveria ver só os números de animais no Início' );
wp_delete_user( $rwp_editor_id );
wp_set_current_user( $rwp_users['protetor admin'] );

// Planos: textos limpos, valor fora do limite recusado, mudança de valor guarda o preço antigo, plano com padrinho não é excluído.
$rwp_plan_request = static function ( string $method, string $path, array $body = array() ) use ( $rwp_server, $rwp_prefix ) {
	$request = new WP_REST_Request( $method, "$rwp_prefix/sponsorship/plans$path" );
	$request->set_header( 'content-type', 'application/json' );
	$request->set_body( wp_json_encode( $body ? $body : new stdClass() ) );

	return $rwp_server->dispatch( $request );
};

$rwp_assert( 400 === $rwp_plan_request( 'POST', '', array( 'amount' => 50 ) )->get_status(), 'Plano abaixo do valor mínimo deveria ser recusado com 400' );
$rwp_assert( 400 === $rwp_plan_request( 'POST', '', array( 'name' => 'Sem valor' ) )->get_status(), 'Plano sem valor deveria ser recusado com 400' );
$rwp_assert( 404 === $rwp_plan_request( 'PUT', '/prod_inexistente', array( 'name' => 'x' ) )->get_status(), 'Plano inexistente deveria responder 404' );

$rwp_plan_id = $rwp_new_plan( 'Amigo' );
$rwp_plan    = ResgataWP\Modules\Sponsorship\Plans::get( $rwp_plan_id );
$rwp_old     = (string) $rwp_plan['price_id'];

$rwp_response = $rwp_plan_request(
	'PUT',
	"/$rwp_plan_id",
	array(
		'name'     => '<b>Protetor</b><script>x</script>',
		'amount'   => 3000,
		'benefits' => array( '<i>Ração</i>', '', 'Vacinas' ),
		'featured' => true,
	)
);
$rwp_plan     = ResgataWP\Modules\Sponsorship\Plans::get( $rwp_plan_id );
$rwp_assert( 200 === $rwp_response->get_status() && 'Protetor' === $rwp_plan['name'] && array( 'Ração', 'Vacinas' ) === $rwp_plan['benefits'], 'Nome e benefícios do plano deveriam ser gravados sem HTML e sem linhas vazias' );
$rwp_assert( 3000 === $rwp_plan['amount'] && $rwp_old !== $rwp_plan['price_id'] && array( $rwp_old ) === wp_list_pluck( $rwp_plan['old_prices'], 'price_id' ), 'Mudar o valor deveria criar um preço novo e guardar o antigo' );

$rwp_found = ResgataWP\Modules\Sponsorship\Plans::find( $rwp_old );
$rwp_assert( $rwp_found && 1500 === $rwp_found['amount'] && 'Protetor' === $rwp_found['name'], 'Quem assinou o preço antigo deveria continuar reconhecido, com o valor antigo' );
$rwp_assert( null === ResgataWP\Modules\Sponsorship\Plans::find_for_sale( $rwp_old ), 'O preço antigo não deveria mais ser vendido no site' );

$rwp_plan_request( 'PUT', "/$rwp_plan_id", array( 'active' => false ) );
$rwp_assert( null === ResgataWP\Modules\Sponsorship\Plans::find_for_sale( (string) $rwp_plan['price_id'] ) && null !== ResgataWP\Modules\Sponsorship\Plans::find( (string) $rwp_plan['price_id'] ), 'Plano escondido não deveria ser vendido, mas deveria continuar reconhecido' );

$rwp_sponsor_customer = 'cus_teste_' . wp_generate_password( 8, false );
ResgataWP\Modules\Sponsorship\Sponsors::upsert(
	$rwp_sponsor_customer,
	array(
		'email'    => 'padrinho-teste@example.org',
		'price_id' => $rwp_old,
		'status'   => 'active',
	)
);
$rwp_assert( 409 === $rwp_plan_request( 'DELETE', "/$rwp_plan_id" )->get_status(), 'Plano com padrinho não deveria ser excluído (409)' );

$rwp_request = new WP_REST_Request( 'GET', "$rwp_prefix/sponsors" );
$rwp_request->set_query_params( array( 'search' => 'padrinho-teste@example.org' ) );
$rwp_row = $rwp_server->dispatch( $rwp_request )->get_data()['items'][0] ?? array();
$rwp_assert( 'Protetor' === ( $rwp_row['plan'] ?? '' ) && str_contains( (string) ( $rwp_row['plan_amount'] ?? '' ), '15' ), 'A lista de padrinhos deveria mostrar o nome do plano e o valor que a pessoa paga' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Limpeza do padrinho criado pelo teste.
$wpdb->delete( ResgataWP\Modules\Sponsorship\Sponsors::table(), array( 'customer_id' => $rwp_sponsor_customer ) );
$rwp_assert( 200 === $rwp_plan_request( 'DELETE', "/$rwp_plan_id" )->get_status() && null === ResgataWP\Modules\Sponsorship\Plans::get( $rwp_plan_id ), 'Plano sem padrinho deveria ser excluído' );

// Configurações: o Editor comum não pode alterar; valores são limpos; campo interno não vaza no site.
$rwp_editor_id = $rwp_make_user( 'editor' );
wp_set_current_user( $rwp_editor_id );
$rwp_assert( 403 === $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/settings" ) )->get_status(), 'Editor comum não deveria ler as configurações' );
wp_delete_user( $rwp_editor_id );

wp_set_current_user( $rwp_users['protetor admin'] );
$rwp_request = new WP_REST_Request( 'PUT', "$rwp_prefix/settings" );
$rwp_request->set_header( 'content-type', 'application/json' );
$rwp_request->set_body(
	wp_json_encode(
		array(
			'name'           => '<b>Protetora</b> Teste',
			'whatsapp'       => '(11) 91234-5678',
			'instagram'      => '@protetora.teste',
			'facebook'       => 'javascript:alert(1)',
			'adoption_email' => 'interno@example.org',
			'pix_key'        => 'pix@example.org',
		)
	)
);
$rwp_data = $rwp_server->dispatch( $rwp_request )->get_data()['settings'] ?? array();
$rwp_assert( 'Protetora Teste' === ( $rwp_data['name'] ?? '' ), 'O nome deveria ter o HTML removido' );
$rwp_assert( '11912345678' === ( $rwp_data['whatsapp'] ?? '' ), 'O WhatsApp deveria guardar só os dígitos' );
$rwp_assert( 'https://www.instagram.com/protetora.teste' === ( $rwp_data['instagram'] ?? '' ), 'O @usuario do Instagram deveria virar link' );
$rwp_assert( '' === ( $rwp_data['facebook'] ?? null ), 'Link com javascript: deveria ser recusado' );

$rwp_html = ResgataWP\Modules\Settings\ContactBlock::render();
$rwp_assert( str_contains( $rwp_html, 'wa.me/5511912345678' ) && str_contains( $rwp_html, 'pix@example.org' ), 'O bloco de contato deveria mostrar WhatsApp e Pix' );
$rwp_assert( ! str_contains( $rwp_html, 'interno@example.org' ), 'O e-mail interno de adoção não pode aparecer no site' );

$rwp_request = new WP_REST_Request( 'PUT', "$rwp_prefix/settings" );
$rwp_request->set_header( 'content-type', 'application/json' );
$rwp_request->set_body( wp_json_encode( array( 'email' => 'nao-e-email' ) ) );
$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'E-mail inválido deveria ser recusado com 400' );

// Pedidos de adoção: Editor comum não acessa; o formulário valida; o pedido não vaza na REST do WordPress.
$rwp_editor_id = $rwp_make_user( 'editor' );
wp_set_current_user( $rwp_editor_id );
$rwp_request_id = $rwp_new_request();
$rwp_assert( 403 === $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/adoptions" ) )->get_status(), 'Editor comum não deveria listar pedidos de adoção' );
$rwp_assert( 403 === $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/adoptions/$rwp_request_id" ) )->get_status(), 'Editor comum não deveria ler um pedido de adoção' );
wp_delete_user( $rwp_editor_id );

wp_set_current_user( $rwp_users['protetor admin'] );
$rwp_assert( 403 === $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/adoptions/$rwp_animal_id" ) )->get_status(), 'ID de um animal não deveria abrir como pedido de adoção' );
$rwp_assert( 404 === $rwp_server->dispatch( new WP_REST_Request( 'GET', '/wp/v2/rwp_adoption' ) )->get_status(), 'Pedidos de adoção não podem existir na REST padrão do WordPress' );

// Padrinhos: Editor comum não acessa; as chaves da Stripe nunca aparecem na resposta.
if ( ResgataWP\Core\Modules::is_enabled( 'sponsorship' ) ) {
	$rwp_editor_id = $rwp_make_user( 'editor' );
	wp_set_current_user( $rwp_editor_id );
	$rwp_assert( 403 === $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/sponsors" ) )->get_status(), 'Editor comum não deveria listar os padrinhos' );
	$rwp_assert( 403 === $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/sponsorship" ) )->get_status(), 'Editor comum não deveria ver o resumo do apadrinhamento' );
	wp_delete_user( $rwp_editor_id );

	wp_set_current_user( $rwp_users['protetor admin'] );
	$rwp_json = (string) wp_json_encode( $rwp_server->dispatch( new WP_REST_Request( 'GET', "$rwp_prefix/sponsorship" ) )->get_data() );
	foreach ( array( ResgataWP\Modules\Sponsorship\Keys::secret_key(), ResgataWP\Modules\Sponsorship\Keys::webhook_secret() ) as $rwp_secret_value ) {
		$rwp_assert( '' === $rwp_secret_value || ! str_contains( $rwp_json, $rwp_secret_value ), 'As chaves da Stripe não podem aparecer na API do painel' );
	}

	$rwp_request = new WP_REST_Request( 'PUT', "$rwp_prefix/sponsorship" );
	$rwp_request->set_header( 'content-type', 'application/json' );
	$rwp_request->set_body( wp_json_encode( array( 'adjustment' => 1000000 ) ) );
	$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'Ajuste do contador fora do limite deveria ser recusado com 400' );

	// Chaves da Stripe salvas pelo painel: cifradas no banco, só o Administrador grava.
	$rwp_keys_request = static function ( array $body ) use ( $rwp_server, $rwp_prefix ) {
		$request = new WP_REST_Request( 'PUT', "$rwp_prefix/sponsorship/keys" );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return $rwp_server->dispatch( $request );
	};
	$rwp_fake_key     = 'rk_test_' . wp_generate_password( 40, false );
	$rwp_fake_whsec   = 'whsec_' . wp_generate_password( 32, false );

	$rwp_assert( 403 === $rwp_keys_request( array( 'secret_key' => $rwp_fake_key ) )->get_status(), 'Protetor admin não deveria gravar a chave da Stripe' );
	$rwp_assert( get_option( 'rwp_stripe_keys', null ) === $rwp_saved_options['rwp_stripe_keys'], 'Pedido recusado não deveria mexer nas chaves gravadas' );

	wp_set_current_user( $rwp_users['administrador'] );
	$rwp_keys = ResgataWP\Modules\Sponsorship\Keys::class;

	$rwp_fake_keys = array(
		'secret_key'     => $rwp_fake_key,
		'webhook_secret' => $rwp_fake_whsec,
	);

	foreach ( $rwp_fake_keys as $rwp_field => $rwp_value ) {
		if ( 'constant' === $rwp_keys::source( $rwp_field ) ) {
			$rwp_assert( 409 === $rwp_keys_request( array( $rwp_field => $rwp_value ) )->get_status(), "Chave do wp-config.php ($rwp_field) não deveria ser trocada pelo painel" );
			continue;
		}

		$rwp_assert( 'secret_key' !== $rwp_field || 'rwp_stripe_key_full' === ( $rwp_keys_request( array( $rwp_field => 'sk_test_' . wp_generate_password( 40, false ) ) )->get_data()['code'] ?? '' ), 'A chave secreta completa (sk_) deveria ser recusada' );
		$rwp_assert( 400 === $rwp_keys_request( array( $rwp_field => 'chave errada' ) )->get_status(), "Chave fora do formato ($rwp_field) deveria ser recusada com 400" );

		$rwp_response = $rwp_keys_request( array( $rwp_field => $rwp_value ) );
		$rwp_assert( 200 === $rwp_response->get_status() && $rwp_value === $rwp_keys::stored( $rwp_field ), "O Administrador deveria conseguir gravar a chave ($rwp_field)" );
		$rwp_assert( ! str_contains( (string) wp_json_encode( $rwp_response->get_data() ), $rwp_value ), "A chave gravada ($rwp_field) não pode voltar na resposta" );
	}

	// Gravação direta: vale também quando o ambiente de teste tem as chaves no wp-config.php.
	$rwp_assert( $rwp_keys::store( 'secret_key', $rwp_fake_key ) && $rwp_fake_key === $rwp_keys::stored( 'secret_key' ), 'A chave gravada deveria ser lida de volta' );
	$rwp_raw = get_option( 'rwp_stripe_keys' );
	$rwp_assert( is_array( $rwp_raw ) && ! str_contains( (string) wp_json_encode( $rwp_raw ), $rwp_fake_key ) && ! str_contains( (string) wp_json_encode( $rwp_raw ), 'rk_test_' ), 'A chave deveria estar cifrada no banco' );
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Confere a coluna de carregamento automático, que não tem função própria.
	$rwp_assert( 'off' === $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", 'rwp_stripe_keys' ) ), 'A option das chaves não deveria ser carregada automaticamente' );

	$rwp_first = $rwp_raw['secret_key'] ?? '';
	$rwp_keys::store( 'secret_key', $rwp_fake_key );
	$rwp_assert( ( get_option( 'rwp_stripe_keys' )['secret_key'] ?? '' ) !== $rwp_first, 'Cifrar a mesma chave duas vezes deveria dar resultados diferentes' );

	update_option( 'rwp_stripe_keys', array( 'secret_key' => substr( $rwp_first, 0, -4 ) . 'AAAA' ), false );
	$rwp_keys::store( 'webhook_secret', '' ); // Só para descartar o que foi lido antes.
	$rwp_assert( '' === $rwp_keys::stored( 'secret_key' ) && array( 'secret_key' ) === $rwp_keys::unreadable(), 'Chave adulterada no banco deveria ser tratada como ilegível' );

	$rwp_keys::store( 'secret_key', '' );
	$rwp_assert( false === get_option( 'rwp_stripe_keys' ), 'Apagar a última chave deveria apagar a option' );

	$rwp_assert( null === ResgataWP\Core\Encryption::decrypt( 'texto que não foi cifrado' ), 'Texto que não foi cifrado pelo plugin não deveria ser decifrado' );

	wp_set_current_user( $rwp_users['protetor admin'] );

	$rwp_request = new WP_REST_Request( 'GET', "$rwp_prefix/sponsors" );
	$rwp_request->set_query_params( array( 'status' => "active' OR 1=1" ) );
	$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'Filtro de padrinhos com situação inválida deveria ser recusado com 400' );
}

wp_set_current_user( 0 );
$rwp_result = ResgataWP\Modules\Adoptions\Submission::create( array( 'name' => 'Sem dados' ) );
$rwp_assert( is_wp_error( $rwp_result ) && isset( $rwp_result->get_error_data()['consent'], $rwp_result->get_error_data()['animal_id'], $rwp_result->get_error_data()['whatsapp'] ), 'Pedido incompleto deveria ser recusado (animal, consentimento e WhatsApp)' );

$rwp_adoptable = $rwp_new_animal();
wp_update_post(
	array(
		'ID'          => $rwp_adoptable,
		'post_status' => 'publish',
	)
);
update_post_meta( $rwp_adoptable, 'rwp_status', 'available' );
$rwp_valid  = array(
	'animal_id'        => $rwp_adoptable,
	'consent'          => '1',
	'name'             => 'Maria <script>alert(1)</script>',
	'whatsapp'         => '(11) 98765-4321',
	'city'             => 'São Paulo',
	'housing'          => 'house',
	'safe_home'        => 'yes',
	'household_agrees' => 'yes',
	'reason'           => 'Quero adotar.',
	'adult'            => '1',
);
$rwp_result = ResgataWP\Modules\Adoptions\Submission::create( $rwp_valid );
$rwp_assert( is_int( $rwp_result ) && 'private' === get_post_status( $rwp_result ), 'Pedido válido deveria ser gravado como privado' );
if ( is_int( $rwp_result ) ) {
	$rwp_created[] = $rwp_result;
	$rwp_assert( ! str_contains( (string) get_post_field( 'post_title', $rwp_result ), '<' ), 'O nome do pedido deveria ter o HTML removido' );
	$rwp_consent = get_post_meta( $rwp_result, 'rwp_adoption_consent', true );
	$rwp_assert( is_array( $rwp_consent ) && ! empty( $rwp_consent['at'] ) && ! empty( $rwp_consent['text'] ), 'O consentimento (data e texto) deveria ser guardado com o pedido' );
}

update_post_meta( $rwp_adoptable, 'rwp_status', 'adopted' );
$rwp_assert( is_wp_error( ResgataWP\Modules\Adoptions\Submission::create( $rwp_valid ) ), 'Animal já adotado não deveria aceitar pedido' );

wp_set_current_user( $rwp_users['protetor admin'] );

// O DELETE só move para a lixeira.
$rwp_server->dispatch( new WP_REST_Request( 'DELETE', "$rwp_prefix/animals/$rwp_animal_id" ) );
$rwp_assert( 'trash' === get_post_status( $rwp_animal_id ), 'Excluir deveria mover para a lixeira, não apagar' );

// Limpeza (definida no começo do arquivo).
$rwp_cleanup();

if ( 0 === $rwp_routes ) {
	WP_CLI::error( 'Nenhuma rota encontrada em ' . BaseController::REST_NAMESPACE . '.' );
}

if ( $rwp_failures ) {
	foreach ( $rwp_failures as $rwp_failure ) {
		WP_CLI::warning( $rwp_failure );
	}
	WP_CLI::error( sprintf( '%d de %d verificações falharam.', count( $rwp_failures ), $rwp_checks ) );
}

WP_CLI::success( sprintf( '%d verificações em %d rotas: tudo certo.', $rwp_checks, $rwp_routes ) );
