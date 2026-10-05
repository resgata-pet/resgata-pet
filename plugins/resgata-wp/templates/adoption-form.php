<?php
/**
 * Formulário de adoção.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/adoption-form.php`.
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $rwp_args {
 *     @type string $wrapper_attributes Atributos HTML do elemento externo (já escapados).
 *     @type array  $fields             Perguntas (ver Questionnaire::fields()).
 *     @type int    $animal_id          Animal do pedido (0 se ainda não escolhido).
 *     @type string $animal_name        Nome do animal.
 *     @type array  $animals            Animais disponíveis (ID => nome), quando há escolha.
 *     @type bool   $show_animal_select Se a pessoa escolhe o animal no formulário.
 *     @type string $consent_text       Texto de consentimento.
 *     @type array  $errors             Erros por campo (`_form` = erro geral).
 *     @type array  $old                Valores enviados, para repreencher.
 *     @type string $adoption_rules     Regras de adoção cadastradas pela protetora.
 *     @type string $adoption_fee       Taxa de adoção cadastrada pela protetora.
 * }
 */

use ResgataWP\Modules\Adoptions\Submission;

defined( 'ABSPATH' ) || exit;

$rwp_errors = $rwp_args['errors'];
$rwp_old    = $rwp_args['old'];
$rwp_name   = Submission::FIELD;

if ( $rwp_args['show_animal_select'] && ! $rwp_args['animals'] ) :
	?>
	<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
		<p class="rwp-empty"><?php esc_html_e( 'No momento não há animais disponíveis para adoção.', 'resgata-wp' ); ?></p>
	</div>
	<?php
	return;
endif;
?>
<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<h2 class="rwp-adoption__title">
		<?php
		echo esc_html(
			'' !== $rwp_args['animal_name'] && ! $rwp_args['show_animal_select']
				/* translators: %s: nome do animal. */
				? sprintf( __( 'Quero adotar %s', 'resgata-wp' ), $rwp_args['animal_name'] )
				: __( 'Quero adotar', 'resgata-wp' )
		);
		?>
	</h2>

	<?php if ( '' !== $rwp_args['adoption_rules'] || '' !== $rwp_args['adoption_fee'] ) : ?>
		<div class="rwp-adoption__rules">
			<?php if ( '' !== $rwp_args['adoption_rules'] ) : ?>
				<h3 class="rwp-adoption__subtitle"><?php esc_html_e( 'Antes de pedir a adoção', 'resgata-wp' ); ?></h3>
				<?php echo wp_kses_post( wpautop( esc_html( $rwp_args['adoption_rules'] ) ) ); ?>
			<?php endif; ?>
			<?php if ( '' !== $rwp_args['adoption_fee'] ) : ?>
				<p><strong><?php esc_html_e( 'Taxa de adoção:', 'resgata-wp' ); ?></strong> <?php echo esc_html( $rwp_args['adoption_fee'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $rwp_errors ) : ?>
		<p class="rwp-adoption__error rwp-adoption__error--form" role="alert">
			<?php echo esc_html( $rwp_errors['_form'] ?? __( 'Confira os campos destacados e envie de novo.', 'resgata-wp' ) ); ?>
		</p>
	<?php endif; ?>

	<form class="rwp-adoption__form" method="post" action="#rwp-adocao" novalidate>
		<?php wp_nonce_field( Submission::NONCE_ACTION, Submission::NONCE_NAME ); ?>

		<?php // Campo invisível contra robôs: pessoas não o veem nem preenchem. ?>
		<p class="rwp-adoption__hp" aria-hidden="true">
			<label for="rwp-adoption-site"><?php esc_html_e( 'Não preencha este campo', 'resgata-wp' ); ?></label>
			<input type="text" id="rwp-adoption-site" name="<?php echo esc_attr( Submission::HONEYPOT ); ?>" value="" tabindex="-1" autocomplete="off">
		</p>

		<?php if ( $rwp_args['show_animal_select'] ) : ?>
			<p class="rwp-adoption__field">
				<label for="rwp-adoption-animal"><?php esc_html_e( 'Qual animal você quer adotar?', 'resgata-wp' ); ?> <span class="rwp-adoption__required" aria-hidden="true">*</span></label>
				<select id="rwp-adoption-animal" name="<?php echo esc_attr( $rwp_name ); ?>[animal_id]" required <?php echo isset( $rwp_errors['animal_id'] ) ? 'aria-invalid="true" aria-describedby="rwp-adoption-animal-error"' : ''; ?>>
					<option value=""><?php esc_html_e( 'Escolha', 'resgata-wp' ); ?></option>
					<?php foreach ( $rwp_args['animals'] as $rwp_id => $rwp_label ) : ?>
						<option value="<?php echo esc_attr( (string) $rwp_id ); ?>" <?php selected( $rwp_args['animal_id'], $rwp_id ); ?>><?php echo esc_html( $rwp_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php if ( isset( $rwp_errors['animal_id'] ) ) : ?>
					<span class="rwp-adoption__error" id="rwp-adoption-animal-error"><?php echo esc_html( $rwp_errors['animal_id'] ); ?></span>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<input type="hidden" name="<?php echo esc_attr( $rwp_name ); ?>[animal_id]" value="<?php echo esc_attr( (string) $rwp_args['animal_id'] ); ?>">
		<?php endif; ?>

		<?php
		foreach ( $rwp_args['fields'] as $rwp_key => $rwp_field ) :
			$rwp_id       = 'rwp-adoption-' . $rwp_key;
			$rwp_value    = isset( $rwp_old[ $rwp_key ] ) && is_scalar( $rwp_old[ $rwp_key ] ) ? (string) $rwp_old[ $rwp_key ] : '';
			$rwp_error    = $rwp_errors[ $rwp_key ] ?? '';
			$rwp_required = ! empty( $rwp_field['required'] );
			$rwp_input    = sprintf( '%s[%s]', $rwp_name, $rwp_key );
			?>
			<p class="rwp-adoption__field rwp-adoption__field--<?php echo esc_attr( $rwp_field['type'] ); ?>">
				<?php if ( 'checkbox' === $rwp_field['type'] ) : ?>
					<label for="<?php echo esc_attr( $rwp_id ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $rwp_id ); ?>" name="<?php echo esc_attr( $rwp_input ); ?>" value="1" <?php checked( '' !== $rwp_value ); ?> <?php echo $rwp_required ? 'required' : ''; ?> <?php echo $rwp_error ? 'aria-invalid="true" aria-describedby="' . esc_attr( $rwp_id ) . '-error"' : ''; ?>>
						<?php echo esc_html( $rwp_field['label'] ); ?>
					</label>
				<?php else : ?>
					<label for="<?php echo esc_attr( $rwp_id ); ?>">
						<?php echo esc_html( $rwp_field['label'] ); ?>
						<?php if ( $rwp_required ) : ?>
							<span class="rwp-adoption__required" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>

					<?php if ( 'select' === $rwp_field['type'] ) : ?>
						<select id="<?php echo esc_attr( $rwp_id ); ?>" name="<?php echo esc_attr( $rwp_input ); ?>" <?php echo $rwp_required ? 'required' : ''; ?> <?php echo $rwp_error ? 'aria-invalid="true" aria-describedby="' . esc_attr( $rwp_id ) . '-error"' : ''; ?>>
							<option value=""><?php esc_html_e( 'Escolha', 'resgata-wp' ); ?></option>
							<?php foreach ( $rwp_field['options'] as $rwp_option => $rwp_label ) : ?>
								<option value="<?php echo esc_attr( $rwp_option ); ?>" <?php selected( $rwp_value, (string) $rwp_option ); ?>><?php echo esc_html( $rwp_label ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php elseif ( 'textarea' === $rwp_field['type'] ) : ?>
						<textarea id="<?php echo esc_attr( $rwp_id ); ?>" name="<?php echo esc_attr( $rwp_input ); ?>" rows="4" maxlength="<?php echo esc_attr( (string) $rwp_field['max'] ); ?>" <?php echo $rwp_required ? 'required' : ''; ?> <?php echo $rwp_error ? 'aria-invalid="true" aria-describedby="' . esc_attr( $rwp_id ) . '-error"' : ''; ?>><?php echo esc_textarea( $rwp_value ); ?></textarea>
					<?php else : ?>
						<input type="<?php echo esc_attr( $rwp_field['type'] ); ?>" id="<?php echo esc_attr( $rwp_id ); ?>" name="<?php echo esc_attr( $rwp_input ); ?>" value="<?php echo esc_attr( $rwp_value ); ?>" maxlength="<?php echo esc_attr( (string) $rwp_field['max'] ); ?>" <?php echo isset( $rwp_field['autocomplete'] ) ? 'autocomplete="' . esc_attr( $rwp_field['autocomplete'] ) . '"' : ''; ?> <?php echo $rwp_required ? 'required' : ''; ?> <?php echo $rwp_error ? 'aria-invalid="true" aria-describedby="' . esc_attr( $rwp_id ) . '-error"' : ''; ?>>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( $rwp_error ) : ?>
					<span class="rwp-adoption__error" id="<?php echo esc_attr( $rwp_id ); ?>-error"><?php echo esc_html( $rwp_error ); ?></span>
				<?php endif; ?>
			</p>
		<?php endforeach; ?>

		<div class="rwp-adoption__consent">
			<div class="rwp-adoption__consent-text"><?php echo wp_kses_post( wpautop( esc_html( $rwp_args['consent_text'] ) ) ); ?></div>
			<p class="rwp-adoption__field rwp-adoption__field--checkbox">
				<label for="rwp-adoption-consent">
					<input type="checkbox" id="rwp-adoption-consent" name="<?php echo esc_attr( $rwp_name ); ?>[consent]" value="1" <?php checked( ! empty( $rwp_old['consent'] ) ); ?> required <?php echo isset( $rwp_errors['consent'] ) ? 'aria-invalid="true" aria-describedby="rwp-adoption-consent-error"' : ''; ?>>
					<?php esc_html_e( 'Li e concordo com o uso dos meus dados descrito acima.', 'resgata-wp' ); ?>
				</label>
				<?php if ( isset( $rwp_errors['consent'] ) ) : ?>
					<span class="rwp-adoption__error" id="rwp-adoption-consent-error"><?php echo esc_html( $rwp_errors['consent'] ); ?></span>
				<?php endif; ?>
			</p>
		</div>

		<p class="rwp-adoption__actions">
			<button type="submit" class="rwp-button wp-element-button"><?php esc_html_e( 'Enviar pedido de adoção', 'resgata-wp' ); ?></button>
		</p>
	</form>
</div>
