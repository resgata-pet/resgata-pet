<?php
/**
 * Planos de apadrinhamento: contador de padrinhos e um cartão por valor mensal.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/sponsorship-plans.php`.
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $rwp_args {
 *     @type string                $wrapper_attributes Atributos HTML do elemento externo (já escapados).
 *     @type array<int, array>     $plans              Planos à venda: price_id, amount (formatado), name, description, benefits e featured.
 *     @type bool                  $show_counter       Se mostra o contador de padrinhos.
 *     @type int                   $counter_total      Padrinhos ativos.
 *     @type string                $counter_updated    Data da última mudança do contador (vazia se nunca mudou).
 *     @type bool                  $thank_you          Se a pessoa acabou de voltar do pagamento.
 *     @type string                $error              Erro do último envio.
 * }
 */

use ResgataWP\Core\Icons;
use ResgataWP\Modules\Sponsorship\Checkout;

defined( 'ABSPATH' ) || exit;

// Cada cartão de plano usa uma das cores de apoio, em sequência.
$rwp_tones = array( 'green', 'blue', 'orange', 'purple' );
?>
<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<?php if ( $rwp_args['show_counter'] ) : ?>
		<p class="rwp-sponsorship__counter">
			<strong class="rwp-sponsorship__count"><?php echo esc_html( number_format_i18n( $rwp_args['counter_total'] ) ); ?></strong>
			<span class="rwp-sponsorship__count-label">
				<?php echo esc_html( _n( 'padrinho ativo', 'padrinhos ativos', $rwp_args['counter_total'], 'resgata-wp' ) ); ?>
			</span>
			<?php if ( '' !== $rwp_args['counter_updated'] ) : ?>
				<small class="rwp-sponsorship__updated">
					<?php
					/* translators: %s: data. */
					echo esc_html( sprintf( __( 'Atualizado em %s', 'resgata-wp' ), $rwp_args['counter_updated'] ) );
					?>
				</small>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ( $rwp_args['thank_you'] ) : ?>
		<div class="rwp-sponsorship__success" role="status">
			<h2 class="rwp-sponsorship__title"><?php esc_html_e( 'Obrigado por apadrinhar!', 'resgata-wp' ); ?></h2>
			<p><?php esc_html_e( 'Você vai receber a confirmação do pagamento por e-mail. Sua ajuda chega todo mês para cuidar dos animais.', 'resgata-wp' ); ?></p>
		</div>
	<?php else : ?>
		<form class="rwp-sponsorship__form" method="post" action="#rwp-apadrinhar">
			<?php wp_nonce_field( Checkout::NONCE_ACTION, Checkout::NONCE_NAME ); ?>

			<h2 class="rwp-sponsorship__title"><?php esc_html_e( 'Escolha o valor que mais combina com você', 'resgata-wp' ); ?></h2>
			<p class="rwp-sponsorship__note">
				<?php esc_html_e( 'Todos os valores são mensais, pagos com cartão de crédito na página segura da Stripe. Você pode mudar o valor ou cancelar quando quiser.', 'resgata-wp' ); ?>
			</p>

			<?php if ( '' !== $rwp_args['error'] ) : ?>
				<p class="rwp-sponsorship__error" role="alert"><?php echo esc_html( $rwp_args['error'] ); ?></p>
			<?php endif; ?>

			<?php // Cada cartão tem o seu botão: um toque já leva ao pagamento do valor escolhido. ?>
			<ul class="rwp-sponsorship__plans">
				<?php
				foreach ( $rwp_args['plans'] as $rwp_index => $rwp_plan ) :
					$rwp_tone = $rwp_tones[ $rwp_index % count( $rwp_tones ) ];
					?>
					<li class="rwp-sponsorship__plan rwp-sponsorship__plan--<?php echo esc_attr( $rwp_tone ); ?><?php echo $rwp_plan['featured'] ? ' rwp-sponsorship__plan--featured' : ''; ?>">
						<?php if ( $rwp_plan['featured'] ) : ?>
							<span class="rwp-sponsorship__flag"><?php esc_html_e( 'Mais escolhido', 'resgata-wp' ); ?></span>
						<?php endif; ?>
						<div class="rwp-sponsorship__plan-head">
							<span class="rwp-sponsorship__plan-icon"><?php echo Icons::svg( 'paw-print' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do plugin. ?></span>
							<?php if ( '' !== $rwp_plan['name'] ) : ?>
								<h3 class="rwp-sponsorship__plan-name"><?php echo esc_html( $rwp_plan['name'] ); ?></h3>
							<?php endif; ?>
						</div>
						<p class="rwp-sponsorship__price">
							<strong><?php echo esc_html( $rwp_plan['amount'] ); ?></strong>
							<span><?php esc_html_e( '/mês', 'resgata-wp' ); ?></span>
						</p>
						<?php if ( '' !== $rwp_plan['description'] ) : ?>
							<p class="rwp-sponsorship__plan-text"><?php echo esc_html( $rwp_plan['description'] ); ?></p>
						<?php endif; ?>
						<?php if ( $rwp_plan['benefits'] ) : ?>
							<ul class="rwp-sponsorship__benefits">
								<?php foreach ( $rwp_plan['benefits'] as $rwp_benefit ) : ?>
									<li>
										<?php echo Icons::svg( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do plugin. ?>
										<?php echo esc_html( $rwp_benefit ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<button type="submit" class="rwp-button rwp-sponsorship__choose" name="<?php echo esc_attr( Checkout::FIELD ); ?>" value="<?php echo esc_attr( $rwp_plan['price_id'] ); ?>">
							<?php esc_html_e( 'Quero ser padrinho', 'resgata-wp' ); ?>
							<span class="screen-reader-text">
								<?php
								echo esc_html(
									'' !== $rwp_plan['name']
										/* translators: 1: nome do plano, 2: valor, ex.: R$ 15. */
										? sprintf( __( 'no plano %1$s, com %2$s por mês', 'resgata-wp' ), $rwp_plan['name'], $rwp_plan['amount'] )
										/* translators: %s: valor, ex.: R$ 15. */
										: sprintf( __( 'com %s por mês', 'resgata-wp' ), $rwp_plan['amount'] )
								);
								?>
							</span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</form>
	<?php endif; ?>
</div>
