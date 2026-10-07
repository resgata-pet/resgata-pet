<?php
/**
 * Listagem de animais: filtros, cartões e paginação.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/animals-list.php`.
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $rwp_args {
 *     @type string   $wrapper_attributes Atributos HTML do elemento externo (já escapados).
 *     @type int[]    $animal_ids         IDs dos animais encontrados.
 *     @type int      $total              Total de animais encontrados (todas as páginas).
 *     @type int      $columns            Número de colunas.
 *     @type bool     $show_filters       Se mostra o formulário de filtros.
 *     @type array    $filters            Opções dos filtros (species, sex, size).
 *     @type array    $selected           Filtros escolhidos pelo visitante.
 *     @type string   $form_action        URL de destino do formulário.
 *     @type array    $hidden_fields      Parâmetros da URL a preservar.
 *     @type bool     $has_active_filters Se há algum filtro aplicado.
 *     @type string   $pagination         HTML da paginação.
 * }
 */

use ResgataWP\Core\TemplateLoader;
use ResgataWP\Modules\Animals\Frontend\ListRenderer;

defined( 'ABSPATH' ) || exit;

$rwp_filter_fields = array(
	'species' => array( ListRenderer::PARAM_SPECIES, __( 'Espécie', 'resgata-wp' ), __( 'Todas', 'resgata-wp' ) ),
	'sex'     => array( ListRenderer::PARAM_SEX, __( 'Sexo', 'resgata-wp' ), __( 'Todos', 'resgata-wp' ) ),
	'size'    => array( ListRenderer::PARAM_SIZE, __( 'Porte', 'resgata-wp' ), __( 'Todos', 'resgata-wp' ) ),
);
?>
<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<?php if ( $rwp_args['show_filters'] ) : ?>
		<form class="rwp-filters" method="get" action="<?php echo esc_url( $rwp_args['form_action'] ); ?>" aria-label="<?php esc_attr_e( 'Filtrar animais', 'resgata-wp' ); ?>">
			<?php foreach ( $rwp_args['hidden_fields'] as $rwp_name => $rwp_value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $rwp_name ); ?>" value="<?php echo esc_attr( $rwp_value ); ?>">
			<?php endforeach; ?>

			<?php
			foreach ( $rwp_filter_fields as $rwp_key => $rwp_field ) :
				if ( empty( $rwp_args['filters'][ $rwp_key ] ) ) {
					continue;
				}
				$rwp_field_id = wp_unique_id( 'rwp-filter-' );
				?>
				<p class="rwp-filters__field">
					<label for="<?php echo esc_attr( $rwp_field_id ); ?>"><?php echo esc_html( $rwp_field[1] ); ?></label>
					<select data-rwp-select id="<?php echo esc_attr( $rwp_field_id ); ?>" name="<?php echo esc_attr( $rwp_field[0] ); ?>">
						<option value=""><?php echo esc_html( $rwp_field[2] ); ?></option>
						<?php foreach ( $rwp_args['filters'][ $rwp_key ] as $rwp_value => $rwp_label ) : ?>
							<option value="<?php echo esc_attr( $rwp_value ); ?>" <?php selected( $rwp_args['selected'][ $rwp_key ], (string) $rwp_value ); ?>><?php echo esc_html( $rwp_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			<?php endforeach; ?>

			<p class="rwp-filters__actions">
				<button type="submit" class="rwp-button rwp-button--primary wp-element-button"><?php esc_html_e( 'Filtrar', 'resgata-wp' ); ?></button>
				<?php if ( $rwp_args['has_active_filters'] ) : ?>
					<a class="rwp-filters__clear" href="<?php echo esc_url( $rwp_args['form_action'] ); ?>"><?php esc_html_e( 'Limpar filtros', 'resgata-wp' ); ?></a>
				<?php endif; ?>
			</p>
		</form>
	<?php endif; ?>

	<?php if ( $rwp_args['animal_ids'] ) : ?>
		<?php if ( $rwp_args['show_filters'] ) : ?>
			<p class="rwp-animals__count" role="status">
				<?php
				/* translators: %s: quantidade de animais encontrados. */
				echo esc_html( sprintf( _n( '%s animal encontrado', '%s animais encontrados', $rwp_args['total'], 'resgata-wp' ), number_format_i18n( $rwp_args['total'] ) ) );
				?>
			</p>
		<?php endif; ?>
		<ul class="rwp-grid" style="--rwp-columns: <?php echo esc_attr( (string) $rwp_args['columns'] ); ?>;">
			<?php
			foreach ( $rwp_args['animal_ids'] as $rwp_animal_id ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- O template do cartão escapa cada valor.
				echo TemplateLoader::render( 'animal-card.php', array( 'animal' => new ResgataWP\Modules\Animals\Animal( $rwp_animal_id ) ) );
			}
			?>
		</ul>
	<?php else : ?>
		<p class="rwp-empty">
			<?php
			echo esc_html(
				$rwp_args['has_active_filters']
					? __( 'Nenhum animal encontrado com esses filtros. Tente outra combinação.', 'resgata-wp' )
					: __( 'Nenhum animal por aqui no momento. Volte em breve!', 'resgata-wp' )
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $rwp_args['pagination'] ) : ?>
		<nav class="rwp-pagination" aria-label="<?php esc_attr_e( 'Páginas da lista de animais', 'resgata-wp' ); ?>">
			<?php echo wp_kses_post( $rwp_args['pagination'] ); ?>
		</nav>
	<?php endif; ?>
</div>
