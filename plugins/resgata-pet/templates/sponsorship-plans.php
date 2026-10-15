<?php
/**
 * Planos de apadrinhamento: contador de padrinhos e um cartão por valor mensal.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/sponsorship-plans.php`.
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $resgata_args {
 *     @type string                $wrapper_attributes Atributos HTML do elemento externo (já escapados).
 *     @type array<int, array>     $plans              Planos à venda: price_id, amount (formatado), name, description, benefits e featured.
 *     @type bool                  $show_counter       Se mostra o contador de padrinhos.
 *     @type int                   $counter_total      Padrinhos ativos.
 *     @type string                $counter_updated    Data da última mudança do contador (vazia se nunca mudou).
 *     @type bool                  $thank_you          Se a pessoa acabou de voltar do pagamento.
 *     @type string                $error              Erro do último envio.
 * }
 */

use ResgataPet\Core\Html;
use ResgataPet\Core\Icons;
use ResgataPet\Modules\Sponsorship\Checkout;

defined( 'ABSPATH' ) || exit;

// Cada cartão de plano usa uma das cores de apoio, em sequência.
$resgata_tones = array( 'green', 'blue', 'orange', 'purple' );
?>
<div <?php echo wp_kses_data( $resgata_args['wrapper_attributes'] ); ?>>
	<?php if ( $resgata_args['show_counter'] ) : ?>
		<p class="resgata-sponsorship__counter">
			<strong class="resgata-sponsorship__count"><?php echo esc_html( number_format_i18n( $resgata_args['counter_total'] ) ); ?></strong>
			<span class="resgata-sponsorship__count-label">
				<?php echo esc_html( _n( 'padrinho ativo', 'padrinhos ativos', $resgata_args['counter_total'], 'resgata-pet' ) ); ?>
			</span>
			<?php if ( '' !== $resgata_args['counter_updated'] ) : ?>
				<small class="resgata-sponsorship__updated">
					<?php
					/* translators: %s: data. */
					echo esc_html( sprintf( __( 'Atualizado em %s', 'resgata-pet' ), $resgata_args['counter_updated'] ) );
					?>
				</small>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ( $resgata_args['thank_you'] ) : ?>
		<div class="resgata-sponsorship__success" role="status">
			<h2 class="resgata-sponsorship__title"><?php esc_html_e( 'Obrigado por apadrinhar!', 'resgata-pet' ); ?></h2>
			<p><?php esc_html_e( 'Você vai receber a confirmação do pagamento por e-mail. Sua ajuda chega todo mês para cuidar dos animais.', 'resgata-pet' ); ?></p>
		</div>
	<?php else : ?>
		<form class="resgata-sponsorship__form" method="post" action="#resgata-apadrinhar">
			<?php wp_nonce_field( Checkout::NONCE_ACTION, Checkout::NONCE_NAME ); ?>

			<h2 class="resgata-sponsorship__title"><?php esc_html_e( 'Escolha o valor que mais combina com você', 'resgata-pet' ); ?></h2>
			<p class="resgata-sponsorship__note">
				<?php esc_html_e( 'Todos os valores são mensais, pagos com cartão de crédito na página segura da Stripe. Você pode mudar o valor ou cancelar quando quiser.', 'resgata-pet' ); ?>
			</p>

			<?php if ( '' !== $resgata_args['error'] ) : ?>
				<p class="resgata-sponsorship__error" role="alert"><?php echo esc_html( $resgata_args['error'] ); ?></p>
			<?php endif; ?>

			<?php // Cada cartão tem o seu botão: um toque já leva ao pagamento do valor escolhido. ?>
			<ul class="resgata-sponsorship__plans">
				<?php
				foreach ( $resgata_args['plans'] as $resgata_index => $resgata_plan ) :
					$resgata_tone = $resgata_tones[ $resgata_index % count( $resgata_tones ) ];
					?>
					<li class="resgata-sponsorship__plan resgata-sponsorship__plan--<?php echo esc_attr( $resgata_tone ); ?><?php echo $resgata_plan['featured'] ? ' resgata-sponsorship__plan--featured' : ''; ?>">
						<?php if ( $resgata_plan['featured'] ) : ?>
							<span class="resgata-sponsorship__flag"><?php esc_html_e( 'Mais escolhido', 'resgata-pet' ); ?></span>
						<?php endif; ?>
						<div class="resgata-sponsorship__plan-head">
							<span class="resgata-sponsorship__plan-icon"><?php echo wp_kses( Icons::svg( 'paw-print' ), Html::svg() ); ?></span>
							<?php if ( '' !== $resgata_plan['name'] ) : ?>
								<h3 class="resgata-sponsorship__plan-name"><?php echo esc_html( $resgata_plan['name'] ); ?></h3>
							<?php endif; ?>
						</div>
						<p class="resgata-sponsorship__price">
							<strong><?php echo esc_html( $resgata_plan['amount'] ); ?></strong>
							<span><?php esc_html_e( '/mês', 'resgata-pet' ); ?></span>
						</p>
						<?php if ( '' !== $resgata_plan['description'] ) : ?>
							<p class="resgata-sponsorship__plan-text"><?php echo esc_html( $resgata_plan['description'] ); ?></p>
						<?php endif; ?>
						<?php if ( $resgata_plan['benefits'] ) : ?>
							<ul class="resgata-sponsorship__benefits">
								<?php foreach ( $resgata_plan['benefits'] as $resgata_benefit ) : ?>
									<li>
										<?php echo wp_kses( Icons::svg( 'check' ), Html::svg() ); ?>
										<?php echo esc_html( $resgata_benefit ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<button type="submit" class="resgata-button resgata-sponsorship__choose" name="<?php echo esc_attr( Checkout::FIELD ); ?>" value="<?php echo esc_attr( $resgata_plan['price_id'] ); ?>">
							<?php esc_html_e( 'Quero ser padrinho', 'resgata-pet' ); ?>
							<span class="screen-reader-text">
								<?php
								echo esc_html(
									'' !== $resgata_plan['name']
										/* translators: 1: nome do plano, 2: valor, ex.: R$ 15. */
										? sprintf( __( 'no plano %1$s, com %2$s por mês', 'resgata-pet' ), $resgata_plan['name'], $resgata_plan['amount'] )
										/* translators: %s: valor, ex.: R$ 15. */
										: sprintf( __( 'com %s por mês', 'resgata-pet' ), $resgata_plan['amount'] )
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
