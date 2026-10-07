<?php
/**
 * Padrinhos (tabela `rwp_sponsors`).
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

defined( 'ABSPATH' ) || exit;

/**
 * Leitura e escrita dos padrinhos.
 *
 * O WordPress guarda só nome, e-mail, IDs da Stripe (cliente, assinatura e
 * preço) e a situação. Nada de CPF, endereço ou dados de pagamento.
 */
final class Sponsors {

	public const STATUS_ACTIVE = 'active';

	/**
	 * Nome da tabela, com o prefixo do site.
	 */
	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'rwp_sponsors';
	}

	/**
	 * Situações da assinatura na Stripe, com o nome mostrado no painel.
	 *
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return array(
			'active'             => __( 'Ativo', 'resgata-wp' ),
			'past_due'           => __( 'Pagamento atrasado', 'resgata-wp' ),
			'unpaid'             => __( 'Não pago', 'resgata-wp' ),
			'incomplete'         => __( 'Aguardando pagamento', 'resgata-wp' ),
			'incomplete_expired' => __( 'Não concluído', 'resgata-wp' ),
			'trialing'           => __( 'Em período de teste', 'resgata-wp' ),
			'paused'             => __( 'Pausado', 'resgata-wp' ),
			'canceled'           => __( 'Cancelado', 'resgata-wp' ),
		);
	}

	/**
	 * Padrinho pelo ID do cliente na Stripe.
	 *
	 * @param string $customer_id ID do cliente (`cus_...`).
	 * @return array<string, mixed>|null
	 */
	public static function find_by_customer( string $customer_id ): ?array {
		global $wpdb;

		if ( '' === $customer_id ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria; os webhooks precisam do dado atual.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE customer_id = %s', self::table(), $customer_id ), ARRAY_A );

		return $row ? $row : null;
	}

	/**
	 * Padrinho pelo ID interno.
	 *
	 * @param int $id ID na tabela.
	 * @return array<string, mixed>|null
	 */
	public static function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), $id ), ARRAY_A );

		return $row ? $row : null;
	}

	/**
	 * Padrinho de um e-mail. Se houver mais de um cadastro, prefere o ativo e depois o mais recente.
	 *
	 * @param string $email E-mail informado.
	 * @return array<string, mixed>|null
	 */
	public static function find_by_email( string $email ): ?array {
		global $wpdb;

		$email = sanitize_email( $email );

		if ( '' === $email ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE email = %s ORDER BY ( status = %s ) DESC, updated_at DESC LIMIT 1',
				self::table(),
				$email,
				self::STATUS_ACTIVE
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Cria ou atualiza o padrinho de um cliente da Stripe.
	 *
	 * @param string               $customer_id ID do cliente (`cus_...`).
	 * @param array<string, mixed> $fields      Colunas a gravar.
	 * @return bool False se o banco recusar a gravação.
	 */
	public static function upsert( string $customer_id, array $fields ): bool {
		global $wpdb;

		$allowed = array(
			'subscription_id' => '%s',
			'name'            => '%s',
			'email'           => '%s',
			'price_id'        => '%s',
			'status'          => '%s',
			'payment_failed'  => '%d',
			'last_paid_at'    => '%s',
			'last_event_at'   => '%d',
		);
		$data    = array_intersect_key( $fields, $allowed );
		$now     = current_time( 'mysql', true );

		foreach ( array( 'subscription_id', 'price_id', 'status' ) as $column ) {
			if ( isset( $data[ $column ] ) ) {
				$data[ $column ] = substr( sanitize_text_field( (string) $data[ $column ] ), 0, 64 );
			}
		}
		if ( isset( $data['name'] ) ) {
			$data['name'] = mb_substr( sanitize_text_field( (string) $data['name'] ), 0, 190 );
		}
		if ( isset( $data['email'] ) ) {
			$data['email'] = sanitize_email( (string) $data['email'] );
		}

		$data['updated_at'] = $now;
		$existing           = self::find_by_customer( $customer_id );

		if ( $existing ) {
			$formats = array();
			foreach ( array_keys( $data ) as $column ) {
				$formats[] = $allowed[ $column ] ?? '%s';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
			return false !== $wpdb->update( self::table(), $data, array( 'id' => (int) $existing['id'] ), $formats, array( '%d' ) );
		}

		$data['customer_id'] = substr( sanitize_text_field( $customer_id ), 0, 64 );
		$data['created_at']  = $now;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
		return false !== $wpdb->insert( self::table(), $data );
	}

	/**
	 * Quantidade de assinaturas ativas.
	 */
	public static function count_active(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria; o resultado fica guardado em uma option.
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', self::table(), self::STATUS_ACTIVE ) );
	}

	/**
	 * Quantos padrinhos (em qualquer situação) há em cada preço.
	 *
	 * @return array<string, int> ID do preço => quantidade.
	 */
	public static function count_by_price(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria; consulta só do painel.
		$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT price_id, COUNT(*) AS total FROM %i GROUP BY price_id', self::table() ), ARRAY_A );
		$counts = array();

		foreach ( (array) $rows as $row ) {
			$counts[ (string) $row['price_id'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Lista paginada para o painel, mais recentes primeiro.
	 *
	 * @param int      $page     Página (a partir de 1).
	 * @param int      $per_page Itens por página.
	 * @param string[] $statuses Situações aceitas (vazio = todas).
	 * @param string   $search   Trecho do nome ou do e-mail.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public static function query( int $page, int $per_page, array $statuses = array(), string $search = '' ): array {
		global $wpdb;

		$where = array( '1 = 1' );
		$args  = array( self::table() );

		if ( $statuses ) {
			$where[] = 'status IN (' . implode( ', ', array_fill( 0, count( $statuses ), '%s' ) ) . ')';
			$args    = array_merge( $args, array_values( $statuses ) );
		}

		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '( name LIKE %s OR email LIKE %s )';
			$args[]  = $like;
			$args[]  = $like;
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Tabela própria; $where_sql só tem marcadores, preenchidos pelo prepare().
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE $where_sql", $args ) );
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE $where_sql ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
				array_merge( $args, array( $per_page, ( max( 1, $page ) - 1 ) * $per_page ) )
			),
			ARRAY_A
		);
		// phpcs:enable

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}
}
