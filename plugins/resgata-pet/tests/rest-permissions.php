<?php
/**
 * Teste de permissões da API do painel (`resgata-pet/v1`).
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
 * @package ResgataPet
 */

use ResgataPet\Core\Capabilities;
use ResgataPet\Modules\Panel\Rest\BaseController;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/user.php';

// Nenhum e-mail sai durante o teste.
add_filter( 'pre_wp_mail', '__return_true' );

// Opções que as rotas de configurações podem alterar: guardadas aqui e devolvidas no final.
$resgata_saved_options = array(
	'resgata_organization'        => get_option( 'resgata_organization', null ),
	'resgata_contact_page_id'     => get_option( 'resgata_contact_page_id', null ),
	'resgata_help_page_id'        => get_option( 'resgata_help_page_id', null ),
	'resgata_banners'             => get_option( 'resgata_banners', null ),
	'resgata_sponsorship'         => get_option( 'resgata_sponsorship', null ),
	'resgata_sponsorship_counter' => get_option( 'resgata_sponsorship_counter', null ),
	'resgata_stripe_keys'         => get_option( 'resgata_stripe_keys', null ),
);

// Página "Como ajudar" que já existia antes do teste (ela pode ser só adotada, não criada).
$resgata_help_before = ResgataPet\Core\Links::help_page_id();

$resgata_failures = array();
$resgata_checks   = 0;
$resgata_created  = array();

$resgata_make_user = static function ( string $role ) {
	$user_id = wp_insert_user(
		array(
			'user_login' => 'resgata_teste_' . $role . '_' . wp_generate_password( 6, false ),
			'user_pass'  => wp_generate_password(),
			'role'       => $role,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		WP_CLI::error( $user_id->get_error_message() );
	}

	return $user_id;
};

$resgata_users = array(
	'visitante'      => 0,
	'assinante'      => $resgata_make_user( 'subscriber' ),
	'protetor admin' => $resgata_make_user( Capabilities::PANEL_ROLE ),
	'administrador'  => $resgata_make_user( 'administrator' ),
);

// Limpeza: roda no fim e também se o teste for interrompido por um erro, para não deixar
// usuários, animais e opções de teste no site.
$resgata_cleanup = static function () use ( &$resgata_created, &$resgata_users, $resgata_saved_options, $resgata_help_before ): void {
	static $resgata_done = false;

	if ( $resgata_done ) {
		return;
	}

	$resgata_done = true;

	$resgata_new_contact_page = (int) get_option( 'resgata_contact_page_id', 0 );
	if ( $resgata_new_contact_page && (int) $resgata_saved_options['resgata_contact_page_id'] !== $resgata_new_contact_page ) {
		$resgata_created[] = $resgata_new_contact_page; // Página de contato criada durante o teste.
	}
	$resgata_new_help_page = (int) get_option( 'resgata_help_page_id', 0 );
	if ( $resgata_new_help_page && (int) $resgata_saved_options['resgata_help_page_id'] !== $resgata_new_help_page && $resgata_help_before !== $resgata_new_help_page ) {
		$resgata_created[] = $resgata_new_help_page; // Página "Como ajudar" criada durante o teste.
	}
	$resgata_new_thanks_page = (int) ( get_option( 'resgata_organization', array() )['sponsorship_thanks_page'] ?? 0 );
	if ( $resgata_new_thanks_page && (int) ( $resgata_saved_options['resgata_organization']['sponsorship_thanks_page'] ?? 0 ) !== $resgata_new_thanks_page ) {
		$resgata_created[] = $resgata_new_thanks_page; // Página de agradecimento criada durante o teste.
	}
	foreach ( $resgata_saved_options as $resgata_option => $resgata_value ) {
		if ( null === $resgata_value ) {
			delete_option( $resgata_option );
		} else {
			update_option( $resgata_option, $resgata_value, false );
		}
	}

	wp_set_current_user( 0 );
	foreach ( array_unique( array_filter( $resgata_created ) ) as $resgata_post_id ) {
		wp_delete_post( (int) $resgata_post_id, true );
	}
	foreach ( array_filter( $resgata_users ) as $resgata_user_id ) {
		wp_delete_user( $resgata_user_id );
	}
};

register_shutdown_function( $resgata_cleanup );

$resgata_species = get_terms(
	array(
		'taxonomy'   => 'resgata_species',
		'hide_empty' => false,
		'fields'     => 'slugs',
		'number'     => 1,
	)
);

$resgata_new_animal = static function () use ( &$resgata_created ): int {
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'resgata_animal',
			'post_status' => 'draft',
			'post_title'  => 'Animal de teste',
		)
	);

	$resgata_created[] = $post_id;

	return (int) $post_id;
};

$resgata_new_request = static function () use ( &$resgata_created, $resgata_new_animal ): int {
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'resgata_adoption',
			'post_status' => 'private',
			'post_title'  => 'Pessoa de teste',
		)
	);

	update_post_meta( $post_id, 'resgata_adoption_status', 'new' );
	update_post_meta( $post_id, 'resgata_adoption_animal_id', $resgata_new_animal() );
	update_post_meta(
		$post_id,
		'resgata_adoption_answers',
		array(
			'name'     => 'Pessoa de teste',
			'whatsapp' => '11987654321',
		)
	);

	$resgata_created[] = $post_id;

	return (int) $post_id;
};

$resgata_assert = static function ( bool $ok, string $message ) use ( &$resgata_failures, &$resgata_checks ): void {
	++$resgata_checks;
	if ( ! $ok ) {
		$resgata_failures[] = $message;
	}
};

// As rotas de planos falariam com a Stripe: aqui ela é substituída por respostas fixas.
$resgata_fake_ids = 0;
add_filter(
	'resgata_sponsorship_pre_stripe',
	static function ( $result, string $operation ) use ( &$resgata_fake_ids ) {
		++$resgata_fake_ids;

		switch ( $operation ) {
			case 'create_plan':
				return array(
					'product_id' => 'prod_teste' . $resgata_fake_ids,
					'price_id'   => 'price_teste' . $resgata_fake_ids,
				);
			case 'replace_price':
				return 'price_teste' . $resgata_fake_ids;
			case 'sync_portal':
				return 'bpc_teste';
			case 'permissions':
				return array_fill_keys( ResgataPet\Modules\Sponsorship\Gateway::PERMISSIONS, true );
			default:
				return true;
		}
	},
	10,
	2
);

$resgata_new_plan = static function ( string $name = 'Plano de teste' ): string {
	// Apaga os planos de teste anteriores, para não bater no limite de planos.
	foreach ( ResgataPet\Modules\Sponsorship\Plans::all() as $plan ) {
		if ( str_starts_with( (string) $plan['price_id'], 'price_teste' ) ) {
			ResgataPet\Modules\Sponsorship\Plans::remove( (string) $plan['id'] );
		}
	}

	$plan = ResgataPet\Modules\Sponsorship\PlanService::create(
		array(
			'name'   => $name,
			'amount' => 1500,
		)
	);

	return is_wp_error( $plan ) ? '' : (string) $plan['id'];
};

$resgata_server = rest_get_server();
$resgata_prefix = '/' . BaseController::REST_NAMESPACE;
$resgata_routes = 0;

foreach ( $resgata_server->get_routes( BaseController::REST_NAMESPACE ) as $resgata_route => $resgata_handlers ) {
	if ( $resgata_prefix === $resgata_route ) {
		continue; // Índice do namespace (gerado pelo WordPress, só lista as rotas).
	}

	// Única rota pública do namespace: quem chama é a Stripe, e a autenticação é a assinatura do evento.
	if ( $resgata_prefix . '/stripe/webhook' === $resgata_route ) {
		wp_set_current_user( 0 );
		$resgata_status = $resgata_server->dispatch( new WP_REST_Request( 'POST', $resgata_route ) )->get_status();
		$resgata_assert( in_array( $resgata_status, array( 400, 503 ), true ), "POST $resgata_route sem assinatura: HTTP $resgata_status (esperado 400 ou 503)" );
		continue;
	}

	++$resgata_routes;

	foreach ( $resgata_handlers as $resgata_handler ) {
		$resgata_assert( isset( $resgata_handler['permission_callback'] ) && is_callable( $resgata_handler['permission_callback'] ), "$resgata_route: rota sem permission_callback" );

		foreach ( array_keys( array_filter( $resgata_handler['methods'] ) ) as $resgata_method ) {
			foreach ( $resgata_users as $resgata_label => $resgata_user_id ) {
				wp_set_current_user( $resgata_user_id );

				// Cada chamada usa um animal novo, para o DELETE de um perfil não afetar o seguinte.
				if ( str_contains( $resgata_route, '/sponsorship/plans' ) ) {
					$resgata_item_id = $resgata_new_plan();
				} else {
					$resgata_item_id = str_contains( $resgata_route, '/adoptions' ) ? $resgata_new_request() : $resgata_new_animal();
				}
				$resgata_path    = preg_replace( '/\(\?P<id>[^)]+\)/', (string) $resgata_item_id, $resgata_route );
				$resgata_request = new WP_REST_Request( $resgata_method, $resgata_path );
				$resgata_request->set_header( 'content-type', 'application/json' );

				if ( in_array( $resgata_method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
					// Nas configurações o corpo vai vazio, para não alterar os dados cadastrados no site.
					$resgata_body = str_starts_with( $resgata_route, $resgata_prefix . '/settings' ) || str_contains( $resgata_route, '/adoptions' ) || str_contains( $resgata_route, '/sponsor' )
						? new stdClass()
						: array(
							'name'    => 'Animal de teste',
							'species' => $resgata_species[0] ?? '',
						);

					if ( $resgata_prefix . '/sponsorship/plans' === $resgata_route ) {
						$resgata_body = array(
							'name'   => 'Plano de teste',
							'amount' => 2000,
						);
					}

					$resgata_request->set_body( wp_json_encode( $resgata_body ) );
				}

				$resgata_response = $resgata_server->dispatch( $resgata_request );
				$resgata_status   = $resgata_response->get_status();
				$resgata_data     = $resgata_response->get_data();

				if ( in_array( $resgata_status, array( 200, 201 ), true ) && is_array( $resgata_data ) && isset( $resgata_data['id'] ) ) {
					$resgata_created[] = (int) $resgata_data['id'];
				}

				$resgata_where = "$resgata_method $resgata_route como $resgata_label: HTTP $resgata_status";

				if ( 'visitante' === $resgata_label ) {
					$resgata_assert( 401 === $resgata_status, "$resgata_where (esperado 401)" );
				} elseif ( 'assinante' === $resgata_label ) {
					$resgata_assert( 403 === $resgata_status, "$resgata_where (esperado 403)" );
				} elseif ( $resgata_prefix . '/sponsorship/keys' === $resgata_route && 'administrador' !== $resgata_label ) {
					// Trocar a chave da Stripe é trocar a conta que recebe: só o Administrador.
					$resgata_assert( 403 === $resgata_status, "$resgata_where (esperado 403)" );
				} else {
					$resgata_assert( $resgata_status >= 200 && $resgata_status < 300, "$resgata_where (esperado 2xx)" );
				}
			}
		}
	}
}

// Casos específicos: ID que não é um animal e HTML perigoso na história.
wp_set_current_user( $resgata_users['protetor admin'] );

$resgata_page_id   = wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_status' => 'draft',
		'post_title'  => 'Página de teste',
	)
);
$resgata_created[] = $resgata_page_id;

foreach ( array( 'GET', 'PUT', 'DELETE' ) as $resgata_method ) {
	$resgata_response = $resgata_server->dispatch( new WP_REST_Request( $resgata_method, "$resgata_prefix/animals/$resgata_page_id" ) );
	$resgata_assert( 403 === $resgata_response->get_status(), "$resgata_method em um post que não é animal: HTTP " . $resgata_response->get_status() . ' (esperado 403)' );
}

$resgata_animal_id = $resgata_new_animal();
$resgata_request   = new WP_REST_Request( 'PUT', "$resgata_prefix/animals/$resgata_animal_id" );
$resgata_request->set_header( 'content-type', 'application/json' );
$resgata_request->set_body(
	wp_json_encode(
		array(
			'story'      => '<p>Oi</p><script>alert(1)</script><img src=x onerror=alert(1)>',
			'status'     => 'reserved',
			'color'      => '<b>Preto</b>',
			'tagline'    => '<i>Um amigão</i>',
			'intro'      => "Linha 1\n<script>alert(1)</script>Linha 2",
			'weight'     => 18.46,
			'vaccinated' => 'yes',
		)
	)
);
$resgata_data = $resgata_server->dispatch( $resgata_request )->get_data();
$resgata_assert( is_array( $resgata_data ) && 'Um amigão' === $resgata_data['tagline'] && ! str_contains( $resgata_data['intro'], '<' ) && str_contains( $resgata_data['intro'], "\n" ), 'Frase e resumo deveriam ficar sem HTML, e o resumo manter as quebras de linha' );
$resgata_assert( is_array( $resgata_data ) && 18.5 === $resgata_data['weight'] && 'yes' === $resgata_data['vaccinated'], 'O peso deveria ser arredondado para uma casa e as vacinas em dia gravadas' );
$resgata_assert( is_array( $resgata_data ) && ! str_contains( $resgata_data['story'], '<script' ) && ! str_contains( $resgata_data['story'], 'onerror' ), 'A história deveria ter o HTML perigoso removido' );
$resgata_assert( is_array( $resgata_data ) && 'Preto' === $resgata_data['color'] && 'reserved' === $resgata_data['status'], 'Campos de texto deveriam ser limpos e a situação gravada' );

$resgata_request = new WP_REST_Request( 'PUT', "$resgata_prefix/animals/$resgata_animal_id" );
$resgata_request->set_header( 'content-type', 'application/json' );
$resgata_request->set_body( wp_json_encode( array( 'status' => 'inexistente' ) ) );
$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'Situação inválida deveria ser recusada com 400' );

foreach ( array( array( 'weight' => 500 ), array( 'tagline' => str_repeat( 'a', 121 ) ), array( 'vaccinated' => 'talvez' ) ) as $resgata_invalid ) {
	$resgata_request = new WP_REST_Request( 'PUT', "$resgata_prefix/animals/$resgata_animal_id" );
	$resgata_request->set_header( 'content-type', 'application/json' );
	$resgata_request->set_body( wp_json_encode( $resgata_invalid ) );
	$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'Valor inválido em "' . key( $resgata_invalid ) . '" deveria ser recusado com 400' );
}

$resgata_request = new WP_REST_Request( 'GET', "$resgata_prefix/animals" );
$resgata_request->set_query_params( array( 'per_page' => 1000 ) );
$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'per_page fora da lista deveria ser recusado com 400' );

// Filtros da listagem.
$resgata_request = new WP_REST_Request( 'GET', "$resgata_prefix/animals" );
$resgata_request->set_query_params(
	array(
		'status'      => 'reserved',
		'post_status' => 'draft',
		'search'      => 'Animal de teste',
		'per_page'    => 100,
	)
);
$resgata_data = $resgata_server->dispatch( $resgata_request )->get_data();
$resgata_ids  = wp_list_pluck( $resgata_data['items'] ?? array(), 'id' );
$resgata_assert( in_array( $resgata_animal_id, $resgata_ids, true ), 'O filtro deveria encontrar o animal de teste (reservado, rascunho)' );
$resgata_assert( array( 'reserved' ) === array_values( array_unique( wp_list_pluck( $resgata_data['items'] ?? array(), 'status' ) ) ), 'O filtro por situação deveria trazer só animais reservados' );

$resgata_request = new WP_REST_Request( 'GET', "$resgata_prefix/animals" );
$resgata_request->set_query_params( array( 'status' => 'reserved,inexistente' ) );
$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'Filtro com situação inválida deveria ser recusado com 400' );

$resgata_request = new WP_REST_Request( 'GET', "$resgata_prefix/animals" );
$resgata_request->set_query_params( array( 'rescue_from' => "2026-01-01' OR 1=1" ) );
$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'Data inválida no filtro deveria ser recusada com 400' );

// Banners: textos limpos, links perigosos recusados, limite de itens e imagem inexistente ignorada.
$resgata_put_banners = static function ( array $banners ) use ( $resgata_server, $resgata_prefix ) {
	$request = new WP_REST_Request( 'PUT', "$resgata_prefix/banners" );
	$request->set_header( 'content-type', 'application/json' );
	$request->set_body( wp_json_encode( array( 'banners' => $banners ) ) );

	return $resgata_server->dispatch( $request );
};

$resgata_response = $resgata_put_banners(
	array(
		array(
			'title'      => '<b>Juntos</b><script>alert(1)</script>',
			'button_url' => '/animais/',
			'image_id'   => 99999999,
			'active'     => true,
		),
	)
);
$resgata_data     = $resgata_response->get_data();
$resgata_banner   = $resgata_data['banners'][0] ?? array();
$resgata_assert( 200 === $resgata_response->get_status() && 'Juntos' === ( $resgata_banner['title'] ?? '' ) && '/animais/' === ( $resgata_banner['button_url'] ?? '' ) && 0 === ( $resgata_banner['image_id'] ?? -1 ), 'Banner deveria ser gravado sem HTML, com o caminho do site e sem a imagem inexistente' );

foreach ( array( 'javascript:alert(1)', '//outro-site.example/x', 'data:text/html,x' ) as $resgata_bad_url ) {
	$resgata_assert(
		400 === $resgata_put_banners(
			array(
				array(
					'title'      => 'x',
					'button_url' => $resgata_bad_url,
				),
			)
		)->get_status(),
		"Link de banner \"$resgata_bad_url\" deveria ser recusado com 400"
	);
}

$resgata_assert( 400 === $resgata_put_banners( array_fill( 0, 6, array( 'title' => 'x' ) ) )->get_status(), 'Mais banners que o limite deveria ser recusado com 400' );

// Ligações de blocos: o link nunca fica vazio, chave desconhecida cai no início e os textos saem sem HTML.
$resgata_assert( home_url( '/' ) === ResgataPet\Core\Bindings::link_value( array( 'key' => 'inexistente' ) ), 'Ligação de link desconhecida deveria cair na página inicial' );
$resgata_assert( '' !== ResgataPet\Core\Bindings::link_value( array( 'key' => 'help' ) ) && '' !== ResgataPet\Core\Bindings::link_value( array( 'key' => 'sponsorship' ) ), 'Ligações de link deveriam sempre devolver um endereço' );
$resgata_assert( null === ResgataPet\Core\Bindings::organization_value( array( 'key' => 'adoption_email' ) ), 'A ligação de dados da protetora não deveria expor campos fora da lista (ex.: e-mail interno)' );
$resgata_assert( ! str_contains( (string) ResgataPet\Core\Bindings::organization_value( array( 'key' => 'copyright' ) ), '<' ), 'O texto de direitos deveria sair sem HTML' );
$resgata_assert( block_has_support( WP_Block_Type_Registry::get_instance()->get_registered( 'resgata-pet/banners' ), 'align' ) && isset( WP_Block_Type_Registry::get_instance()->get_registered( 'resgata-pet/banners' )->attributes['align'] ), 'Os blocos do plugin deveriam ser registrados antes dos atributos de suporte (senão a prévia no editor falha)' );

// Início: os números batem com os cadastros e cada área só vem para quem tem acesso.
$resgata_summary        = $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/dashboard" ) )->get_data();
$resgata_counts         = wp_count_posts( 'resgata_animal' );
$resgata_expected_total = (int) $resgata_counts->publish + (int) $resgata_counts->draft;
$resgata_assert( ( $resgata_summary['animals']['total'] ?? -1 ) === $resgata_expected_total, 'O total de animais do Início deveria ser a soma de publicados e rascunhos' );
$resgata_assert( array_sum( $resgata_summary['animals']['by_status'] ?? array() ) === ( $resgata_summary['animals']['total'] ?? -1 ), 'A soma por situação deveria bater com o total de animais' );
$resgata_expected_drafts = (int) $resgata_counts->draft;
$resgata_assert( ( $resgata_summary['animals']['drafts'] ?? -1 ) === $resgata_expected_drafts, 'O Início deveria contar os rascunhos' );

$resgata_editor_id = $resgata_make_user( 'editor' );
wp_set_current_user( $resgata_editor_id );
$resgata_summary = $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/dashboard" ) )->get_data();
$resgata_assert( is_array( $resgata_summary['animals'] ?? null ) && null === $resgata_summary['adoptions'] && null === $resgata_summary['sponsorship'], 'Editor comum deveria ver só os números de animais no Início' );
wp_delete_user( $resgata_editor_id );
wp_set_current_user( $resgata_users['protetor admin'] );

// Planos: textos limpos, valor fora do limite recusado, mudança de valor guarda o preço antigo, plano com padrinho não é excluído.
$resgata_plan_request = static function ( string $method, string $path, array $body = array() ) use ( $resgata_server, $resgata_prefix ) {
	$request = new WP_REST_Request( $method, "$resgata_prefix/sponsorship/plans$path" );
	$request->set_header( 'content-type', 'application/json' );
	$request->set_body( wp_json_encode( $body ? $body : new stdClass() ) );

	return $resgata_server->dispatch( $request );
};

$resgata_assert( 400 === $resgata_plan_request( 'POST', '', array( 'amount' => 50 ) )->get_status(), 'Plano abaixo do valor mínimo deveria ser recusado com 400' );
$resgata_assert( 400 === $resgata_plan_request( 'POST', '', array( 'name' => 'Sem valor' ) )->get_status(), 'Plano sem valor deveria ser recusado com 400' );
$resgata_assert( 404 === $resgata_plan_request( 'PUT', '/prod_inexistente', array( 'name' => 'x' ) )->get_status(), 'Plano inexistente deveria responder 404' );

$resgata_plan_id = $resgata_new_plan( 'Amigo' );
$resgata_plan    = ResgataPet\Modules\Sponsorship\Plans::get( $resgata_plan_id );
$resgata_old     = (string) $resgata_plan['price_id'];

$resgata_response = $resgata_plan_request(
	'PUT',
	"/$resgata_plan_id",
	array(
		'name'     => '<b>Protetor</b><script>x</script>',
		'amount'   => 3000,
		'benefits' => array( '<i>Ração</i>', '', 'Vacinas' ),
		'featured' => true,
	)
);
$resgata_plan     = ResgataPet\Modules\Sponsorship\Plans::get( $resgata_plan_id );
$resgata_assert( 200 === $resgata_response->get_status() && 'Protetor' === $resgata_plan['name'] && array( 'Ração', 'Vacinas' ) === $resgata_plan['benefits'], 'Nome e benefícios do plano deveriam ser gravados sem HTML e sem linhas vazias' );
$resgata_assert( 3000 === $resgata_plan['amount'] && $resgata_old !== $resgata_plan['price_id'] && array( $resgata_old ) === wp_list_pluck( $resgata_plan['old_prices'], 'price_id' ), 'Mudar o valor deveria criar um preço novo e guardar o antigo' );

$resgata_found = ResgataPet\Modules\Sponsorship\Plans::find( $resgata_old );
$resgata_assert( $resgata_found && 1500 === $resgata_found['amount'] && 'Protetor' === $resgata_found['name'], 'Quem assinou o preço antigo deveria continuar reconhecido, com o valor antigo' );
$resgata_assert( null === ResgataPet\Modules\Sponsorship\Plans::find_for_sale( $resgata_old ), 'O preço antigo não deveria mais ser vendido no site' );

$resgata_plan_request( 'PUT', "/$resgata_plan_id", array( 'active' => false ) );
$resgata_assert( null === ResgataPet\Modules\Sponsorship\Plans::find_for_sale( (string) $resgata_plan['price_id'] ) && null !== ResgataPet\Modules\Sponsorship\Plans::find( (string) $resgata_plan['price_id'] ), 'Plano escondido não deveria ser vendido, mas deveria continuar reconhecido' );

$resgata_sponsor_customer = 'cus_teste_' . wp_generate_password( 8, false );
ResgataPet\Modules\Sponsorship\Sponsors::upsert(
	$resgata_sponsor_customer,
	array(
		'email'    => 'padrinho-teste@example.org',
		'price_id' => $resgata_old,
		'status'   => 'active',
	)
);
$resgata_assert( 409 === $resgata_plan_request( 'DELETE', "/$resgata_plan_id" )->get_status(), 'Plano com padrinho não deveria ser excluído (409)' );

$resgata_request = new WP_REST_Request( 'GET', "$resgata_prefix/sponsors" );
$resgata_request->set_query_params( array( 'search' => 'padrinho-teste@example.org' ) );
$resgata_row = $resgata_server->dispatch( $resgata_request )->get_data()['items'][0] ?? array();
$resgata_assert( 'Protetor' === ( $resgata_row['plan'] ?? '' ) && str_contains( (string) ( $resgata_row['plan_amount'] ?? '' ), '15' ), 'A lista de padrinhos deveria mostrar o nome do plano e o valor que a pessoa paga' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Limpeza do padrinho criado pelo teste.
$wpdb->delete( ResgataPet\Modules\Sponsorship\Sponsors::table(), array( 'customer_id' => $resgata_sponsor_customer ) );
$resgata_assert( 200 === $resgata_plan_request( 'DELETE', "/$resgata_plan_id" )->get_status() && null === ResgataPet\Modules\Sponsorship\Plans::get( $resgata_plan_id ), 'Plano sem padrinho deveria ser excluído' );

// Configurações: o Editor comum não pode alterar; valores são limpos; campo interno não vaza no site.
$resgata_editor_id = $resgata_make_user( 'editor' );
wp_set_current_user( $resgata_editor_id );
$resgata_assert( 403 === $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/settings" ) )->get_status(), 'Editor comum não deveria ler as configurações' );
wp_delete_user( $resgata_editor_id );

wp_set_current_user( $resgata_users['protetor admin'] );
$resgata_request = new WP_REST_Request( 'PUT', "$resgata_prefix/settings" );
$resgata_request->set_header( 'content-type', 'application/json' );
$resgata_request->set_body(
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
$resgata_data = $resgata_server->dispatch( $resgata_request )->get_data()['settings'] ?? array();
$resgata_assert( 'Protetora Teste' === ( $resgata_data['name'] ?? '' ), 'O nome deveria ter o HTML removido' );
$resgata_assert( '11912345678' === ( $resgata_data['whatsapp'] ?? '' ), 'O WhatsApp deveria guardar só os dígitos' );
$resgata_assert( 'https://www.instagram.com/protetora.teste' === ( $resgata_data['instagram'] ?? '' ), 'O @usuario do Instagram deveria virar link' );
$resgata_assert( '' === ( $resgata_data['facebook'] ?? null ), 'Link com javascript: deveria ser recusado' );

$resgata_html = ResgataPet\Modules\Settings\ContactBlock::render();
$resgata_assert( str_contains( $resgata_html, 'wa.me/5511912345678' ) && str_contains( $resgata_html, 'pix@example.org' ), 'O bloco de contato deveria mostrar WhatsApp e Pix' );
$resgata_assert( ! str_contains( $resgata_html, 'interno@example.org' ), 'O e-mail interno de adoção não pode aparecer no site' );

$resgata_request = new WP_REST_Request( 'PUT', "$resgata_prefix/settings" );
$resgata_request->set_header( 'content-type', 'application/json' );
$resgata_request->set_body( wp_json_encode( array( 'email' => 'nao-e-email' ) ) );
$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'E-mail inválido deveria ser recusado com 400' );

// Página de agradecimento do apadrinhamento: só uma página publicada do site serve.
$resgata_request = new WP_REST_Request( 'PUT', "$resgata_prefix/settings" );
$resgata_request->set_header( 'content-type', 'application/json' );
$resgata_request->set_body( wp_json_encode( array( 'sponsorship_thanks_page' => (string) $resgata_animal_id ) ) );
$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'Um animal não pode ser escolhido como página de agradecimento' );

if ( ResgataPet\Core\Modules::is_enabled( 'sponsorship' ) ) {
	// Um salvamento anterior deste teste pode ter criado a página "Obrigado": ela sai na limpeza.
	$resgata_auto_thanks = ResgataPet\Modules\Sponsorship\ThanksPage::get();
	if ( $resgata_auto_thanks && (int) ( $resgata_saved_options['resgata_organization']['sponsorship_thanks_page'] ?? 0 ) !== $resgata_auto_thanks->ID ) {
		$resgata_created[] = $resgata_auto_thanks->ID;
	}

	$resgata_thanks_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Agradecimento de teste', 'post_content' => '<!-- wp:resgata-pet/sponsorship-thanks /-->' ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
	$resgata_created[] = $resgata_thanks_id;

	$resgata_request = new WP_REST_Request( 'PUT', "$resgata_prefix/settings" );
	$resgata_request->set_header( 'content-type', 'application/json' );
	$resgata_request->set_body(
		wp_json_encode(
			array(
				'sponsorship_thanks_page'  => (string) $resgata_thanks_id,
				'sponsorship_thanks_title' => 'Valeu, <b>padrinho</b>!',
				'sponsorship_thanks_text'  => 'Texto de teste do agradecimento.',
			)
		)
	);
	$resgata_response = $resgata_server->dispatch( $resgata_request )->get_data();
	$resgata_assert( ( $resgata_response['thanks_page']['id'] ?? 0 ) === $resgata_thanks_id, 'A página escolhida deveria virar a página de agradecimento' );
	$resgata_assert( get_permalink( $resgata_thanks_id ) === ResgataPet\Modules\Sponsorship\ThanksPage::url(), 'O retorno do pagamento deveria apontar para a página escolhida' );

	$resgata_html = ResgataPet\Modules\Sponsorship\Blocks::render_thanks();
	$resgata_assert( str_contains( $resgata_html, 'Valeu, padrinho!' ) && str_contains( $resgata_html, 'Texto de teste do agradecimento.' ) && ! str_contains( $resgata_html, '<b>' ), 'O bloco de agradecimento deveria mostrar o título (sem HTML) e o texto das Configurações' );
}

// Pedidos de adoção: Editor comum não acessa; o formulário valida; o pedido não vaza na REST do WordPress.
$resgata_editor_id = $resgata_make_user( 'editor' );
wp_set_current_user( $resgata_editor_id );
$resgata_request_id = $resgata_new_request();
$resgata_assert( 403 === $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/adoptions" ) )->get_status(), 'Editor comum não deveria listar pedidos de adoção' );
$resgata_assert( 403 === $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/adoptions/$resgata_request_id" ) )->get_status(), 'Editor comum não deveria ler um pedido de adoção' );
wp_delete_user( $resgata_editor_id );

wp_set_current_user( $resgata_users['protetor admin'] );
$resgata_assert( 403 === $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/adoptions/$resgata_animal_id" ) )->get_status(), 'ID de um animal não deveria abrir como pedido de adoção' );
$resgata_assert( 404 === $resgata_server->dispatch( new WP_REST_Request( 'GET', '/wp/v2/resgata_adoption' ) )->get_status(), 'Pedidos de adoção não podem existir na REST padrão do WordPress' );

// Padrinhos: Editor comum não acessa; as chaves da Stripe nunca aparecem na resposta.
if ( ResgataPet\Core\Modules::is_enabled( 'sponsorship' ) ) {
	$resgata_editor_id = $resgata_make_user( 'editor' );
	wp_set_current_user( $resgata_editor_id );
	$resgata_assert( 403 === $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/sponsors" ) )->get_status(), 'Editor comum não deveria listar os padrinhos' );
	$resgata_assert( 403 === $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/sponsorship" ) )->get_status(), 'Editor comum não deveria ver o resumo do apadrinhamento' );
	wp_delete_user( $resgata_editor_id );

	wp_set_current_user( $resgata_users['protetor admin'] );
	$resgata_json = (string) wp_json_encode( $resgata_server->dispatch( new WP_REST_Request( 'GET', "$resgata_prefix/sponsorship" ) )->get_data() );
	foreach ( array( ResgataPet\Modules\Sponsorship\Keys::secret_key(), ResgataPet\Modules\Sponsorship\Keys::webhook_secret() ) as $resgata_secret_value ) {
		$resgata_assert( '' === $resgata_secret_value || ! str_contains( $resgata_json, $resgata_secret_value ), 'As chaves da Stripe não podem aparecer na API do painel' );
	}

	$resgata_request = new WP_REST_Request( 'PUT', "$resgata_prefix/sponsorship" );
	$resgata_request->set_header( 'content-type', 'application/json' );
	$resgata_request->set_body( wp_json_encode( array( 'adjustment' => 1000000 ) ) );
	$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'Ajuste do contador fora do limite deveria ser recusado com 400' );

	// Chaves da Stripe salvas pelo painel: cifradas no banco, só o Administrador grava.
	$resgata_keys_request = static function ( array $body ) use ( $resgata_server, $resgata_prefix ) {
		$request = new WP_REST_Request( 'PUT', "$resgata_prefix/sponsorship/keys" );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return $resgata_server->dispatch( $request );
	};
	$resgata_fake_key     = 'rk_test_' . wp_generate_password( 40, false );
	$resgata_fake_whsec   = 'whsec_' . wp_generate_password( 32, false );

	$resgata_assert( 403 === $resgata_keys_request( array( 'secret_key' => $resgata_fake_key ) )->get_status(), 'Protetor admin não deveria gravar a chave da Stripe' );
	$resgata_assert( get_option( 'resgata_stripe_keys', null ) === $resgata_saved_options['resgata_stripe_keys'], 'Pedido recusado não deveria mexer nas chaves gravadas' );

	wp_set_current_user( $resgata_users['administrador'] );
	$resgata_keys = ResgataPet\Modules\Sponsorship\Keys::class;

	$resgata_fake_keys = array(
		'secret_key'     => $resgata_fake_key,
		'webhook_secret' => $resgata_fake_whsec,
	);

	foreach ( $resgata_fake_keys as $resgata_field => $resgata_value ) {
		if ( 'constant' === $resgata_keys::source( $resgata_field ) ) {
			$resgata_assert( 409 === $resgata_keys_request( array( $resgata_field => $resgata_value ) )->get_status(), "Chave do wp-config.php ($resgata_field) não deveria ser trocada pelo painel" );
			continue;
		}

		$resgata_assert( 'secret_key' !== $resgata_field || 'resgata_stripe_key_full' === ( $resgata_keys_request( array( $resgata_field => 'sk_test_' . wp_generate_password( 40, false ) ) )->get_data()['code'] ?? '' ), 'A chave secreta completa (sk_) deveria ser recusada' );
		$resgata_assert( 400 === $resgata_keys_request( array( $resgata_field => 'chave errada' ) )->get_status(), "Chave fora do formato ($resgata_field) deveria ser recusada com 400" );

		$resgata_response = $resgata_keys_request( array( $resgata_field => $resgata_value ) );
		$resgata_assert( 200 === $resgata_response->get_status() && $resgata_value === $resgata_keys::stored( $resgata_field ), "O Administrador deveria conseguir gravar a chave ($resgata_field)" );
		$resgata_assert( ! str_contains( (string) wp_json_encode( $resgata_response->get_data() ), $resgata_value ), "A chave gravada ($resgata_field) não pode voltar na resposta" );
	}

	// Gravação direta: vale também quando o ambiente de teste tem as chaves no wp-config.php.
	$resgata_assert( $resgata_keys::store( 'secret_key', $resgata_fake_key ) && $resgata_fake_key === $resgata_keys::stored( 'secret_key' ), 'A chave gravada deveria ser lida de volta' );
	$resgata_raw = get_option( 'resgata_stripe_keys' );
	$resgata_assert( is_array( $resgata_raw ) && ! str_contains( (string) wp_json_encode( $resgata_raw ), $resgata_fake_key ) && ! str_contains( (string) wp_json_encode( $resgata_raw ), 'rk_test_' ), 'A chave deveria estar cifrada no banco' );
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Confere a coluna de carregamento automático, que não tem função própria.
	$resgata_assert( 'off' === $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", 'resgata_stripe_keys' ) ), 'A option das chaves não deveria ser carregada automaticamente' );

	$resgata_first = $resgata_raw['secret_key'] ?? '';
	$resgata_keys::store( 'secret_key', $resgata_fake_key );
	$resgata_assert( ( get_option( 'resgata_stripe_keys' )['secret_key'] ?? '' ) !== $resgata_first, 'Cifrar a mesma chave duas vezes deveria dar resultados diferentes' );

	update_option( 'resgata_stripe_keys', array( 'secret_key' => substr( $resgata_first, 0, -4 ) . 'AAAA' ), false );
	$resgata_keys::store( 'webhook_secret', '' ); // Só para descartar o que foi lido antes.
	$resgata_assert( '' === $resgata_keys::stored( 'secret_key' ) && array( 'secret_key' ) === $resgata_keys::unreadable(), 'Chave adulterada no banco deveria ser tratada como ilegível' );

	$resgata_keys::store( 'secret_key', '' );
	$resgata_assert( false === get_option( 'resgata_stripe_keys' ), 'Apagar a última chave deveria apagar a option' );

	$resgata_assert( null === ResgataPet\Core\Encryption::decrypt( 'texto que não foi cifrado' ), 'Texto que não foi cifrado pelo plugin não deveria ser decifrado' );

	wp_set_current_user( $resgata_users['protetor admin'] );

	$resgata_request = new WP_REST_Request( 'GET', "$resgata_prefix/sponsors" );
	$resgata_request->set_query_params( array( 'status' => "active' OR 1=1" ) );
	$resgata_assert( 400 === $resgata_server->dispatch( $resgata_request )->get_status(), 'Filtro de padrinhos com situação inválida deveria ser recusado com 400' );
}

wp_set_current_user( 0 );
$resgata_result = ResgataPet\Modules\Adoptions\Submission::create( array( 'name' => 'Sem dados' ) );
$resgata_assert( is_wp_error( $resgata_result ) && isset( $resgata_result->get_error_data()['consent'], $resgata_result->get_error_data()['animal_id'], $resgata_result->get_error_data()['whatsapp'] ), 'Pedido incompleto deveria ser recusado (animal, consentimento e WhatsApp)' );

$resgata_adoptable = $resgata_new_animal();
wp_update_post(
	array(
		'ID'          => $resgata_adoptable,
		'post_status' => 'publish',
	)
);
update_post_meta( $resgata_adoptable, 'resgata_status', 'available' );
$resgata_valid  = array(
	'animal_id'        => $resgata_adoptable,
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
$resgata_result = ResgataPet\Modules\Adoptions\Submission::create( $resgata_valid );
$resgata_assert( is_int( $resgata_result ) && 'private' === get_post_status( $resgata_result ), 'Pedido válido deveria ser gravado como privado' );
if ( is_int( $resgata_result ) ) {
	$resgata_created[] = $resgata_result;
	$resgata_assert( ! str_contains( (string) get_post_field( 'post_title', $resgata_result ), '<' ), 'O nome do pedido deveria ter o HTML removido' );
	$resgata_consent = get_post_meta( $resgata_result, 'resgata_adoption_consent', true );
	$resgata_assert( is_array( $resgata_consent ) && ! empty( $resgata_consent['at'] ) && ! empty( $resgata_consent['text'] ), 'O consentimento (data e texto) deveria ser guardado com o pedido' );
}

update_post_meta( $resgata_adoptable, 'resgata_status', 'adopted' );
$resgata_assert( is_wp_error( ResgataPet\Modules\Adoptions\Submission::create( $resgata_valid ) ), 'Animal já adotado não deveria aceitar pedido' );

wp_set_current_user( $resgata_users['protetor admin'] );

// O DELETE só move para a lixeira.
$resgata_server->dispatch( new WP_REST_Request( 'DELETE', "$resgata_prefix/animals/$resgata_animal_id" ) );
$resgata_assert( 'trash' === get_post_status( $resgata_animal_id ), 'Excluir deveria mover para a lixeira, não apagar' );

// Limpeza (definida no começo do arquivo).
$resgata_cleanup();

if ( 0 === $resgata_routes ) {
	WP_CLI::error( 'Nenhuma rota encontrada em ' . BaseController::REST_NAMESPACE . '.' );
}

if ( $resgata_failures ) {
	foreach ( $resgata_failures as $resgata_failure ) {
		WP_CLI::warning( $resgata_failure );
	}
	WP_CLI::error( sprintf( '%d de %d verificações falharam.', count( $resgata_failures ), $resgata_checks ) );
}

WP_CLI::success( sprintf( '%d verificações em %d rotas: tudo certo.', $resgata_checks, $resgata_routes ) );
