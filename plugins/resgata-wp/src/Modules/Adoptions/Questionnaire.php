<?php
/**
 * Questionário de adoção.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Adoptions;

defined( 'ABSPATH' ) || exit;

/**
 * Perguntas do pedido de adoção: usadas para montar o formulário público,
 * validar o envio, mostrar as respostas no painel e montar a mensagem do WhatsApp.
 */
final class Questionnaire {

	/**
	 * Perguntas, na ordem em que aparecem.
	 *
	 * Cada pergunta: `label`, `type` (text, tel, email, select, textarea, checkbox),
	 * `required`, `max` (tamanho máximo) e, para select, `options`.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(): array {
		$fields = array(
			'name'             => array(
				'label'        => __( 'Seu nome completo', 'resgata-wp' ),
				'short'        => __( 'Nome', 'resgata-wp' ),
				'type'         => 'text',
				'required'     => true,
				'max'          => 120,
				'autocomplete' => 'name',
			),
			'whatsapp'         => array(
				'label'        => __( 'Seu WhatsApp (com DDD)', 'resgata-wp' ),
				'short'        => __( 'WhatsApp', 'resgata-wp' ),
				'type'         => 'tel',
				'required'     => true,
				'max'          => 20,
				'autocomplete' => 'tel',
			),
			'email'            => array(
				'label'        => __( 'Seu e-mail (opcional)', 'resgata-wp' ),
				'short'        => __( 'E-mail', 'resgata-wp' ),
				'type'         => 'email',
				'required'     => false,
				'max'          => 120,
				'autocomplete' => 'email',
			),
			'city'             => array(
				'label'    => __( 'Cidade e bairro onde você mora', 'resgata-wp' ),
				'short'    => __( 'Cidade e bairro', 'resgata-wp' ),
				'type'     => 'text',
				'required' => true,
				'max'      => 150,
			),
			'housing'          => array(
				'label'    => __( 'Você mora em', 'resgata-wp' ),
				'short'    => __( 'Moradia', 'resgata-wp' ),
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					'house'     => __( 'Casa', 'resgata-wp' ),
					'apartment' => __( 'Apartamento', 'resgata-wp' ),
					'farm'      => __( 'Sítio ou chácara', 'resgata-wp' ),
				),
			),
			'housing_owner'    => array(
				'label'    => __( 'O imóvel é', 'resgata-wp' ),
				'short'    => __( 'Imóvel', 'resgata-wp' ),
				'type'     => 'select',
				'required' => false,
				'options'  => array(
					'own'    => __( 'Próprio', 'resgata-wp' ),
					'rented' => __( 'Alugado', 'resgata-wp' ),
					'family' => __( 'De familiares', 'resgata-wp' ),
				),
			),
			'safe_home'        => array(
				'label'    => __( 'O imóvel é seguro contra fugas (telas nas janelas, muros, portões)?', 'resgata-wp' ),
				'short'    => __( 'Seguro contra fugas', 'resgata-wp' ),
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					'yes'  => __( 'Sim', 'resgata-wp' ),
					'will' => __( 'Vou adaptar antes da adoção', 'resgata-wp' ),
					'no'   => __( 'Não', 'resgata-wp' ),
				),
			),
			'household_agrees' => array(
				'label'    => __( 'Todas as pessoas da casa concordam com a adoção?', 'resgata-wp' ),
				'short'    => __( 'Todos concordam', 'resgata-wp' ),
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					'yes'   => __( 'Sim, todos concordam', 'resgata-wp' ),
					'alone' => __( 'Moro sozinho(a)', 'resgata-wp' ),
					'no'    => __( 'Ainda não conversei com todos', 'resgata-wp' ),
				),
			),
			'children'         => array(
				'label'    => __( 'Há crianças na casa?', 'resgata-wp' ),
				'short'    => __( 'Crianças na casa', 'resgata-wp' ),
				'type'     => 'select',
				'required' => false,
				'options'  => array(
					'no'  => __( 'Não', 'resgata-wp' ),
					'yes' => __( 'Sim', 'resgata-wp' ),
				),
			),
			'other_pets'       => array(
				'label'    => __( 'Você tem outros animais? Quais?', 'resgata-wp' ),
				'short'    => __( 'Outros animais', 'resgata-wp' ),
				'type'     => 'text',
				'required' => false,
				'max'      => 200,
			),
			'hours_alone'      => array(
				'label'    => __( 'Quantas horas por dia o animal ficaria sozinho?', 'resgata-wp' ),
				'short'    => __( 'Horas sozinho por dia', 'resgata-wp' ),
				'type'     => 'text',
				'required' => false,
				'max'      => 50,
			),
			'experience'       => array(
				'label'    => __( 'Você já teve animais antes? Conte um pouco.', 'resgata-wp' ),
				'short'    => __( 'Experiência com animais', 'resgata-wp' ),
				'type'     => 'textarea',
				'required' => false,
				'max'      => 600,
			),
			'reason'           => array(
				'label'    => __( 'Por que você quer adotar?', 'resgata-wp' ),
				'short'    => __( 'Por que quer adotar', 'resgata-wp' ),
				'type'     => 'textarea',
				'required' => true,
				'max'      => 600,
			),
			'adult'            => array(
				'label'    => __( 'Tenho 18 anos ou mais.', 'resgata-wp' ),
				'short'    => __( 'Maior de 18 anos', 'resgata-wp' ),
				'type'     => 'checkbox',
				'required' => true,
			),
		);

		/**
		 * Filtra as perguntas do questionário de adoção.
		 *
		 * @param array<string, array<string, mixed>> $fields Perguntas.
		 */
		return (array) apply_filters( 'resgata_adoption_questionnaire_fields', $fields );
	}

	/**
	 * Limpa e valida as respostas enviadas.
	 *
	 * @param array<string, mixed> $input Valores enviados, sem barras de escape.
	 * @return array{answers: array<string, string>, errors: array<string, string>}
	 */
	public static function validate( array $input ): array {
		$answers = array();
		$errors  = array();

		foreach ( self::fields() as $key => $field ) {
			$raw = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? (string) $input[ $key ] : '';

			switch ( $field['type'] ) {
				case 'checkbox':
					$value = '' !== $raw && '0' !== $raw ? 'yes' : '';
					break;

				case 'select':
					$value = sanitize_key( $raw );
					$value = isset( $field['options'][ $value ] ) ? $value : '';
					break;

				case 'email':
					$value = sanitize_email( $raw );
					if ( '' !== trim( $raw ) && ! is_email( $value ) ) {
						$errors[ $key ] = __( 'Confira o e-mail: o formato não parece válido.', 'resgata-wp' );
					}
					break;

				case 'tel':
					$value = (string) preg_replace( '/\D+/', '', $raw );
					if ( '' !== $value && ( strlen( $value ) < 10 || strlen( $value ) > 13 ) ) {
						$errors[ $key ] = __( 'Informe o número com DDD. Ex.: (11) 91234-5678.', 'resgata-wp' );
					}
					break;

				case 'textarea':
					$value = sanitize_textarea_field( $raw );
					break;

				default:
					$value = sanitize_text_field( $raw );
			}

			if ( isset( $field['max'] ) ) {
				$value = mb_substr( $value, 0, (int) $field['max'] );
			}

			if ( ! empty( $field['required'] ) && '' === $value && ! isset( $errors[ $key ] ) ) {
				$errors[ $key ] = 'checkbox' === $field['type']
					? __( 'Marque esta opção para continuar.', 'resgata-wp' )
					: __( 'Preencha este campo.', 'resgata-wp' );
			}

			$answers[ $key ] = $value;
		}

		return array(
			'answers' => $answers,
			'errors'  => $errors,
		);
	}

	/**
	 * Respostas prontas para leitura: rótulo curto => texto (sem campos vazios).
	 *
	 * @param array<string, mixed> $answers Respostas gravadas.
	 * @return array<int, array{key: string, label: string, value: string}>
	 */
	public static function readable( array $answers ): array {
		$rows = array();

		foreach ( self::fields() as $key => $field ) {
			$value = isset( $answers[ $key ] ) && is_scalar( $answers[ $key ] ) ? (string) $answers[ $key ] : '';

			if ( '' === $value ) {
				continue;
			}

			if ( 'select' === $field['type'] ) {
				$value = (string) ( $field['options'][ $value ] ?? $value );
			} elseif ( 'checkbox' === $field['type'] ) {
				$value = __( 'Sim', 'resgata-wp' );
			} elseif ( 'tel' === $field['type'] ) {
				$value = \ResgataWP\Modules\Settings\Organization::format_phone( $value );
			}

			$rows[] = array(
				'key'   => $key,
				'label' => (string) ( $field['short'] ?? $field['label'] ),
				'value' => $value,
			);
		}

		return $rows;
	}
}
