import { useQuery } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import { CheckCircle2Icon, CircleAlertIcon, CircleDashedIcon, Loader2Icon, RefreshCwIcon } from 'lucide-react';
import { api, errorMessage, type SponsorshipOverview } from '@/lib/api';
import { config } from '@/lib/config';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { StripeKeysForm } from '@/components/stripe-keys-form';

/** Um item de lista: feito (verde), pendente (alerta) ou ainda não conferido (`done` indefinido). */
function CheckItem( { done, children }: { done?: boolean; children: string } ) {
	if ( done === undefined ) {
		return (
			<li className="text-muted-foreground flex items-start gap-2">
				<CircleDashedIcon className="mt-0.5 size-5 shrink-0" aria-hidden="true" />
				<span>{ children }</span>
			</li>
		);
	}

	const Icon = done ? CheckCircle2Icon : CircleAlertIcon;

	return (
		<li className="flex items-start gap-2">
			<Icon className={ done ? 'text-primary mt-0.5 size-5 shrink-0' : 'mt-0.5 size-5 shrink-0 text-amber-600' } aria-hidden="true" />
			<span>
				<span className="sr-only">{ done ? __( 'Funcionando:', 'resgata-pet' ) : __( 'Pendente:', 'resgata-pet' ) } </span>
				{ children }
			</span>
		</li>
	);
}

/** Permissões que a chave restrita precisa ter, com o nome que aparece na Stripe. */
const STRIPE_PERMISSIONS: Record< string, string > = {
	products: __( 'Products (escrita): criar e renomear os planos', 'resgata-pet' ),
	prices: __( 'Prices (escrita): definir o valor mensal de cada plano', 'resgata-pet' ),
	checkout: __( 'Checkout Sessions (escrita): abrir a página de pagamento', 'resgata-pet' ),
	portal_config: __( 'Customer portal configurations (escrita): preparar a área do padrinho', 'resgata-pet' ),
	portal_session: __( 'Customer portal sessions (escrita): abrir a área do padrinho', 'resgata-pet' ),
};

/** O que precisa estar liberado na chave da Stripe, com a situação de cada item conferida na hora. */
function StripePermissionsList( { hasKey }: { hasKey: boolean } ) {
	const query = useQuery( {
		queryKey: [ 'stripe-permissions' ],
		queryFn: api.getStripePermissions,
		staleTime: 5 * 60 * 1000,
	} );

	const checked = hasKey && query.data?.checked === true && ! query.isFetching;
	const missing = checked ? Object.keys( STRIPE_PERMISSIONS ).filter( ( id ) => ! query.data?.permissions[ id ] ).length : 0;

	return (
		<div className="flex flex-col gap-2 border-t pt-4">
			<div className="flex flex-wrap items-center justify-between gap-2">
				<h3 className="font-medium">{ __( 'O que a chave precisa permitir na Stripe', 'resgata-pet' ) }</h3>
				{ hasKey && (
					<Button variant="outline" size="sm" disabled={ query.isFetching } onClick={ () => query.refetch() }>
						{ query.isFetching ? <Loader2Icon className="animate-spin" aria-hidden="true" /> : <RefreshCwIcon aria-hidden="true" /> }
						{ __( 'Conferir de novo', 'resgata-pet' ) }
					</Button>
				) }
			</div>

			<ul className="flex flex-col gap-2" aria-busy={ query.isFetching }>
				{ Object.entries( STRIPE_PERMISSIONS ).map( ( [ id, label ] ) => (
					<CheckItem key={ id } done={ checked ? query.data?.permissions[ id ] === true : undefined }>
						{ label }
					</CheckItem>
				) ) }
			</ul>

			{ hasKey && query.isError && ! query.isFetching && (
				<p className="text-destructive text-sm" role="alert">
					{ errorMessage( query.error ) }
				</p>
			) }
			{ ! hasKey && (
				<p className="text-muted-foreground text-sm">
					{ __( 'Ao criar a chave restrita na Stripe, libere estes itens. Depois de guardar a chave, cada um é conferido aqui.', 'resgata-pet' ) }
				</p>
			) }
			{ checked && missing > 0 && (
				<p className="text-sm">
					{ __( 'Na Stripe, edite a chave restrita, libere a escrita nos itens pendentes e toque em "Conferir de novo".', 'resgata-pet' ) }
				</p>
			) }
			{ checked && missing === 0 && <p className="text-muted-foreground text-sm">{ __( 'Tudo liberado.', 'resgata-pet' ) }</p> }
		</div>
	);
}

export function StripeCard( { overview }: { overview: SponsorshipOverview } ) {
	const { stripe, plans } = overview;

	return (
		<Card>
			<CardHeader>
				<CardTitle className="flex flex-wrap items-center gap-2">
					{ __( 'Pagamentos (Stripe)', 'resgata-pet' ) }
					{ stripe.mode === 'test' && <Badge variant="warning">{ __( 'Ambiente de teste', 'resgata-pet' ) }</Badge> }
					{ stripe.mode === 'live' && <Badge>{ __( 'Recebendo de verdade', 'resgata-pet' ) }</Badge> }
				</CardTitle>
				<CardDescription>
					{ __(
						'Configurada uma vez pelo administrador do site. As chaves ficam guardadas com criptografia e nunca aparecem inteiras aqui.',
						'resgata-pet'
					) }
				</CardDescription>
			</CardHeader>
			<CardContent className="flex flex-col gap-4">
				<ul className="flex flex-col gap-2">
					<CheckItem done={ stripe.secret_key }>
						{ stripe.secret_key
							? __( 'Chave da Stripe definida.', 'resgata-pet' )
							: __( 'Falta informar a chave da Stripe.', 'resgata-pet' ) }
					</CheckItem>
					<CheckItem done={ stripe.webhook_secret }>
						{ stripe.webhook_secret
							? __( 'Aviso automático de pagamentos definido.', 'resgata-pet' )
							: __( 'Falta informar o segredo do aviso de pagamentos (webhook).', 'resgata-pet' ) }
					</CheckItem>
					<CheckItem done={ plans.length > 0 }>
						{ plans.length > 0
							? sprintf(
									/* translators: %s: lista de valores, ex.: R$ 15, R$ 30. */
									__( 'Planos mensais: %s.', 'resgata-pet' ),
									plans.map( ( plan ) => plan.label ).join( ', ' )
							  )
							: __( 'Falta criar os planos mensais (na tela "Padrinhos", em "Planos").', 'resgata-pet' ) }
					</CheckItem>
				</ul>
				<StripePermissionsList hasKey={ stripe.secret_key } />
				{ config.user.isAdmin ? (
					<StripeKeysForm stripe={ stripe } />
				) : (
					<p className="text-muted-foreground text-sm">
						{ __( 'Só o administrador do site pode informar ou trocar as chaves.', 'resgata-pet' ) }
					</p>
				) }
				<p className="text-muted-foreground text-sm">
					{ __( 'Endereço para cadastrar na Stripe (webhook):', 'resgata-pet' ) }{ ' ' }
					<code className="break-all select-all">{ overview.webhook_url }</code>
				</p>
			</CardContent>
		</Card>
	);
}
