import { useId, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { Loader2Icon } from 'lucide-react';
import { api, errorMessage, type SponsorshipOverview, type StripeKeysInput } from '@/lib/api';
import {
	AlertDialog,
	AlertDialogAction,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Field } from '@/components/field';

const LOCKED = __( 'Definida no arquivo wp-config.php do site. Para trocar, é preciso editar esse arquivo.', 'resgata-wp' );

/**
 * Campos para o administrador informar ou trocar as chaves da Stripe.
 *
 * Os campos começam sempre vazios: a chave salva nunca volta do servidor.
 */
export function StripeKeysForm( { stripe }: { stripe: SponsorshipOverview[ 'stripe' ] } ) {
	const uid = useId();
	const queryClient = useQueryClient();
	const [ secretKey, setSecretKey ] = useState( '' );
	const [ webhookSecret, setWebhookSecret ] = useState( '' );
	const [ confirmRemove, setConfirmRemove ] = useState( false );

	const save = useMutation( {
		mutationFn: ( data: StripeKeysInput ) => api.saveStripeKeys( data ),
		onSuccess: ( response, data ) => {
			queryClient.setQueryData( [ 'sponsorship' ], response );
			queryClient.invalidateQueries( { queryKey: [ 'stripe-permissions' ] } );
			setSecretKey( '' );
			setWebhookSecret( '' );
			setConfirmRemove( false );
			toast.success(
				Object.values( data ).some( Boolean ) ? __( 'Chaves guardadas.', 'resgata-wp' ) : __( 'Chaves removidas.', 'resgata-wp' )
			);
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	const secretLocked = stripe.sources.secret_key === 'constant';
	const webhookLocked = stripe.sources.webhook_secret === 'constant';
	const savedHere = stripe.sources.secret_key === 'panel' || stripe.sources.webhook_secret === 'panel';

	if ( secretLocked && webhookLocked ) {
		return <p className="text-muted-foreground text-sm">{ LOCKED }</p>;
	}

	if ( ! stripe.can_store ) {
		return (
			<p className="text-muted-foreground text-sm">
				{ __(
					'Este site não consegue guardar as chaves com segurança. Peça a quem cuida da hospedagem para defini-las no arquivo wp-config.php.',
					'resgata-wp'
				) }
			</p>
		);
	}

	const data: StripeKeysInput = {};
	if ( secretKey.trim() ) {
		data.secret_key = secretKey.trim();
	}
	if ( webhookSecret.trim() ) {
		data.webhook_secret = webhookSecret.trim();
	}

	return (
		<form
			noValidate
			className="flex flex-col gap-4 border-t pt-4"
			onSubmit={ ( event ) => {
				event.preventDefault();
				if ( Object.keys( data ).length > 0 ) {
					save.mutate( data );
				}
			} }
		>
			{ stripe.unreadable.length > 0 && (
				<p className="text-destructive text-sm" role="alert">
					{ __( 'A chave que estava guardada não pôde ser lida, porque a segurança do site foi trocada. Cole a chave de novo.', 'resgata-wp' ) }
				</p>
			) }

			<Field
				label={ __( 'Chave da Stripe', 'resgata-wp' ) }
				htmlFor={ `${ uid }-secret` }
				hint={
					secretLocked
						? LOCKED
						: __(
								'Na Stripe, abra "Desenvolvedores" → "Chaves de API" e crie uma chave restrita com as permissões da lista acima. A chave secreta completa ("sk_") não é aceita.',
								'resgata-wp'
						  )
				}
			>
				<Input
					id={ `${ uid }-secret` }
					type="password"
					autoComplete="off"
					spellCheck={ false }
					disabled={ secretLocked }
					placeholder={
						stripe.hint
							? sprintf(
									/* translators: %s: começo e fim da chave em uso, ex.: rk_live_…Ab12. */
									__( 'Em uso: %s. Cole outra para trocar.', 'resgata-wp' ),
									stripe.hint
							  )
							: 'rk_live_...'
					}
					value={ secretKey }
					onChange={ ( event ) => setSecretKey( event.target.value ) }
				/>
			</Field>

			<Field
				label={ __( 'Segredo do aviso de pagamentos (webhook)', 'resgata-wp' ) }
				htmlFor={ `${ uid }-webhook` }
				hint={
					webhookLocked
						? LOCKED
						: __(
								'Na Stripe, abra "Desenvolvedores" → "Webhooks", cadastre o endereço abaixo e copie o "Segredo da assinatura".',
								'resgata-wp'
						  )
				}
			>
				<Input
					id={ `${ uid }-webhook` }
					type="password"
					autoComplete="off"
					spellCheck={ false }
					disabled={ webhookLocked }
					placeholder={ stripe.webhook_secret ? __( 'Já definido. Cole outro para trocar.', 'resgata-wp' ) : 'whsec_...' }
					value={ webhookSecret }
					onChange={ ( event ) => setWebhookSecret( event.target.value ) }
				/>
			</Field>

			<div className="flex flex-wrap gap-3">
				<Button type="submit" disabled={ Object.keys( data ).length === 0 || save.isPending }>
					{ save.isPending && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
					{ __( 'Guardar chaves', 'resgata-wp' ) }
				</Button>
				{ savedHere && (
					<Button type="button" variant="outline" disabled={ save.isPending } onClick={ () => setConfirmRemove( true ) }>
						{ __( 'Remover chaves', 'resgata-wp' ) }
					</Button>
				) }
			</div>

			<AlertDialog open={ confirmRemove } onOpenChange={ setConfirmRemove }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>{ __( 'Remover as chaves da Stripe?', 'resgata-wp' ) }</AlertDialogTitle>
						<AlertDialogDescription>
							{ __(
								'O site para de aceitar novos padrinhos e de receber os avisos de pagamento até você informar as chaves de novo. Quem já é padrinho continua sendo cobrado pela Stripe.',
								'resgata-wp'
							) }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>{ __( 'Cancelar', 'resgata-wp' ) }</AlertDialogCancel>
						<AlertDialogAction
							variant="destructive"
							disabled={ save.isPending }
							onClick={ () =>
								save.mutate( {
									...( secretLocked ? {} : { secret_key: '' } ),
									...( webhookLocked ? {} : { webhook_secret: '' } ),
								} )
							}
						>
							{ __( 'Remover', 'resgata-wp' ) }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</form>
	);
}
