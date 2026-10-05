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

$rwp_assert = static function ( bool $ok, string $message ) use ( &$rwp_failures, &$rwp_checks ): void {
	++$rwp_checks;
	if ( ! $ok ) {
		$rwp_failures[] = $message;
	}
};

$rwp_server = rest_get_server();
$rwp_prefix = '/' . BaseController::REST_NAMESPACE;
$rwp_routes = 0;

foreach ( $rwp_server->get_routes( BaseController::REST_NAMESPACE ) as $rwp_route => $rwp_handlers ) {
	if ( $rwp_prefix === $rwp_route ) {
		continue; // Índice do namespace (gerado pelo WordPress, só lista as rotas).
	}

	++$rwp_routes;

	foreach ( $rwp_handlers as $rwp_handler ) {
		$rwp_assert( isset( $rwp_handler['permission_callback'] ) && is_callable( $rwp_handler['permission_callback'] ), "$rwp_route: rota sem permission_callback" );

		foreach ( array_keys( array_filter( $rwp_handler['methods'] ) ) as $rwp_method ) {
			foreach ( $rwp_users as $rwp_label => $rwp_user_id ) {
				wp_set_current_user( $rwp_user_id );

				// Cada chamada usa um animal novo, para o DELETE de um perfil não afetar o seguinte.
				$rwp_path    = preg_replace( '/\(\?P<id>[^)]+\)/', (string) $rwp_new_animal(), $rwp_route );
				$rwp_request = new WP_REST_Request( $rwp_method, $rwp_path );
				$rwp_request->set_header( 'content-type', 'application/json' );

				if ( in_array( $rwp_method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
					$rwp_request->set_body(
						wp_json_encode(
							array(
								'name'    => 'Animal de teste',
								'species' => $rwp_species[0] ?? '',
							)
						)
					);
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
			'story'  => '<p>Oi</p><script>alert(1)</script><img src=x onerror=alert(1)>',
			'status' => 'reserved',
			'color'  => '<b>Preto</b>',
		)
	)
);
$rwp_data = $rwp_server->dispatch( $rwp_request )->get_data();
$rwp_assert( is_array( $rwp_data ) && ! str_contains( $rwp_data['story'], '<script' ) && ! str_contains( $rwp_data['story'], 'onerror' ), 'A história deveria ter o HTML perigoso removido' );
$rwp_assert( is_array( $rwp_data ) && 'Preto' === $rwp_data['color'] && 'reserved' === $rwp_data['status'], 'Campos de texto deveriam ser limpos e a situação gravada' );

$rwp_request = new WP_REST_Request( 'PUT', "$rwp_prefix/animals/$rwp_animal_id" );
$rwp_request->set_header( 'content-type', 'application/json' );
$rwp_request->set_body( wp_json_encode( array( 'status' => 'inexistente' ) ) );
$rwp_assert( 400 === $rwp_server->dispatch( $rwp_request )->get_status(), 'Situação inválida deveria ser recusada com 400' );

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

// O DELETE só move para a lixeira.
$rwp_server->dispatch( new WP_REST_Request( 'DELETE', "$rwp_prefix/animals/$rwp_animal_id" ) );
$rwp_assert( 'trash' === get_post_status( $rwp_animal_id ), 'Excluir deveria mover para a lixeira, não apagar' );

// Limpeza.
wp_set_current_user( 0 );
foreach ( array_unique( array_filter( $rwp_created ) ) as $rwp_post_id ) {
	wp_delete_post( (int) $rwp_post_id, true );
}
foreach ( array_filter( $rwp_users ) as $rwp_user_id ) {
	wp_delete_user( $rwp_user_id );
}

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
