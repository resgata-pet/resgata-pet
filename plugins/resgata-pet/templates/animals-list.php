<?php
/**
 * Listagem de animais: filtros, cartões e paginação.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/animals-list.php`.
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $resgata_args {
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

use ResgataPet\Core\TemplateLoader;
use ResgataPet\Modules\Animals\Frontend\ListRenderer;

defined( 'ABSPATH' ) || exit;

$resgata_filter_fields = array(
	'species' => array( ListRenderer::PARAM_SPECIES, __( 'Espécie', 'resgata-pet' ), __( 'Todas', 'resgata-pet' ) ),
	'sex'     => array( ListRenderer::PARAM_SEX, __( 'Sexo', 'resgata-pet' ), __( 'Todos', 'resgata-pet' ) ),
	'size'    => array( ListRenderer::PARAM_SIZE, __( 'Porte', 'resgata-pet' ), __( 'Todos', 'resgata-pet' ) ),
);
?>
<div <?php echo wp_kses_data( $resgata_args['wrapper_attributes'] ); ?>>
	<?php if ( $resgata_args['show_filters'] ) : ?>
		<form class="resgata-filters" method="get" action="<?php echo esc_url( $resgata_args['form_action'] ); ?>" aria-label="<?php esc_attr_e( 'Filtrar animais', 'resgata-pet' ); ?>">
			<?php foreach ( $resgata_args['hidden_fields'] as $resgata_name => $resgata_value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $resgata_name ); ?>" value="<?php echo esc_attr( $resgata_value ); ?>">
			<?php endforeach; ?>

			<?php
			foreach ( $resgata_filter_fields as $resgata_key => $resgata_field ) :
				if ( empty( $resgata_args['filters'][ $resgata_key ] ) ) {
					continue;
				}
				$resgata_field_id = wp_unique_id( 'resgata-filter-' );
				?>
				<p class="resgata-filters__field">
					<label for="<?php echo esc_attr( $resgata_field_id ); ?>"><?php echo esc_html( $resgata_field[1] ); ?></label>
					<select data-resgata-select id="<?php echo esc_attr( $resgata_field_id ); ?>" name="<?php echo esc_attr( $resgata_field[0] ); ?>">
						<option value=""><?php echo esc_html( $resgata_field[2] ); ?></option>
						<?php foreach ( $resgata_args['filters'][ $resgata_key ] as $resgata_value => $resgata_label ) : ?>
							<option value="<?php echo esc_attr( $resgata_value ); ?>" <?php selected( $resgata_args['selected'][ $resgata_key ], (string) $resgata_value ); ?>><?php echo esc_html( $resgata_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			<?php endforeach; ?>

			<p class="resgata-filters__actions">
				<button type="submit" class="resgata-button resgata-button--primary wp-element-button"><?php esc_html_e( 'Filtrar', 'resgata-pet' ); ?></button>
				<?php if ( $resgata_args['has_active_filters'] ) : ?>
					<a class="resgata-filters__clear" href="<?php echo esc_url( $resgata_args['form_action'] ); ?>"><?php esc_html_e( 'Limpar filtros', 'resgata-pet' ); ?></a>
				<?php endif; ?>
			</p>
		</form>
	<?php endif; ?>

	<?php if ( $resgata_args['animal_ids'] ) : ?>
		<?php if ( $resgata_args['show_filters'] ) : ?>
			<p class="resgata-animals__count" role="status">
				<?php
				/* translators: %s: quantidade de animais encontrados. */
				echo esc_html( sprintf( _n( '%s animal encontrado', '%s animais encontrados', $resgata_args['total'], 'resgata-pet' ), number_format_i18n( $resgata_args['total'] ) ) );
				?>
			</p>
		<?php endif; ?>
		<ul class="resgata-grid" style="--resgata-columns: <?php echo esc_attr( (string) $resgata_args['columns'] ); ?>;">
			<?php
			foreach ( $resgata_args['animal_ids'] as $resgata_animal_id ) {
				TemplateLoader::display( 'animal-card.php', array( 'animal' => new ResgataPet\Modules\Animals\Animal( $resgata_animal_id ) ) );
			}
			?>
		</ul>
	<?php else : ?>
		<p class="resgata-empty <?php echo esc_attr( $resgata_args['has_active_filters'] ? 'resgata-empty--filtered' : 'resgata-empty--none' ); ?>">
			<?php
			echo esc_html(
				$resgata_args['has_active_filters']
					? __( 'Nenhum animal encontrado com esses filtros. Tente outra combinação.', 'resgata-pet' )
					: __( 'Nenhum animal por aqui no momento. Volte em breve!', 'resgata-pet' )
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $resgata_args['pagination'] ) : ?>
		<nav class="resgata-pagination" aria-label="<?php esc_attr_e( 'Páginas da lista de animais', 'resgata-pet' ); ?>">
			<?php echo wp_kses_post( $resgata_args['pagination'] ); ?>
		</nav>
	<?php endif; ?>
</div>
