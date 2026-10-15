import { useId, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __, _n, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { Loader2Icon, PencilIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { api, errorMessage, type PlanInput, type SponsorshipOverview, type SponsorshipPlan } from '@/lib/api';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/components/field';

type Limits = SponsorshipOverview[ 'limits' ];

/** "15", "12,50" ou "1.250,00" em centavos; null se não for um valor. */
function toCents( value: string ): number | null {
	const clean = value.trim().replace( /\./g, '' ).replace( ',', '.' );
	const number = Number( clean );

	return clean !== '' && Number.isFinite( number ) && number > 0 ? Math.round( number * 100 ) : null;
}

/** Centavos no formato do campo: 1500 vira "15", 1250 vira "12,50". */
function fromCents( amount: number ): string {
	return amount % 100 === 0 ? String( amount / 100 ) : ( amount / 100 ).toFixed( 2 ).replace( '.', ',' );
}

function PlanDialog( {
	plan,
	limits,
	onClose,
}: {
	/** Plano em edição, ou null para um plano novo. */
	plan: SponsorshipPlan | null;
	limits: Limits;
	onClose: () => void;
} ) {
	const uid = useId();
	const queryClient = useQueryClient();
	const [ name, setName ] = useState( plan?.name ?? '' );
	const [ amount, setAmount ] = useState( plan ? fromCents( plan.amount ) : '' );
	const [ description, setDescription ] = useState( plan?.description ?? '' );
	const [ benefits, setBenefits ] = useState( ( plan?.benefits ?? [] ).join( '\n' ) );
	const [ featured, setFeatured ] = useState( plan?.featured ?? false );
	const [ error, setError ] = useState( '' );

	const save = useMutation( {
		mutationFn: ( input: PlanInput ) => ( plan ? api.updatePlan( plan.id, input ) : api.createPlan( input ) ),
		onSuccess: ( response ) => {
			queryClient.setQueryData( [ 'sponsorship' ], response );
			void queryClient.invalidateQueries( { queryKey: [ 'sponsors' ] } );

			if ( response.warning ) {
				toast.warning( response.warning );
			} else {
				toast.success( plan ? __( 'Plano salvo.', 'resgata-pet' ) : __( 'Plano criado. Ele já aparece no site.', 'resgata-pet' ) );
			}

			onClose();
		},
		onError: ( failure ) => setError( errorMessage( failure ) ),
	} );

	const cents = toCents( amount );
	const amountChanged = plan !== null && cents !== null && cents !== plan.amount;
	const id = ( field: string ) => `${ uid }-${ field }`;

	const submit = () => {
		if ( cents === null || cents < limits.amount_min || cents > limits.amount_max ) {
			setError(
				sprintf(
					/* translators: 1: valor mínimo em reais, 2: valor máximo em reais. */
					__( 'Informe um valor mensal entre R$ %1$s e R$ %2$s.', 'resgata-pet' ),
					fromCents( limits.amount_min ),
					fromCents( limits.amount_max )
				)
			);
			return;
		}

		setError( '' );
		save.mutate( {
			name: name.trim(),
			amount: cents,
			description: description.trim(),
			benefits: benefits
				.split( '\n' )
				.map( ( line ) => line.trim() )
				.filter( ( line ) => line !== '' )
				.slice( 0, limits.benefits ),
			featured,
		} );
	};

	return (
		<Dialog open onOpenChange={ ( open ) => ! open && ! save.isPending && onClose() }>
			<DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto text-base sm:max-w-lg">
				<form
					noValidate
					className="grid gap-4"
					onSubmit={ ( event ) => {
						event.preventDefault();
						submit();
					} }
				>
					<DialogHeader>
						<DialogTitle className="text-lg">{ plan ? __( 'Editar plano', 'resgata-pet' ) : __( 'Novo plano', 'resgata-pet' ) }</DialogTitle>
						<DialogDescription>
							{ __( 'O plano é uma contribuição mensal no cartão. O nome e o valor aparecem no site e na página de pagamento.', 'resgata-pet' ) }
						</DialogDescription>
					</DialogHeader>

					<Field label={ __( 'Nome do plano', 'resgata-pet' ) } htmlFor={ id( 'name' ) } hint={ __( 'Opcional. Ex.: Amigo, Protetor, Anjo da Guarda', 'resgata-pet' ) }>
						<Input id={ id( 'name' ) } value={ name } maxLength={ limits.name } autoComplete="off" onChange={ ( event ) => setName( event.target.value ) } />
					</Field>

					<Field
						label={ __( 'Valor por mês (R$)', 'resgata-pet' ) }
						htmlFor={ id( 'amount' ) }
						hint={
							amountChanged
								? __( 'Quem já apadrinha neste plano continua pagando o valor antigo. O valor novo vale para quem entrar a partir de agora.', 'resgata-pet' )
								: undefined
						}
					>
						<Input
							id={ id( 'amount' ) }
							className="w-36"
							inputMode="decimal"
							autoComplete="off"
							disabled={ plan !== null && ! plan.can_reprice }
							value={ amount }
							maxLength={ 9 }
							onChange={ ( event ) => setAmount( event.target.value.replace( /[^0-9.,]/g, '' ) ) }
						/>
					</Field>

					<Field label={ __( 'Descrição curta', 'resgata-pet' ) } htmlFor={ id( 'description' ) } hint={ __( 'Opcional. Uma frase logo abaixo do valor.', 'resgata-pet' ) }>
						<Input
							id={ id( 'description' ) }
							value={ description }
							maxLength={ limits.description }
							onChange={ ( event ) => setDescription( event.target.value ) }
						/>
					</Field>

					<Field
						label={ __( 'O que este plano ajuda a pagar', 'resgata-pet' ) }
						htmlFor={ id( 'benefits' ) }
						hint={ sprintf(
							/* translators: %d: quantidade máxima de linhas. */
							__( 'Opcional. Um item por linha, até %d. Ex.: Ajuda com a ração do mês', 'resgata-pet' ),
							limits.benefits
						) }
					>
						<Textarea id={ id( 'benefits' ) } rows={ 4 } value={ benefits } onChange={ ( event ) => setBenefits( event.target.value ) } />
					</Field>

					<label className="flex min-h-11 cursor-pointer items-center gap-3">
						<Checkbox checked={ featured } onCheckedChange={ ( checked ) => setFeatured( Boolean( checked ) ) } />
						{ __( 'Destacar como "Mais escolhido" (só um plano por vez)', 'resgata-pet' ) }
					</label>

					{ error && (
						<p className="text-destructive text-sm" role="alert">
							{ error }
						</p>
					) }

					<DialogFooter>
						<Button type="button" variant="outline" disabled={ save.isPending } onClick={ onClose }>
							{ __( 'Cancelar', 'resgata-pet' ) }
						</Button>
						<Button type="submit" disabled={ save.isPending }>
							{ save.isPending && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
							{ plan ? __( 'Salvar plano', 'resgata-pet' ) : __( 'Criar plano', 'resgata-pet' ) }
						</Button>
					</DialogFooter>
				</form>
			</DialogContent>
		</Dialog>
	);
}

/** Planos de apadrinhamento: criar, editar, esconder do site e excluir. */
export function PlansCard( { overview }: { overview: SponsorshipOverview } ) {
	const queryClient = useQueryClient();
	const { plans, limits, stripe } = overview;
	const [ editing, setEditing ] = useState< SponsorshipPlan | 'new' | null >( null );
	const [ deleting, setDeleting ] = useState< SponsorshipPlan | null >( null );

	const onSaved = ( response: SponsorshipOverview, message: string ) => {
		queryClient.setQueryData( [ 'sponsorship' ], response );

		if ( response.warning ) {
			toast.warning( response.warning );
		} else {
			toast.success( message );
		}
	};

	const toggle = useMutation( {
		mutationFn: ( plan: SponsorshipPlan ) => api.updatePlan( plan.id, { active: ! plan.active } ),
		onSuccess: ( response, plan ) =>
			onSaved( response, plan.active ? __( 'Plano escondido do site.', 'resgata-pet' ) : __( 'Plano de volta ao site.', 'resgata-pet' ) ),
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	const remove = useMutation( {
		mutationFn: ( plan: SponsorshipPlan ) => api.deletePlan( plan.id ),
		onSuccess: ( response ) => {
			onSaved( response, __( 'Plano excluído.', 'resgata-pet' ) );
			setDeleting( null );
		},
		onError: ( error ) => {
			toast.error( errorMessage( error ) );
			setDeleting( null );
		},
	} );

	return (
		<Card className="md:col-span-2">
			<CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
				<div className="flex min-w-0 flex-col gap-1.5">
					<CardTitle>{ __( 'Planos', 'resgata-pet' ) }</CardTitle>
					<CardDescription>
						{ __( 'São as opções de contribuição mensal que aparecem no site. Você escolhe os nomes e os valores.', 'resgata-pet' ) }
					</CardDescription>
				</div>
				<Button type="button" disabled={ ! stripe.secret_key || plans.length >= limits.plans } onClick={ () => setEditing( 'new' ) }>
					<PlusIcon aria-hidden="true" />
					{ __( 'Novo plano', 'resgata-pet' ) }
				</Button>
			</CardHeader>
			<CardContent className="flex flex-col gap-3">
				{ ! stripe.secret_key && (
					<p className="text-muted-foreground text-sm">
						{ __( 'Para criar planos, a chave da Stripe precisa estar definida (veja "Pagamentos").', 'resgata-pet' ) }
					</p>
				) }

				{ plans.length === 0 && stripe.secret_key && (
					<p className="text-muted-foreground">{ __( 'Nenhum plano ainda. Crie o primeiro para o site começar a receber padrinhos.', 'resgata-pet' ) }</p>
				) }

				{ plans.length > 0 && (
					<ul className="divide-y rounded-xl border">
						{ plans.map( ( plan ) => (
							<li key={ plan.id } className="flex flex-wrap items-center gap-x-4 gap-y-2 p-3 sm:p-4">
								<div className="min-w-0 flex-1 basis-48">
									<p className="flex flex-wrap items-center gap-2 font-medium">
										{ plan.name || __( '(sem nome)', 'resgata-pet' ) }
										{ plan.featured && <Badge variant="info">{ __( 'Mais escolhido', 'resgata-pet' ) }</Badge> }
										{ ! plan.active && <Badge variant="muted">{ __( 'Escondido', 'resgata-pet' ) }</Badge> }
									</p>
									<p className="text-muted-foreground text-sm">
										{
											/* translators: %s: valor, ex.: R$ 15. */
											sprintf( __( '%s por mês', 'resgata-pet' ), plan.label )
										}
										{ ' • ' }
										{ sprintf(
											/* translators: %d: quantidade de padrinhos no plano. */
											_n( '%d padrinho', '%d padrinhos', plan.subscribers, 'resgata-pet' ),
											plan.subscribers
										) }
									</p>
								</div>
								<label className="flex min-h-11 cursor-pointer items-center gap-2 text-sm">
									<Switch checked={ plan.active } disabled={ toggle.isPending } onCheckedChange={ () => toggle.mutate( plan ) } />
									{ __( 'No site', 'resgata-pet' ) }
								</label>
								<div className="flex items-center gap-1">
									<Button
										type="button"
										variant="ghost"
										size="icon"
										aria-label={
											/* translators: %s: nome ou valor do plano. */
											sprintf( __( 'Editar plano %s', 'resgata-pet' ), plan.name || plan.label )
										}
										onClick={ () => setEditing( plan ) }
									>
										<PencilIcon aria-hidden="true" />
									</Button>
									<Button
										type="button"
										variant="ghost"
										size="icon"
										aria-label={
											/* translators: %s: nome ou valor do plano. */
											sprintf( __( 'Excluir plano %s', 'resgata-pet' ), plan.name || plan.label )
										}
										onClick={ () => setDeleting( plan ) }
									>
										<Trash2Icon className="text-destructive" aria-hidden="true" />
									</Button>
								</div>
							</li>
						) ) }
					</ul>
				) }
			</CardContent>

			{ editing && <PlanDialog plan={ editing === 'new' ? null : editing } limits={ limits } onClose={ () => setEditing( null ) } /> }

			<AlertDialog open={ deleting !== null } onOpenChange={ ( open ) => ! open && setDeleting( null ) }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>{ __( 'Excluir este plano?', 'resgata-pet' ) }</AlertDialogTitle>
						<AlertDialogDescription>
							{ deleting && deleting.subscribers > 0
								? __( 'Este plano tem padrinhos e não pode ser excluído. Use a chave "No site" para escondê-lo: quem já apadrinha continua normalmente.', 'resgata-pet' )
								: __( 'O plano sai do site e deixa de ser vendido na Stripe. Isso não pode ser desfeito.', 'resgata-pet' ) }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>{ __( 'Cancelar', 'resgata-pet' ) }</AlertDialogCancel>
						{ deleting && deleting.subscribers === 0 && (
							<AlertDialogAction variant="destructive" disabled={ remove.isPending } onClick={ () => remove.mutate( deleting ) }>
								{ __( 'Excluir', 'resgata-pet' ) }
							</AlertDialogAction>
						) }
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</Card>
	);
}
