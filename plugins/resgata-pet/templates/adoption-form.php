<?php
/**
 * Formulário de adoção.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/adoption-form.php`.
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $resgata_args {
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

use ResgataPet\Modules\Adoptions\Submission;

defined( 'ABSPATH' ) || exit;

$resgata_errors = $resgata_args['errors'];
$resgata_old    = $resgata_args['old'];
$resgata_name   = Submission::FIELD;

if ( $resgata_args['show_animal_select'] && ! $resgata_args['animals'] ) :
	?>
	<div <?php echo $resgata_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
		<p class="resgata-empty"><?php esc_html_e( 'No momento não há animais disponíveis para adoção.', 'resgata-pet' ); ?></p>
	</div>
	<?php
	return;
endif;
?>
<div <?php echo $resgata_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<h2 class="resgata-adoption__title">
		<?php
		echo esc_html(
			'' !== $resgata_args['animal_name'] && ! $resgata_args['show_animal_select']
				/* translators: %s: nome do animal. */
				? sprintf( __( 'Quero adotar %s', 'resgata-pet' ), $resgata_args['animal_name'] )
				: __( 'Quero adotar', 'resgata-pet' )
		);
		?>
	</h2>

	<?php if ( '' !== $resgata_args['adoption_rules'] || '' !== $resgata_args['adoption_fee'] ) : ?>
		<div class="resgata-adoption__rules">
			<?php if ( '' !== $resgata_args['adoption_rules'] ) : ?>
				<h3 class="resgata-adoption__subtitle"><?php esc_html_e( 'Antes de pedir a adoção', 'resgata-pet' ); ?></h3>
				<?php echo wp_kses_post( wpautop( esc_html( $resgata_args['adoption_rules'] ) ) ); ?>
			<?php endif; ?>
			<?php if ( '' !== $resgata_args['adoption_fee'] ) : ?>
				<p><strong><?php esc_html_e( 'Taxa de adoção:', 'resgata-pet' ); ?></strong> <?php echo esc_html( $resgata_args['adoption_fee'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $resgata_errors ) : ?>
		<p class="resgata-adoption__error resgata-adoption__error--form" role="alert">
			<?php echo esc_html( $resgata_errors['_form'] ?? __( 'Confira os campos destacados e envie de novo.', 'resgata-pet' ) ); ?>
		</p>
	<?php endif; ?>

	<form class="resgata-adoption__form" method="post" action="#resgata-adocao" novalidate>
		<?php wp_nonce_field( Submission::NONCE_ACTION, Submission::NONCE_NAME ); ?>

		<?php // Campo invisível contra robôs: pessoas não o veem nem preenchem. ?>
		<p class="resgata-adoption__hp" aria-hidden="true">
			<label for="resgata-adoption-site"><?php esc_html_e( 'Não preencha este campo', 'resgata-pet' ); ?></label>
			<input type="text" id="resgata-adoption-site" name="<?php echo esc_attr( Submission::HONEYPOT ); ?>" value="" tabindex="-1" autocomplete="off">
		</p>

		<?php if ( $resgata_args['show_animal_select'] ) : ?>
			<p class="resgata-adoption__field">
				<label for="resgata-adoption-animal"><?php esc_html_e( 'Qual animal você quer adotar?', 'resgata-pet' ); ?> <span class="resgata-adoption__required" aria-hidden="true">*</span></label>
				<select data-resgata-select id="resgata-adoption-animal" name="<?php echo esc_attr( $resgata_name ); ?>[animal_id]" required <?php echo isset( $resgata_errors['animal_id'] ) ? 'aria-invalid="true" aria-describedby="resgata-adoption-animal-error"' : ''; ?>>
					<option value=""><?php esc_html_e( 'Escolha', 'resgata-pet' ); ?></option>
					<?php foreach ( $resgata_args['animals'] as $resgata_id => $resgata_label ) : ?>
						<option value="<?php echo esc_attr( (string) $resgata_id ); ?>" <?php selected( $resgata_args['animal_id'], $resgata_id ); ?>><?php echo esc_html( $resgata_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php if ( isset( $resgata_errors['animal_id'] ) ) : ?>
					<span class="resgata-adoption__error" id="resgata-adoption-animal-error"><?php echo esc_html( $resgata_errors['animal_id'] ); ?></span>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<input type="hidden" name="<?php echo esc_attr( $resgata_name ); ?>[animal_id]" value="<?php echo esc_attr( (string) $resgata_args['animal_id'] ); ?>">
		<?php endif; ?>

		<?php
		foreach ( $resgata_args['fields'] as $resgata_key => $resgata_field ) :
			$resgata_id       = 'resgata-adoption-' . $resgata_key;
			$resgata_value    = isset( $resgata_old[ $resgata_key ] ) && is_scalar( $resgata_old[ $resgata_key ] ) ? (string) $resgata_old[ $resgata_key ] : '';
			$resgata_error    = $resgata_errors[ $resgata_key ] ?? '';
			$resgata_required = ! empty( $resgata_field['required'] );
			$resgata_input    = sprintf( '%s[%s]', $resgata_name, $resgata_key );
			?>
			<p class="resgata-adoption__field resgata-adoption__field--<?php echo esc_attr( $resgata_field['type'] ); ?>">
				<?php if ( 'checkbox' === $resgata_field['type'] ) : ?>
					<label for="<?php echo esc_attr( $resgata_id ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $resgata_id ); ?>" name="<?php echo esc_attr( $resgata_input ); ?>" value="1" <?php checked( '' !== $resgata_value ); ?> <?php echo $resgata_required ? 'required' : ''; ?> <?php echo $resgata_error ? 'aria-invalid="true" aria-describedby="' . esc_attr( $resgata_id ) . '-error"' : ''; ?>>
						<?php echo esc_html( $resgata_field['label'] ); ?>
					</label>
				<?php else : ?>
					<label for="<?php echo esc_attr( $resgata_id ); ?>">
						<?php echo esc_html( $resgata_field['label'] ); ?>
						<?php if ( $resgata_required ) : ?>
							<span class="resgata-adoption__required" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>

					<?php if ( 'select' === $resgata_field['type'] ) : ?>
						<select data-resgata-select id="<?php echo esc_attr( $resgata_id ); ?>" name="<?php echo esc_attr( $resgata_input ); ?>" <?php echo $resgata_required ? 'required' : ''; ?> <?php echo $resgata_error ? 'aria-invalid="true" aria-describedby="' . esc_attr( $resgata_id ) . '-error"' : ''; ?>>
							<option value=""><?php esc_html_e( 'Escolha', 'resgata-pet' ); ?></option>
							<?php foreach ( $resgata_field['options'] as $resgata_option => $resgata_label ) : ?>
								<option value="<?php echo esc_attr( $resgata_option ); ?>" <?php selected( $resgata_value, (string) $resgata_option ); ?>><?php echo esc_html( $resgata_label ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php elseif ( 'textarea' === $resgata_field['type'] ) : ?>
						<textarea id="<?php echo esc_attr( $resgata_id ); ?>" name="<?php echo esc_attr( $resgata_input ); ?>" rows="4" maxlength="<?php echo esc_attr( (string) $resgata_field['max'] ); ?>" <?php echo $resgata_required ? 'required' : ''; ?> <?php echo $resgata_error ? 'aria-invalid="true" aria-describedby="' . esc_attr( $resgata_id ) . '-error"' : ''; ?>><?php echo esc_textarea( $resgata_value ); ?></textarea>
					<?php else : ?>
						<input type="<?php echo esc_attr( $resgata_field['type'] ); ?>" id="<?php echo esc_attr( $resgata_id ); ?>" name="<?php echo esc_attr( $resgata_input ); ?>" value="<?php echo esc_attr( $resgata_value ); ?>" maxlength="<?php echo esc_attr( (string) $resgata_field['max'] ); ?>" <?php echo isset( $resgata_field['autocomplete'] ) ? 'autocomplete="' . esc_attr( $resgata_field['autocomplete'] ) . '"' : ''; ?> <?php echo $resgata_required ? 'required' : ''; ?> <?php echo $resgata_error ? 'aria-invalid="true" aria-describedby="' . esc_attr( $resgata_id ) . '-error"' : ''; ?>>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( $resgata_error ) : ?>
					<span class="resgata-adoption__error" id="<?php echo esc_attr( $resgata_id ); ?>-error"><?php echo esc_html( $resgata_error ); ?></span>
				<?php endif; ?>
			</p>
		<?php endforeach; ?>

		<div class="resgata-adoption__consent">
			<div class="resgata-adoption__consent-text"><?php echo wp_kses_post( wpautop( esc_html( $resgata_args['consent_text'] ) ) ); ?></div>
			<p class="resgata-adoption__field resgata-adoption__field--checkbox">
				<label for="resgata-adoption-consent">
					<input type="checkbox" id="resgata-adoption-consent" name="<?php echo esc_attr( $resgata_name ); ?>[consent]" value="1" <?php checked( ! empty( $resgata_old['consent'] ) ); ?> required <?php echo isset( $resgata_errors['consent'] ) ? 'aria-invalid="true" aria-describedby="resgata-adoption-consent-error"' : ''; ?>>
					<?php esc_html_e( 'Li e concordo com o uso dos meus dados descrito acima.', 'resgata-pet' ); ?>
				</label>
				<?php if ( isset( $resgata_errors['consent'] ) ) : ?>
					<span class="resgata-adoption__error" id="resgata-adoption-consent-error"><?php echo esc_html( $resgata_errors['consent'] ); ?></span>
				<?php endif; ?>
			</p>
		</div>

		<p class="resgata-adoption__actions">
			<button type="submit" class="resgata-button wp-element-button"><?php esc_html_e( 'Enviar pedido de adoção', 'resgata-pet' ); ?></button>
		</p>
	</form>
</div>
