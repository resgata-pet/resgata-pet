import { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { createColumnHelper, flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { __, sprintf } from '@wordpress/i18n';
import { ChevronLeftIcon, ChevronRightIcon, PawPrintIcon, PlusIcon } from 'lucide-react';
import { api, errorMessage, type AnimalListItem } from '@/lib/api';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';

const PAGE_SIZES = [ 25, 50, 75, 100 ];
const column = createColumnHelper< AnimalListItem >();

export function AnimalsListPage() {
	const navigate = useNavigate();
	const [ pagination, setPagination ] = useState( { pageIndex: 0, pageSize: PAGE_SIZES[ 0 ] } );

	const { data, isPending, isError, error, isPlaceholderData } = useQuery( {
		queryKey: [ 'animals', pagination.pageIndex, pagination.pageSize ],
		queryFn: () => api.listAnimals( pagination.pageIndex + 1, pagination.pageSize ),
		placeholderData: keepPreviousData,
	} );

	const columns = useMemo(
		() => [
			column.accessor( 'thumbnail', {
				header: () => <span className="sr-only">{ __( 'Foto', 'resgata-wp' ) }</span>,
				cell: ( info ) =>
					info.getValue() ? (
						<img src={ info.getValue() } alt="" className="size-12 rounded-lg object-cover" loading="lazy" />
					) : (
						<span className="bg-muted text-muted-foreground flex size-12 items-center justify-center rounded-lg">
							<PawPrintIcon className="size-5" aria-hidden="true" />
						</span>
					),
			} ),
			column.accessor( 'name', {
				header: () => __( 'Nome', 'resgata-wp' ),
				cell: ( info ) => (
					<div className="flex flex-wrap items-center gap-2">
						<Link
							to={ `/animais/${ info.row.original.id }` }
							className="font-medium underline-offset-4 hover:underline"
							onClick={ ( event ) => event.stopPropagation() }
						>
							{ info.getValue() || __( '(sem nome)', 'resgata-wp' ) }
						</Link>
						{ info.row.original.post_status === 'draft' && (
							<Badge variant="muted">{ __( 'Rascunho', 'resgata-wp' ) }</Badge>
						) }
					</div>
				),
			} ),
			column.accessor( 'status', {
				header: () => __( 'Situação', 'resgata-wp' ),
				cell: ( info ) => <StatusBadge status={ info.getValue() } />,
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
		<>
			<PageHeader
				title={ __( 'Animais', 'resgata-wp' ) }
				action={
					<Link to="/animais/novo" className={ buttonVariants() }>
						<PlusIcon aria-hidden="true" />
						{ __( 'Cadastrar animal', 'resgata-wp' ) }
					</Link>
				}
			/>

			{ isError && (
				<p className="text-destructive" role="alert">
					{ errorMessage( error ) }
				</p>
			) }

			{ isPending && (
				<div className="flex flex-col gap-3" aria-busy="true">
					{ [ 0, 1, 2, 3 ].map( ( key ) => (
						<Skeleton key={ key } className="h-16 w-full" />
					) ) }
				</div>
			) }

			{ data && data.total === 0 && (
				<div className="bg-card flex flex-col items-center gap-3 rounded-xl border p-8 text-center">
					<PawPrintIcon className="text-brand size-10" aria-hidden="true" />
					<p className="font-medium">{ __( 'Nenhum animal cadastrado ainda.', 'resgata-wp' ) }</p>
					<Link to="/animais/novo" className={ buttonVariants() }>
						{ __( 'Cadastrar o primeiro', 'resgata-wp' ) }
					</Link>
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
											<TableHead key={ header.id } className={ header.column.id === 'thumbnail' ? 'w-16' : undefined }>
												{ flexRender( header.column.columnDef.header, header.getContext() ) }
											</TableHead>
										) ) }
									</TableRow>
								) ) }
							</TableHeader>
							<TableBody>
								{ table.getRowModel().rows.map( ( row ) => (
									<TableRow
										key={ row.id }
										className="cursor-pointer"
										onClick={ () => navigate( `/animais/${ row.original.id }` ) }
									>
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
						<label className="text-muted-foreground flex items-center gap-2 text-sm">
							{ __( 'Itens por página', 'resgata-wp' ) }
							<NativeSelect
								className="w-24"
								value={ pagination.pageSize }
								onChange={ ( event ) => setPagination( { pageIndex: 0, pageSize: Number( event.target.value ) } ) }
							>
								{ PAGE_SIZES.map( ( size ) => (
									<NativeSelectOption key={ size } value={ size }>
										{ size }
									</NativeSelectOption>
								) ) }
							</NativeSelect>
						</label>

						<div className="flex items-center gap-2">
							<span className="text-muted-foreground text-sm">
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
