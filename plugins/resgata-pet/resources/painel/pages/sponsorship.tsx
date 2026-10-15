import { useEffect, useId, useMemo, useState } from 'react';
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper, flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { __, _n, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ChevronLeftIcon, ChevronRightIcon, CircleAlertIcon, HandCoinsIcon, Loader2Icon, SettingsIcon } from 'lucide-react';
import { Link } from 'react-router';
import { api, errorMessage, type Payment, type PaymentStatus, type Sponsor, type SponsorshipOverview } from '@/lib/api';
import { config } from '@/lib/config';
import { formatDate, formatDateTime } from '@/lib/dates';
import { Badge, type BadgeVariant } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Field } from '@/components/field';
import { OptionSelect } from '@/components/option-select';
import { PageHeader } from '@/components/page-header';
import { PlansCard } from '@/components/plans-card';

const PAGE_SIZES = [ 25, 50, 75, 100 ];
const PAGE_SIZE_OPTIONS = Object.fromEntries( PAGE_SIZES.map( ( size ) => [ String( size ), String( size ) ] ) );
const ADJUSTMENT_LIMIT = 9999;
const column = createColumnHelper< Sponsor >();
const paymentColumn = createColumnHelper< Payment >();

const PAYMENT_VARIANTS: Record< PaymentStatus, BadgeVariant > = {
	paid: 'default',
	dispute_won: 'default',
	partially_refunded: 'warning',
	refunded: 'muted',
	disputed: 'destructive',
	dispute_lost: 'destructive',
};

function paymentStatusLabel( status: PaymentStatus ): string {
	switch ( status ) {
		case 'partially_refunded':
			return __( 'Devolvido em parte', 'resgata-pet' );
		case 'refunded':
			return __( 'Devolvido', 'resgata-pet' );
		case 'disputed':
			return __( 'Contestado', 'resgata-pet' );
		case 'dispute_lost':
			return __( 'Contestação perdida', 'resgata-pet' );
		case 'dispute_won':
			return __( 'Pago (contestação ganha)', 'resgata-pet' );
		default:
			return __( 'Pago', 'resgata-pet' );
	}
}

const STATUS_VARIANTS: Record< string, BadgeVariant > = {
	active: 'default',
	past_due: 'warning',
	unpaid: 'destructive',
	incomplete: 'info',
	canceled: 'muted',
	incomplete_expired: 'muted',
};

/** Situação dos pagamentos, com o caminho para as chaves da Stripe (que ficam em Configurações). */
function PaymentSetupCard( { overview }: { overview: SponsorshipOverview } ) {
	const { stripe } = overview;
	const ready = stripe.secret_key && stripe.webhook_secret;

	return (
		<Card>
			<CardHeader>
				<CardTitle className="flex flex-wrap items-center gap-2">
					{ __( 'Pagamentos (Stripe)', 'resgata-pet' ) }
					{ stripe.mode === 'test' && <Badge variant="warning">{ __( 'Ambiente de teste', 'resgata-pet' ) }</Badge> }
					{ stripe.mode === 'live' && <Badge>{ __( 'Recebendo de verdade', 'resgata-pet' ) }</Badge> }
				</CardTitle>
				<CardDescription>
					{ ready
						? __( 'As chaves da Stripe estão definidas: o site já pode receber os apadrinhamentos.', 'resgata-pet' )
						: __( 'Falta terminar a configuração da Stripe. Sem as duas chaves, o site não recebe os apadrinhamentos.', 'resgata-pet' ) }
				</CardDescription>
			</CardHeader>
			<CardContent className="flex flex-col gap-4">
				{ ! ready && ! config.user.isAdmin && (
					<p className="flex items-start gap-2 text-sm">
						<CircleAlertIcon className="mt-0.5 size-5 shrink-0 text-amber-600" aria-hidden="true" />
						<span>{ __( 'Só o administrador do site pode informar as chaves.', 'resgata-pet' ) }</span>
					</p>
				) }
				{ config.user.canManageSettings && (
					<div>
						<Link to="/configuracoes?aba=pagamentos" className={ buttonVariants( { variant: 'outline' } ) }>
							<SettingsIcon aria-hidden="true" />
							{ __( 'Abrir as configurações de pagamento', 'resgata-pet' ) }
						</Link>
					</div>
				) }
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
			toast.success( __( 'Contador atualizado.', 'resgata-pet' ) );
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	const parsed = Number( adjustment );
	const valid = adjustment.trim() !== '' && Number.isInteger( parsed ) && Math.abs( parsed ) <= ADJUSTMENT_LIMIT;

	return (
		<Card>
			<CardHeader>
				<CardTitle>{ __( 'Contador do site', 'resgata-pet' ) }</CardTitle>
				<CardDescription>
					{ __( 'O site mostra quantas pessoas apadrinham hoje. O número muda sozinho a cada pagamento.', 'resgata-pet' ) }
				</CardDescription>
			</CardHeader>
			<CardContent className="flex flex-col gap-5">
				<div>
					<p className="text-4xl font-semibold tracking-tight">{ counter.total }</p>
					<p className="text-muted-foreground text-sm">
						{ sprintf(
							/* translators: %d: quantidade de padrinhos. */
							_n( '%d padrinho ativo pela Stripe', '%d padrinhos ativos pela Stripe', counter.active, 'resgata-pet' ),
							counter.active
						) }
						{ counter.updated_at &&
							' · ' +
								sprintf(
									/* translators: %s: data e hora. */
									__( 'atualizado em %s', 'resgata-pet' ),
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
						label={ __( 'Ajuste manual', 'resgata-pet' ) }
						htmlFor={ `${ uid }-adjustment` }
						hint={ __( 'Some aqui quem ajuda por fora (Pix, dinheiro). Use um número negativo para diminuir.', 'resgata-pet' ) }
						error={ valid ? undefined : __( 'Digite um número inteiro, como 3 ou -2.', 'resgata-pet' ) }
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
						{ __( 'Salvar ajuste', 'resgata-pet' ) }
					</Button>
				</form>
			</CardContent>
		</Card>
	);
}

function RevenueCard( { overview }: { overview: SponsorshipOverview } ) {
	const { revenue } = overview;
	const numbers = [
		{ label: __( 'Este mês', 'resgata-pet' ), value: revenue.month },
		{ label: __( 'Mês passado', 'resgata-pet' ), value: revenue.last_month },
		{
			label: revenue.since
				? sprintf(
						/* translators: %s: data. */
						__( 'Total desde %s', 'resgata-pet' ),
						formatDate( revenue.since )
				  )
				: __( 'Total até hoje', 'resgata-pet' ),
			value: revenue.total,
		},
	];

	return (
		<Card className="md:col-span-2">
			<CardHeader>
				<CardTitle>{ __( 'Arrecadação dos padrinhos', 'resgata-pet' ) }</CardTitle>
				<CardDescription>
					{ __( 'Quanto os padrinhos pagaram, já sem o que foi devolvido ou contestado. As taxas da Stripe não estão descontadas.', 'resgata-pet' ) }
				</CardDescription>
			</CardHeader>
			<CardContent className="flex flex-col gap-4">
				<dl className="grid gap-4 sm:grid-cols-3">
					{ numbers.map( ( number ) => (
						<div key={ number.label }>
							<dt className="text-muted-foreground text-sm">{ number.label }</dt>
							<dd className="text-3xl font-semibold tracking-tight">{ number.value }</dd>
						</div>
					) ) }
				</dl>

				{ revenue.open_disputes > 0 && (
					<p className="flex items-start gap-2 rounded-lg bg-red-100 p-3 text-sm text-red-900" role="alert">
						<CircleAlertIcon className="mt-0.5 size-5 shrink-0" aria-hidden="true" />
						<span>
							{ sprintf(
								/* translators: %d: quantidade de pagamentos. */
								_n(
									'%d pagamento foi contestado no banco e espera resposta. Entre na Stripe para responder: a resposta tem prazo.',
									'%d pagamentos foram contestados no banco e esperam resposta. Entre na Stripe para responder: a resposta tem prazo.',
									revenue.open_disputes,
									'resgata-pet'
								),
								revenue.open_disputes
							) }
						</span>
					</p>
				) }
			</CardContent>
		</Card>
	);
}

function PaymentsTable() {
	const [ pagination, setPagination ] = useState( { pageIndex: 0, pageSize: PAGE_SIZES[ 0 ] } );

	const { data, isPending, isError, error, isPlaceholderData } = useQuery( {
		queryKey: [ 'payments', pagination.pageIndex, pagination.pageSize ],
		queryFn: () => api.listPayments( pagination.pageIndex + 1, pagination.pageSize ),
		placeholderData: keepPreviousData,
	} );

	const columns = useMemo(
		() => [
			paymentColumn.accessor( 'paid_at', {
				header: () => __( 'Data', 'resgata-pet' ),
				cell: ( info ) => <span className="text-sm whitespace-nowrap">{ formatDateTime( info.getValue() ) || '—' }</span>,
			} ),
			paymentColumn.accessor( 'sponsor', {
				header: () => __( 'Padrinho', 'resgata-pet' ),
				cell: ( info ) => <span className="font-medium">{ info.getValue() || __( '(sem nome)', 'resgata-pet' ) }</span>,
			} ),
			paymentColumn.accessor( 'amount', {
				header: () => __( 'Valor', 'resgata-pet' ),
				cell: ( info ) => (
					<div className="whitespace-nowrap">
						<p className="font-medium">{ info.getValue() }</p>
						{ info.row.original.refunded && (
							<p className="text-muted-foreground text-sm">
								{
									/* translators: %s: valor, ex.: R$ 15. */
									sprintf( __( '%s devolvido', 'resgata-pet' ), info.row.original.refunded )
								}
							</p>
						) }
					</div>
				),
			} ),
			paymentColumn.accessor( 'status', {
				header: () => __( 'Situação', 'resgata-pet' ),
				cell: ( info ) => <Badge variant={ PAYMENT_VARIANTS[ info.getValue() ] ?? 'outline' }>{ paymentStatusLabel( info.getValue() ) }</Badge>,
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
			<h2 className="mb-4 text-xl font-semibold tracking-tight">{ __( 'Pagamentos', 'resgata-pet' ) }</h2>

			{ isError && (
				<p className="text-destructive" role="alert">
					{ errorMessage( error ) }
				</p>
			) }

			{ isPending && (
				<div className="flex flex-col gap-3" aria-busy="true">
					{ [ 0, 1, 2 ].map( ( key ) => (
						<Skeleton key={ key } className="h-12 w-full" />
					) ) }
				</div>
			) }

			{ data && data.total === 0 && (
				<div className="bg-card flex flex-col items-center gap-3 rounded-xl border p-8 text-center">
					<HandCoinsIcon className="text-brand size-10" aria-hidden="true" />
					<p className="font-medium">{ __( 'Nenhum pagamento registrado por enquanto.', 'resgata-pet' ) }</p>
					<p className="text-muted-foreground max-w-md text-sm">
						{ __( 'Cada mensalidade paga aparece aqui logo depois da cobrança.', 'resgata-pet' ) }
					</p>
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
							<span>{ __( 'Itens por página', 'resgata-pet' ) }</span>
							<OptionSelect
								className="w-24"
								ariaLabel={ __( 'Itens por página', 'resgata-pet' ) }
								value={ String( pagination.pageSize ) }
								options={ PAGE_SIZE_OPTIONS }
								onChange={ ( value ) => setPagination( { pageIndex: 0, pageSize: Number( value ) } ) }
							/>
						</div>

						<div className="flex items-center gap-2">
							<span className="text-muted-foreground text-sm">
								{ sprintf(
									/* translators: %d: quantidade de pagamentos. */
									_n( '%d pagamento', '%d pagamentos', data.total, 'resgata-pet' ),
									data.total
								) }
								{ ' · ' }
								{ sprintf(
									/* translators: 1: página atual, 2: total de páginas. */
									__( 'Página %1$d de %2$d', 'resgata-pet' ),
									pagination.pageIndex + 1,
									pageCount
								) }
							</span>
							<Button
								variant="outline"
								size="icon"
								aria-label={ __( 'Página anterior', 'resgata-pet' ) }
								disabled={ ! table.getCanPreviousPage() }
								onClick={ () => table.previousPage() }
							>
								<ChevronLeftIcon aria-hidden="true" />
							</Button>
							<Button
								variant="outline"
								size="icon"
								aria-label={ __( 'Próxima página', 'resgata-pet' ) }
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
				header: () => __( 'Padrinho', 'resgata-pet' ),
				cell: ( info ) => (
					<div className="min-w-0">
						<p className="font-medium">{ info.getValue() || __( '(sem nome)', 'resgata-pet' ) }</p>
						<p className="text-muted-foreground text-sm break-all">{ info.row.original.email }</p>
					</div>
				),
			} ),
			column.accessor( 'plan', {
				header: () => __( 'Plano', 'resgata-pet' ),
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
										sprintf( __( '%s por mês', 'resgata-pet' ), amount )
									}
								</p>
							) }
						</div>
					);
				},
			} ),
			column.accessor( 'status', {
				header: () => __( 'Situação', 'resgata-pet' ),
				cell: ( info ) => (
					<div className="flex flex-wrap gap-1">
						<Badge variant={ STATUS_VARIANTS[ info.getValue() ] ?? 'outline' }>
							{ config.options.sponsorStatuses[ info.getValue() ] ?? info.getValue() }
						</Badge>
						{ info.row.original.payment_failed && <Badge variant="destructive">{ __( 'Pagamento falhou', 'resgata-pet' ) }</Badge> }
						{ info.row.original.cancel_at && (
							<Badge variant="warning">
								{
									/* translators: %s: data. */
									sprintf( __( 'Cancela em %s', 'resgata-pet' ), formatDate( info.row.original.cancel_at ) )
								}
							</Badge>
						) }
					</div>
				),
			} ),
			column.accessor( 'total_paid', {
				header: () => __( 'Total doado', 'resgata-pet' ),
				cell: ( info ) => <span className="font-medium whitespace-nowrap">{ info.getValue() || '—' }</span>,
			} ),
			column.accessor( 'last_paid_at', {
				header: () => __( 'Último pagamento', 'resgata-pet' ),
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
			<h2 className="mb-4 text-xl font-semibold tracking-tight">{ __( 'Padrinhos', 'resgata-pet' ) }</h2>

			<div className="mb-4 w-full sm:w-64">
				<OptionSelect
					ariaLabel={ __( 'Filtrar por situação', 'resgata-pet' ) }
					value={ status }
					options={ config.options.sponsorStatuses }
					emptyLabel={ __( 'Todas as situações', 'resgata-pet' ) }
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
						{ status ? __( 'Nenhum padrinho nessa situação.', 'resgata-pet' ) : __( 'Nenhum padrinho por enquanto.', 'resgata-pet' ) }
					</p>
					{ ! status && (
						<p className="text-muted-foreground max-w-md text-sm">
							{ __( 'Quando alguém apadrinhar pelo site, o nome aparece aqui logo depois do pagamento.', 'resgata-pet' ) }
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
							<span>{ __( 'Itens por página', 'resgata-pet' ) }</span>
							<OptionSelect
								className="w-24"
								ariaLabel={ __( 'Itens por página', 'resgata-pet' ) }
								value={ String( pagination.pageSize ) }
								options={ PAGE_SIZE_OPTIONS }
								onChange={ ( value ) => setPagination( { pageIndex: 0, pageSize: Number( value ) } ) }
							/>
						</div>

						<div className="flex items-center gap-2">
							<span className="text-muted-foreground text-sm">
								{ sprintf(
									/* translators: %d: quantidade de padrinhos. */
									_n( '%d padrinho', '%d padrinhos', data.total, 'resgata-pet' ),
									data.total
								) }
								{ ' · ' }
								{ sprintf(
									/* translators: 1: página atual, 2: total de páginas. */
									__( 'Página %1$d de %2$d', 'resgata-pet' ),
									pagination.pageIndex + 1,
									pageCount
								) }
							</span>
							<Button
								variant="outline"
								size="icon"
								aria-label={ __( 'Página anterior', 'resgata-pet' ) }
								disabled={ ! table.getCanPreviousPage() }
								onClick={ () => table.previousPage() }
							>
								<ChevronLeftIcon aria-hidden="true" />
							</Button>
							<Button
								variant="outline"
								size="icon"
								aria-label={ __( 'Próxima página', 'resgata-pet' ) }
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
				title={ __( 'Padrinhos', 'resgata-pet' ) }
				description={ __( 'Você não tem permissão para ver os padrinhos.', 'resgata-pet' ) }
			/>
		);
	}

	return (
		<>
			<PageHeader
				title={ __( 'Padrinhos', 'resgata-pet' ) }
				description={ __( 'Quem ajuda a protetora todo mês, com pagamento por cartão.', 'resgata-pet' ) }
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
					<RevenueCard overview={ overview.data } />
					<CounterCard overview={ overview.data } />
					<PaymentSetupCard overview={ overview.data } />
					<PlansCard overview={ overview.data } />
				</div>
			) }

			<SponsorsTable />
			<PaymentsTable />
		</>
	);
}
