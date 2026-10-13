<?php
/**
 * Pagamentos dos padrinhos (tabela `resgata_payments`).
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

defined( 'ABSPATH' ) || exit;

/**
 * Registro de cada mensalidade paga, com o que foi devolvido ou contestado.
 *
 * Cada linha é uma fatura paga da Stripe. Ficam só os IDs da fatura e do
 * pagamento, o valor e a data: nenhum dado do cartão. Valores em centavos.
 */
final class Payments {

	/**
	 * Situações de uma contestação: em análise (o banco retirou o valor), ganha (o valor voltou) e perdida.
	 */
	public const DISPUTE_OPEN = 'open';
	public const DISPUTE_WON  = 'won';
	public const DISPUTE_LOST = 'lost';

	/**
	 * A mesma conta de `net()`, em SQL, para somar no banco.
	 */
	private const NET_SQL = "CASE WHEN dispute IN ( 'open', 'lost' ) OR refunded >= amount THEN 0 ELSE amount - refunded END";

	/**
	 * Por quantos dias fica guardada a linha de uma fatura que não é de um padrinho.
	 */
	private const KEEP_ORPHAN_DAYS = 30;

	/**
	 * Nome da tabela, com o prefixo do site.
	 */
	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'resgata_payments';
	}

	/**
	 * Cria ou completa o pagamento de uma fatura.
	 *
	 * Os dados chegam em dois eventos, em qualquer ordem: a fatura paga (padrinho,
	 * valor e data) e o pagamento da fatura (ID do pagamento, usado depois para
	 * achar a linha em um estorno ou contestação). Cada um grava só o que traz.
	 *
	 * @param string               $invoice_id ID da fatura (`in_...`).
	 * @param array<string, mixed> $fields     Colunas a gravar.
	 * @return bool False se o banco recusar a gravação.
	 */
	public static function record( string $invoice_id, array $fields ): bool {
		global $wpdb;

		$allowed = array(
			'sponsor_id'        => '%d',
			'payment_intent_id' => '%s',
			'amount'            => '%d',
			'paid_at'           => '%s',
		);
		$data    = array_intersect_key( $fields, $allowed );

		if ( '' === $invoice_id || ! $data ) {
			return true;
		}

		if ( isset( $data['payment_intent_id'] ) ) {
			$data['payment_intent_id'] = substr( sanitize_text_field( (string) $data['payment_intent_id'] ), 0, 64 );
		}

		$columns = array( 'invoice_id', 'created_at' );
		$marks   = array( '%s', '%s' );
		$values  = array( substr( sanitize_text_field( $invoice_id ), 0, 64 ), current_time( 'mysql', true ) );
		$updates = array();

		foreach ( $data as $column => $value ) {
			$columns[] = $column;
			$marks[]   = $allowed[ $column ];
			$values[]  = $value;
			$updates[] = "$column = VALUES($column)";
		}

		$columns_sql = implode( ', ', $columns );
		$marks_sql   = implode( ', ', $marks );
		$updates_sql = implode( ', ', $updates );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Tabela própria; os nomes de coluna vêm da lista fixa acima e os valores passam pelo prepare().
		$saved = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i ( $columns_sql ) VALUES ( $marks_sql ) ON DUPLICATE KEY UPDATE $updates_sql",
				array_merge( array( self::table() ), $values )
			)
		);
		// phpcs:enable

		return false !== $saved;
	}

	/**
	 * Pagamento pelo ID do pagamento na Stripe (`pi_...`).
	 *
	 * @param string $payment_intent_id ID do pagamento.
	 * @return array<string, mixed>|null
	 */
	public static function find_by_intent( string $payment_intent_id ): ?array {
		return '' !== $payment_intent_id ? self::find_by( 'payment_intent_id', $payment_intent_id ) : null;
	}

	/**
	 * Pagamento pelo ID da fatura (`in_...`).
	 *
	 * @param string $invoice_id ID da fatura.
	 * @return array<string, mixed>|null
	 */
	public static function find_by_invoice( string $invoice_id ): ?array {
		return '' !== $invoice_id ? self::find_by( 'invoice_id', $invoice_id ) : null;
	}

	/**
	 * Grava quanto do pagamento já foi devolvido.
	 *
	 * A Stripe informa o total devolvido até o momento; um evento antigo, entregue com
	 * atraso, não diminui o que já está gravado.
	 *
	 * @param int $id       ID na tabela.
	 * @param int $refunded Total devolvido, em centavos.
	 */
	public static function set_refunded( int $id, int $refunded ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
		return false !== $wpdb->query( $wpdb->prepare( 'UPDATE %i SET refunded = GREATEST( refunded, %d ) WHERE id = %d', self::table(), $refunded, $id ) );
	}

	/**
	 * Grava a situação da contestação de um pagamento.
	 *
	 * @param int    $id      ID na tabela.
	 * @param string $dispute Uma das constantes DISPUTE_*, ou vazio.
	 */
	public static function set_dispute( int $id, string $dispute ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
		return false !== $wpdb->update( self::table(), array( 'dispute' => $dispute ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
	}

	/**
	 * Quanto de um pagamento ficou de fato com a protetora, em centavos.
	 *
	 * @param array<string, mixed> $payment Linha da tabela.
	 */
	public static function net( array $payment ): int {
		if ( in_array( $payment['dispute'], array( self::DISPUTE_OPEN, self::DISPUTE_LOST ), true ) ) {
			return 0;
		}

		return max( 0, (int) $payment['amount'] - (int) $payment['refunded'] );
	}

	/**
	 * Total doado por um padrinho, já sem estornos e contestações.
	 *
	 * @param int $sponsor_id ID do padrinho.
	 * @return array{total: int, count: int} Total em centavos e número de pagamentos que contam.
	 */
	public static function totals( int $sponsor_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria; um padrinho tem um pagamento por mês.
		$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT amount, refunded, dispute FROM %i WHERE sponsor_id = %d', self::table(), $sponsor_id ), ARRAY_A );
		$totals = array(
			'total' => 0,
			'count' => 0,
		);

		foreach ( (array) $rows as $row ) {
			$net = self::net( $row );

			if ( $net > 0 ) {
				$totals['total'] += $net;
				++$totals['count'];
			}
		}

		return $totals;
	}

	/**
	 * Total doado por cada padrinho de uma lista, já sem estornos e contestações.
	 *
	 * @param int[] $sponsor_ids IDs dos padrinhos.
	 * @return array<int, int> ID do padrinho => total em centavos (só quem tem pagamento).
	 */
	public static function totals_by_sponsor( array $sponsor_ids ): array {
		global $wpdb;

		$sponsor_ids = array_values( array_filter( array_map( 'absint', $sponsor_ids ) ) );

		if ( ! $sponsor_ids ) {
			return array();
		}

		$net   = self::NET_SQL;
		$marks = implode( ', ', array_fill( 0, count( $sponsor_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Tabela própria; $net é uma constante e $marks só tem marcadores, preenchidos pelo prepare().
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sponsor_id, SUM( $net ) AS total FROM %i WHERE sponsor_id IN ( $marks ) GROUP BY sponsor_id",
				array_merge( array( self::table() ), $sponsor_ids )
			),
			ARRAY_A
		);
		// phpcs:enable

		$totals = array();
		foreach ( (array) $rows as $row ) {
			$totals[ (int) $row['sponsor_id'] ] = (int) $row['total'];
		}

		return $totals;
	}

	/**
	 * Arrecadação dos padrinhos: mês atual, mês passado e total, mais as contestações em análise.
	 *
	 * Os meses seguem o fuso do site; os valores já descontam estornos e contestações.
	 *
	 * @return array{month: int, last_month: int, total: int, open_disputes: int} Valores em centavos.
	 */
	public static function revenue(): array {
		global $wpdb;

		$net        = self::NET_SQL;
		$this_month = new \DateTimeImmutable( 'first day of this month midnight', wp_timezone() );
		$utc        = new \DateTimeZone( 'UTC' );
		$month_from = $this_month->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		$last_from  = $this_month->modify( '-1 month' )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabela própria; $net é uma constante da classe.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM( CASE WHEN paid_at >= %s THEN $net ELSE 0 END ) AS month,
					SUM( CASE WHEN paid_at >= %s AND paid_at < %s THEN $net ELSE 0 END ) AS last_month,
					SUM( $net ) AS total,
					SUM( dispute = %s ) AS open_disputes
				FROM %i WHERE sponsor_id > 0",
				$month_from,
				$last_from,
				$month_from,
				self::DISPUTE_OPEN,
				self::table()
			),
			ARRAY_A
		);
		// phpcs:enable

		return array(
			'month'         => (int) ( $row['month'] ?? 0 ),
			'last_month'    => (int) ( $row['last_month'] ?? 0 ),
			'total'         => (int) ( $row['total'] ?? 0 ),
			'open_disputes' => (int) ( $row['open_disputes'] ?? 0 ),
		);
	}

	/**
	 * Lista paginada dos pagamentos dos padrinhos para o painel, mais recentes primeiro.
	 *
	 * @param int $page     Página (a partir de 1).
	 * @param int $per_page Itens por página.
	 * @return array{items: array<int, array<string, mixed>>, total: int} Cada item traz também `sponsor_name`.
	 */
	public static function query( int $page, int $per_page ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Tabelas próprias; consulta só do painel.
		$total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE sponsor_id > 0', self::table() ) );
		$items = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.*, s.name AS sponsor_name FROM %i p LEFT JOIN %i s ON s.id = p.sponsor_id WHERE p.sponsor_id > 0 ORDER BY p.paid_at DESC, p.id DESC LIMIT %d OFFSET %d',
				self::table(),
				Sponsors::table(),
				$per_page,
				( max( 1, $page ) - 1 ) * $per_page
			),
			ARRAY_A
		);
		// phpcs:enable

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Situação de um pagamento para o painel.
	 *
	 * @param array<string, mixed> $payment Linha da tabela.
	 * @return string `paid`, `partially_refunded`, `refunded`, `disputed`, `dispute_lost` ou `dispute_won`.
	 */
	public static function status( array $payment ): string {
		if ( self::DISPUTE_OPEN === $payment['dispute'] ) {
			return 'disputed';
		}

		if ( self::DISPUTE_LOST === $payment['dispute'] ) {
			return 'dispute_lost';
		}

		if ( (int) $payment['refunded'] >= (int) $payment['amount'] ) {
			return 'refunded';
		}

		if ( (int) $payment['refunded'] > 0 ) {
			return 'partially_refunded';
		}

		return self::DISPUTE_WON === $payment['dispute'] ? 'dispute_won' : 'paid';
	}

	/**
	 * Apaga as linhas antigas de faturas que não são de padrinhos (outros produtos da mesma conta Stripe).
	 */
	public static function cleanup(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE sponsor_id = 0 AND created_at < %s',
				self::table(),
				gmdate( 'Y-m-d H:i:s', time() - self::KEEP_ORPHAN_DAYS * DAY_IN_SECONDS )
			)
		);
	}

	/**
	 * Primeira linha em que a coluna tem o valor pedido.
	 *
	 * @param string $column `invoice_id` ou `payment_intent_id`.
	 * @param string $value  Valor procurado.
	 * @return array<string, mixed>|null
	 */
	private static function find_by( string $column, string $value ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria; os webhooks precisam do dado atual.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE %i = %s LIMIT 1', self::table(), $column, $value ), ARRAY_A );

		return $row ? $row : null;
	}
}
