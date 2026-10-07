import { useEffect, useId, useMemo, useState } from 'react';
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper, flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { __, _n, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { CheckCircle2Icon, ChevronLeftIcon, ChevronRightIcon, CircleAlertIcon, CircleDashedIcon, HandCoinsIcon, Loader2Icon, RefreshCwIcon } from 'lucide-react';
import { api, errorMessage, type Sponsor, type SponsorshipOverview } from '@/lib/api';
import { config } from '@/lib/config';
import { formatDateTime } from '@/lib/dates';
import { Badge, type BadgeVariant } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Field } from '@/components/field';
import { OptionSelect } from '@/components/option-select';
import { PageHeader } from '@/components/page-header';
import { PlansCard } from '@/components/plans-card';
import { StripeKeysForm } from '@/components/stripe-keys-form';

const PAGE_SIZES = [ 25, 50, 75, 100 ];
const PAGE_SIZE_OPTIONS = Object.fromEntries( PAGE_SIZES.map( ( size ) => [ String( size ), String( size ) ] ) );
const ADJUSTMENT_LIMIT = 9999;
const column = createColumnHelper< Sponsor >();

const STATUS_VARIANTS: Record< string, BadgeVariant > = {
	active: 'default',
	past_due: 'warning',
	unpaid: 'destructive',
	incomplete: 'info',
	canceled: 'muted',
	incomplete_expired: 'muted',
};

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
				<span className="sr-only">{ done ? __( 'Funcionando:', 'resgata-wp' ) : __( 'Pendente:', 'resgata-wp' ) } </span>
				{ children }
			</span>
		</li>
	);
}

/** Permissões que a chave restrita precisa ter, com o nome que aparece na Stripe. */
const STRIPE_PERMISSIONS: Record< string, string > = {
	products: __( 'Products (escrita): criar e renomear os planos', 'resgata-wp' ),
	prices: __( 'Prices (escrita): definir o valor mensal de cada plano', 'resgata-wp' ),
	checkout: __( 'Checkout Sessions (escrita): abrir a página de pagamento', 'resgata-wp' ),
	portal_config: __( 'Customer portal configurations (escrita): preparar a área do padrinho', 'resgata-wp' ),
	portal_session: __( 'Customer portal sessions (escrita): abrir a área do padrinho', 'resgata-wp' ),
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
				<h3 className="font-medium">{ __( 'O que a chave precisa permitir na Stripe', 'resgata-wp' ) }</h3>
				{ hasKey && (
					<Button variant="outline" size="sm" disabled={ query.isFetching } onClick={ () => query.refetch() }>
						{ query.isFetching ? <Loader2Icon className="animate-spin" aria-hidden="true" /> : <RefreshCwIcon aria-hidden="true" /> }
						{ __( 'Conferir de novo', 'resgata-wp' ) }
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
					{ __( 'Ao criar a chave restrita na Stripe, libere estes itens. Depois de guardar a chave, cada um é conferido aqui.', 'resgata-wp' ) }
				</p>
			) }
			{ checked && missing > 0 && (
				<p className="text-sm">
					{ __( 'Na Stripe, edite a chave restrita, libere a escrita nos itens pendentes e toque em "Conferir de novo".', 'resgata-wp' ) }
				</p>
			) }
			{ checked && missing === 0 && <p className="text-muted-foreground text-sm">{ __( 'Tudo liberado.', 'resgata-wp' ) }</p> }
		</div>
	);
}

function StripeCard( { overview }: { overview: SponsorshipOverview } ) {
	const { stripe, plans } = overview;

	return (
		<Card>
			<CardHeader>
				<CardTitle className="flex flex-wrap items-center gap-2">
					{ __( 'Pagamentos (Stripe)', 'resgata-wp' ) }
					{ stripe.mode === 'test' && <Badge variant="warning">{ __( 'Ambiente de teste', 'resgata-wp' ) }</Badge> }
					{ stripe.mode === 'live' && <Badge>{ __( 'Recebendo de verdade', 'resgata-wp' ) }</Badge> }
				</CardTitle>
				<CardDescription>
					{ __(
						'Configurada uma vez pelo administrador do site. As chaves ficam guardadas com criptografia e nunca aparecem inteiras aqui.',
						'resgata-wp'
					) }
				</CardDescription>
			</CardHeader>
			<CardContent className="flex flex-col gap-4">
				<ul className="flex flex-col gap-2">
					<CheckItem done={ stripe.secret_key }>
						{ stripe.secret_key
							? __( 'Chave da Stripe definida.', 'resgata-wp' )
							: __( 'Falta informar a chave da Stripe.', 'resgata-wp' ) }
					</CheckItem>
					<CheckItem done={ stripe.webhook_secret }>
						{ stripe.webhook_secret
							? __( 'Aviso automático de pagamentos definido.', 'resgata-wp' )
							: __( 'Falta informar o segredo do aviso de pagamentos (webhook).', 'resgata-wp' ) }
					</CheckItem>
					<CheckItem done={ plans.length > 0 }>
						{ plans.length > 0
							? sprintf(
									/* translators: %s: lista de valores, ex.: R$ 15, R$ 30. */
									__( 'Planos mensais: %s.', 'resgata-wp' ),
									plans.map( ( plan ) => plan.label ).join( ', ' )
							  )
							: __( 'Falta criar os planos mensais (em "Planos", logo abaixo).', 'resgata-wp' ) }
					</CheckItem>
				</ul>
				<StripePermissionsList hasKey={ stripe.secret_key } />
				{ config.user.isAdmin ? (
					<StripeKeysForm stripe={ stripe } />
				) : (
					<p className="text-muted-foreground text-sm">
						{ __( 'Só o administrador do site pode informar ou trocar as chaves.', 'resgata-wp' ) }
					</p>
				) }
				<p className="text-muted-foreground text-sm">
					{ __( 'Endereço para cadastrar na Stripe (webhook):', 'resgata-wp' ) }{ ' ' }
					<code className="break-all select-all">{ overview.webhook_url }</code>
				</p>
			</CardContent>
		</Card>
	);
}

function CounterCard( { overview }: { overview: SponsorshipOverview } ) {
	const uid = useId();
	const queryClient = useQueryClient();
	const { counter } = overview;
	const [ adjustment, setAdjustment ] = useState( String( counter.adjustment ) );

	useEffect( () => setAdjustment( String( counter.adjustment ) ), [ counter.adjustment ] );

	const save = useMutation( {
		mutationFn: ( value: number ) => api.saveSponsorship( { adjustment: value } ),
		onSuccess: ( response ) => {
			queryClient.setQueryData( [ 'sponsorship' ], response );
			toast.success( __( 'Contador atualizado.', 'resgata-wp' ) );
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	const parsed = Number( adjustment );
	const valid = adjustment.trim() !== '' && Number.isInteger( parsed ) && Math.abs( parsed ) <= ADJUSTMENT_LIMIT;

	return (
		<Card>
			<CardHeader>
				<CardTitle>{ __( 'Contador do site', 'resgata-wp' ) }</CardTitle>
				<CardDescription>
					{ __( 'O site mostra quantas pessoas apadrinham hoje. O número muda sozinho a cada pagamento.', 'resgata-wp' ) }
				</CardDescription>
			</CardHeader>
			<CardContent className="flex flex-col gap-5">
				<div>
					<p className="text-4xl font-semibold tracking-tight">{ counter.total }</p>
					<p className="text-muted-foreground text-sm">
						{ sprintf(
							/* translators: %d: quantidade de padrinhos. */
							_n( '%d padrinho ativo pela Stripe', '%d padrinhos ativos pela Stripe', counter.active, 'resgata-wp' ),
							counter.active
						) }
						{ counter.updated_at &&
							' · ' +
								sprintf(
									/* translators: %s: data e hora. */
									__( 'atualizado em %s', 'resgata-wp' ),
									formatDateTime( counter.updated_at )
								) }
					</p>
				</div>

				<form
					noValidate
					className="flex flex-wrap items-end gap-3"
					onSubmit={ ( event ) => {
						event.preventDefault();
						if ( valid ) {
							save.mutate( parsed );
						}
					} }
				>
					<Field
						className="w-full sm:w-64"
						label={ __( 'Ajuste manual', 'resgata-wp' ) }
						htmlFor={ `${ uid }-adjustment` }
						hint={ __( 'Some aqui quem ajuda por fora (Pix, dinheiro). Use um número negativo para diminuir.', 'resgata-wp' ) }
						error={ valid ? undefined : __( 'Digite um número inteiro, como 3 ou -2.', 'resgata-wp' ) }
					>
						<Input
							id={ `${ uid }-adjustment` }
							type="number"
							inputMode="numeric"
							step={ 1 }
							min={ -ADJUSTMENT_LIMIT }
							max={ ADJUSTMENT_LIMIT }
							value={ adjustment }
							onChange={ ( event ) => setAdjustment( event.target.value ) }
						/>
					</Field>
					<Button type="submit" disabled={ ! valid || save.isPending || parsed === counter.adjustment }>
						{ save.isPending && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
						{ __( 'Salvar ajuste', 'resgata-wp' ) }
					</Button>
				</form>
			</CardContent>
		</Card>
	);
}

function SponsorsTable() {
	const [ pagination, setPagination ] = useState( { pageIndex: 0, pageSize: PAGE_SIZES[ 0 ] } );
	const [ status, setStatus ] = useState( '' );

	const { data, isPending, isError, error, isPlaceholderData } = useQuery( {
		queryKey: [ 'sponsors', pagination.pageIndex, pagination.pageSize, status ],
		queryFn: () => api.listSponsors( pagination.pageIndex + 1, pagination.pageSize, status ),
		placeholderData: keepPreviousData,
	} );

	const columns = useMemo(
		() => [
			column.accessor( 'name', {
				header: () => __( 'Padrinho', 'resgata-wp' ),
				cell: ( info ) => (
					<div className="min-w-0">
						<p className="font-medium">{ info.getValue() || __( '(sem nome)', 'resgata-wp' ) }</p>
						<p className="text-muted-foreground text-sm break-all">{ info.row.original.email }</p>
					</div>
				),
			} ),
			column.accessor( 'plan', {
				header: () => __( 'Plano', 'resgata-wp' ),
				cell: ( info ) => {
					const { plan, plan_amount: amount } = info.row.original;

					if ( ! plan && ! amount ) {
						return <span className="text-muted-foreground">—</span>;
					}

					return (
						<div>
							{ plan && <p className="font-medium">{ plan }</p> }
							{ amount && (
								<p className={ plan ? 'text-muted-foreground text-sm' : undefined }>
									{
										/* translators: %s: valor, ex.: R$ 15. */
										sprintf( __( '%s por mês', 'resgata-wp' ), amount )
									}
								</p>
							) }
						</div>
					);
				},
			} ),
			column.accessor( 'status', {
				header: () => __( 'Situação', 'resgata-wp' ),
				cell: ( info ) => (
					<div className="flex flex-wrap gap-1">
						<Badge variant={ STATUS_VARIANTS[ info.getValue() ] ?? 'outline' }>
							{ config.options.sponsorStatuses[ info.getValue() ] ?? info.getValue() }
						</Badge>
						{ info.row.original.payment_failed && <Badge variant="destructive">{ __( 'Pagamento falhou', 'resgata-wp' ) }</Badge> }
					</div>
				),
			} ),
			column.accessor( 'last_paid_at', {
				header: () => __( 'Último pagamento', 'resgata-wp' ),
				cell: ( info ) => (
					<span className="text-muted-foreground text-sm whitespace-nowrap">{ formatDateTime( info.getValue() ) || '—' }</span>
				),
			} ),
		],
		[]
	);

	const table = useReactTable( {
		data: data?.items ?? [],
		columns,
		getCoreRowModel: getCoreRowModel(),
		manualPagination: true,
		pageCount: data?.total_pages ?? -1,
		state: { pagination },
		onPaginationChange: setPagination,
	} );

	const pageCount = Math.max( 1, data?.total_pages ?? 1 );

	return (
		<section className="mt-8">
			<h2 className="mb-4 text-xl font-semibold tracking-tight">{ __( 'Padrinhos', 'resgata-wp' ) }</h2>

			<div className="mb-4 w-full sm:w-64">
				<OptionSelect
					ariaLabel={ __( 'Filtrar por situação', 'resgata-wp' ) }
					value={ status }
					options={ config.options.sponsorStatuses }
					emptyLabel={ __( 'Todas as situações', 'resgata-wp' ) }
					onChange={ ( value ) => {
						setStatus( value );
						setPagination( ( current ) => ( { ...current, pageIndex: 0 } ) );
					} }
				/>
			</div>

			{ isError && (
				<p className="text-destructive" role="alert">
					{ errorMessage( error ) }
				</p>
			) }

			{ isPending && (
				<div className="flex flex-col gap-3" aria-busy="true">
					{ [ 0, 1, 2 ].map( ( key ) => (
						<Skeleton key={ key } className="h-16 w-full" />
					) ) }
				</div>
			) }

			{ data && data.total === 0 && (
				<div className="bg-card flex flex-col items-center gap-3 rounded-xl border p-8 text-center">
					<HandCoinsIcon className="text-brand size-10" aria-hidden="true" />
					<p className="font-medium">
						{ status ? __( 'Nenhum padrinho nessa situação.', 'resgata-wp' ) : __( 'Nenhum padrinho por enquanto.', 'resgata-wp' ) }
					</p>
					{ ! status && (
						<p className="text-muted-foreground max-w-md text-sm">
							{ __( 'Quando alguém apadrinhar pelo site, o nome aparece aqui logo depois do pagamento.', 'resgata-wp' ) }
						</p>
					) }
				</div>
			) }

			{ data && data.total > 0 && (
				<>
					<div className={ isPlaceholderData ? 'bg-card rounded-xl border opacity-60' : 'bg-card rounded-xl border' }>
						<Table>
							<TableHeader>
								{ table.getHeaderGroups().map( ( group ) => (
									<TableRow key={ group.id }>
										{ group.headers.map( ( header ) => (
											<TableHead key={ header.id }>{ flexRender( header.column.columnDef.header, header.getContext() ) }</TableHead>
										) ) }
									</TableRow>
								) ) }
							</TableHeader>
							<TableBody>
								{ table.getRowModel().rows.map( ( row ) => (
									<TableRow key={ row.id }>
										{ row.getVisibleCells().map( ( cell ) => (
											<TableCell key={ cell.id } className="py-2 whitespace-normal">
												{ flexRender( cell.column.columnDef.cell, cell.getContext() ) }
											</TableCell>
										) ) }
									</TableRow>
								) ) }
							</TableBody>
						</Table>
					</div>

					<div className="mt-4 flex flex-wrap items-center justify-between gap-3">
						<div className="text-muted-foreground flex items-center gap-2 text-sm">
							<span>{ __( 'Itens por página', 'resgata-wp' ) }</span>
							<OptionSelect
								className="w-24"
								ariaLabel={ __( 'Itens por página', 'resgata-wp' ) }
								value={ String( pagination.pageSize ) }
								options={ PAGE_SIZE_OPTIONS }
								onChange={ ( value ) => setPagination( { pageIndex: 0, pageSize: Number( value ) } ) }
							/>
						</div>

						<div className="flex items-center gap-2">
							<span className="text-muted-foreground text-sm">
								{ sprintf(
									/* translators: %d: quantidade de padrinhos. */
									_n( '%d padrinho', '%d padrinhos', data.total, 'resgata-wp' ),
									data.total
								) }
								{ ' · ' }
								{ sprintf(
									/* translators: 1: página atual, 2: total de páginas. */
									__( 'Página %1$d de %2$d', 'resgata-wp' ),
									pagination.pageIndex + 1,
									pageCount
								) }
							</span>
							<Button
								variant="outline"
								size="icon"
								aria-label={ __( 'Página anterior', 'resgata-wp' ) }
								disabled={ ! table.getCanPreviousPage() }
								onClick={ () => table.previousPage() }
							>
								<ChevronLeftIcon aria-hidden="true" />
							</Button>
							<Button
								variant="outline"
								size="icon"
								aria-label={ __( 'Próxima página', 'resgata-wp' ) }
								disabled={ pagination.pageIndex + 1 >= pageCount }
								onClick={ () => table.nextPage() }
							>
								<ChevronRightIcon aria-hidden="true" />
							</Button>
						</div>
					</div>
				</>
			) }
		</section>
	);
}

export function SponsorshipPage() {
	const overview = useQuery( {
		queryKey: [ 'sponsorship' ],
		queryFn: api.getSponsorship,
		enabled: config.user.canManageSponsorship,
	} );

	if ( ! config.user.canManageSponsorship ) {
		return (
			<PageHeader
				title={ __( 'Padrinhos', 'resgata-wp' ) }
				description={ __( 'Você não tem permissão para ver os padrinhos.', 'resgata-wp' ) }
			/>
		);
	}

	return (
		<>
			<PageHeader
				title={ __( 'Padrinhos', 'resgata-wp' ) }
				description={ __( 'Quem ajuda a protetora todo mês, com pagamento por cartão.', 'resgata-wp' ) }
			/>

			{ overview.isError && (
				<p className="text-destructive" role="alert">
					{ errorMessage( overview.error ) }
				</p>
			) }

			{ overview.isPending && (
				<div className="grid gap-4 md:grid-cols-2" aria-busy="true">
					<Skeleton className="h-56 w-full" />
					<Skeleton className="h-56 w-full" />
				</div>
			) }

			{ overview.data && (
				<div className="grid gap-4 md:grid-cols-2">
					<CounterCard overview={ overview.data } />
					<StripeCard overview={ overview.data } />
					<PlansCard overview={ overview.data } />
				</div>
			) }

			<SponsorsTable />
		</>
	);
}
