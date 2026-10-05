import { useMemo, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper, flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { __, _n, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ChevronLeftIcon, ChevronRightIcon, HeartHandshakeIcon, PawPrintIcon } from 'lucide-react';
import { api, errorMessage, type AdoptionList, type AdoptionListItem } from '@/lib/api';
import { config } from '@/lib/config';
import { formatDateTime } from '@/lib/dates';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { OptionSelect } from '@/components/option-select';
import { PageHeader } from '@/components/page-header';

const PAGE_SIZES = [ 25, 50, 75, 100 ];
const PAGE_SIZE_OPTIONS = Object.fromEntries( PAGE_SIZES.map( ( size ) => [ String( size ), String( size ) ] ) );
const column = createColumnHelper< AdoptionListItem >();

export function AdoptionsListPage() {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const [ searchParams, setSearchParams ] = useSearchParams();
	const [ pagination, setPagination ] = useState( { pageIndex: 0, pageSize: PAGE_SIZES[ 0 ] } );

	const statusParam = searchParams.get( 'situacao' ) ?? '';
	const status = statusParam in config.options.adoptionStatuses ? statusParam : '';
	const queryKey = [ 'adoptions', pagination.pageIndex, pagination.pageSize, status ];

	const { data, isPending, isError, error, isPlaceholderData } = useQuery( {
		queryKey,
		queryFn: () => api.listAdoptions( pagination.pageIndex + 1, pagination.pageSize, status ),
		placeholderData: keepPreviousData,
		enabled: config.user.canManageAdoptions,
	} );

	const changeStatus = useMutation( {
		mutationFn: ( { id, next }: { id: number; next: string } ) => api.updateAdoption( id, { status: next } ),
		onMutate: async ( { id, next } ) => {
			await queryClient.cancelQueries( { queryKey: [ 'adoptions' ] } );
			const previous = queryClient.getQueryData< AdoptionList >( queryKey );

			queryClient.setQueryData< AdoptionList >( queryKey, ( list ) =>
				list ? { ...list, items: list.items.map( ( item ) => ( item.id === id ? { ...item, status: next } : item ) ) } : list
			);

			return { previous };
		},
		onError: ( mutationError, _variables, context ) => {
			queryClient.setQueryData( queryKey, context?.previous );
			toast.error( errorMessage( mutationError ) );
		},
		onSuccess: ( adoption ) => {
			queryClient.setQueryData( [ 'adoption', adoption.id ], adoption );
			toast.success( __( 'Situação do pedido atualizada.', 'resgata-wp' ) );
		},
		onSettled: () => void queryClient.invalidateQueries( { queryKey: [ 'adoptions', 'new-count' ] } ),
	} );
	const { mutate: updateRow } = changeStatus;

	const columns = useMemo(
		() => [
			column.accessor( 'name', {
				header: () => __( 'Quem pediu', 'resgata-wp' ),
				cell: ( info ) => (
					<Link to={ `/adocoes/${ info.row.original.id }` } className="font-medium" onClick={ ( event ) => event.stopPropagation() }>
						{ info.getValue() || __( '(sem nome)', 'resgata-wp' ) }
					</Link>
				),
			} ),
			column.accessor( 'animal', {
				header: () => __( 'Animal', 'resgata-wp' ),
				cell: ( info ) => {
					const animal = info.getValue();

					if ( ! animal ) {
						return <span className="text-muted-foreground">{ __( 'Animal removido', 'resgata-wp' ) }</span>;
					}

					return (
						<span className="flex items-center gap-3">
							{ animal.thumbnail ? (
								<img src={ animal.thumbnail } alt="" className="size-10 rounded-lg object-cover" loading="lazy" />
							) : (
								<span className="bg-muted text-muted-foreground flex size-10 items-center justify-center rounded-lg">
									<PawPrintIcon className="size-4" aria-hidden="true" />
								</span>
							) }
							{ animal.name }
						</span>
					);
				},
			} ),
			column.accessor( 'status', {
				header: () => __( 'Situação', 'resgata-wp' ),
				cell: ( info ) => (
					// O clique aqui não pode abrir o pedido (a linha inteira é clicável).
					<div className="w-44 max-w-full" onClick={ ( event ) => event.stopPropagation() }>
						<OptionSelect
							ariaLabel={ sprintf(
								/* translators: %s: nome de quem pediu a adoção. */
								__( 'Situação do pedido de %s', 'resgata-wp' ),
								info.row.original.name
							) }
							value={ info.getValue() }
							options={ config.options.adoptionStatuses }
							onChange={ ( next ) => {
								if ( next && next !== info.getValue() ) {
									updateRow( { id: info.row.original.id, next } );
								}
							} }
						/>
					</div>
				),
			} ),
			column.accessor( 'date', {
				header: () => __( 'Recebido em', 'resgata-wp' ),
				cell: ( info ) => <span className="text-muted-foreground text-sm whitespace-nowrap">{ formatDateTime( info.getValue() ) }</span>,
			} ),
		],
		[ updateRow ]
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

	if ( ! config.user.canManageAdoptions ) {
		return (
			<PageHeader
				title={ __( 'Pedidos de adoção', 'resgata-wp' ) }
				description={ __( 'Você não tem permissão para ver os pedidos de adoção.', 'resgata-wp' ) }
			/>
		);
	}

	const pageCount = Math.max( 1, data?.total_pages ?? 1 );

	return (
		<>
			<PageHeader
				title={ __( 'Pedidos de adoção', 'resgata-wp' ) }
				description={ __( 'Pedidos enviados pelo formulário do site.', 'resgata-wp' ) }
			/>

			<div className="mb-4 w-full sm:w-64">
				<OptionSelect
					ariaLabel={ __( 'Filtrar por situação', 'resgata-wp' ) }
					value={ status }
					options={ config.options.adoptionStatuses }
					emptyLabel={ __( 'Todas as situações', 'resgata-wp' ) }
					onChange={ ( value ) => {
						setSearchParams( value ? { situacao: value } : {}, { replace: true } );
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
					<HeartHandshakeIcon className="text-brand size-10" aria-hidden="true" />
					<p className="font-medium">
						{ status
							? __( 'Nenhum pedido nessa situação.', 'resgata-wp' )
							: __( 'Nenhum pedido de adoção por enquanto.', 'resgata-wp' ) }
					</p>
					{ ! status && (
						<p className="text-muted-foreground max-w-md text-sm">
							{ __(
								'Quando alguém preencher o formulário na página de um animal disponível, o pedido aparece aqui.',
								'resgata-wp'
							) }
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
									<TableRow key={ row.id } className="cursor-pointer" onClick={ () => navigate( `/adocoes/${ row.original.id }` ) }>
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
									/* translators: %d: quantidade de pedidos. */
									_n( '%d pedido', '%d pedidos', data.total, 'resgata-wp' ),
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
		</>
	);
}
