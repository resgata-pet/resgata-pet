import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper, flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { __, _n, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ChevronLeftIcon, ChevronRightIcon, PawPrintIcon, PlusIcon, SearchIcon, XIcon } from 'lucide-react';
import { api, emptyFilters, errorMessage, type AnimalFilters, type AnimalList, type AnimalListItem, type PostStatus } from '@/lib/api';
import { config } from '@/lib/config';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { AnimalFiltersButton, countFilters } from '@/components/animal-filters';
import { OptionSelect } from '@/components/option-select';
import { PageHeader } from '@/components/page-header';

const PAGE_SIZES = [ 25, 50, 75, 100 ];
const PAGE_SIZE_OPTIONS = Object.fromEntries( PAGE_SIZES.map( ( size ) => [ String( size ), String( size ) ] ) );
const column = createColumnHelper< AnimalListItem >();

type QuickChanges = { status?: string; post_status?: PostStatus };

/** Nome de cada filtro na URL, para os filtros sobreviverem a "abrir um animal e voltar". */
const URL_KEYS: Record< keyof AnimalFilters, string > = {
	search: 'busca',
	species: 'especie',
	status: 'situacao',
	post_status: 'publicacao',
	sex: 'sexo',
	size: 'porte',
	neutered: 'castrado',
	rescue_from: 'de',
	rescue_to: 'ate',
};

function filtersFromUrl( params: URLSearchParams ): AnimalFilters {
	const filters = { ...emptyFilters };

	for ( const key of Object.keys( URL_KEYS ) as ( keyof AnimalFilters )[] ) {
		const value = params.get( URL_KEYS[ key ] ) ?? '';

		if ( Array.isArray( emptyFilters[ key ] ) ) {
			( filters[ key ] as string[] ) = value ? value.split( ',' ).filter( Boolean ) : [];
		} else {
			( filters[ key ] as string ) = value;
		}
	}

	return filters;
}

function filtersToUrl( filters: AnimalFilters ): Record< string, string > {
	const params: Record< string, string > = {};

	for ( const key of Object.keys( URL_KEYS ) as ( keyof AnimalFilters )[] ) {
		const value = filters[ key ];
		const text = Array.isArray( value ) ? value.join( ',' ) : value.trim();

		if ( text !== '' ) {
			params[ URL_KEYS[ key ] ] = text;
		}
	}

	return params;
}

export function AnimalsListPage() {
	const navigate = useNavigate();
	const [ pagination, setPagination ] = useState( { pageIndex: 0, pageSize: PAGE_SIZES[ 0 ] } );

	const [ searchParams, setSearchParams ] = useSearchParams();
	const urlKey = searchParams.toString();
	const filters = useMemo( () => filtersFromUrl( new URLSearchParams( urlKey ) ), [ urlKey ] );
	const [ searchText, setSearchText ] = useState( filters.search );
	const hasFilters = filters.search !== '' || countFilters( filters ) > 0;

	// Mantém o campo de busca igual à URL (ex.: ao limpar tudo ou usar o "voltar" do navegador).
	useEffect( () => setSearchText( filters.search ), [ filters.search ] );

	const applyFilters = ( next: AnimalFilters ) => {
		setSearchParams( filtersToUrl( next ), { replace: true } );
		setPagination( ( current ) => ( { ...current, pageIndex: 0 } ) );
	};

	const submitSearch = ( event: FormEvent ) => {
		event.preventDefault();
		applyFilters( { ...filters, search: searchText.trim() } );
	};

	const { data, isPending, isError, error, isPlaceholderData } = useQuery( {
		queryKey: [ 'animals', pagination.pageIndex, pagination.pageSize, urlKey ],
		queryFn: () => api.listAnimals( pagination.pageIndex + 1, pagination.pageSize, filters ),
		placeholderData: keepPreviousData,
	} );

	const queryClient = useQueryClient();
	const queryKey = [ 'animals', pagination.pageIndex, pagination.pageSize, urlKey ];

	// Troca rápida de situação/publicação: a linha muda na hora e volta atrás se o servidor recusar.
	const quickUpdate = useMutation( {
		mutationFn: ( { id, changes }: { id: number; changes: QuickChanges } ) => api.updateAnimal( id, changes ),
		onMutate: async ( { id, changes } ) => {
			await queryClient.cancelQueries( { queryKey: [ 'animals' ] } );
			const previous = queryClient.getQueryData< AnimalList >( queryKey );

			queryClient.setQueryData< AnimalList >( queryKey, ( list ) =>
				list ? { ...list, items: list.items.map( ( item ) => ( item.id === id ? { ...item, ...changes } : item ) ) } : list
			);

			return { previous };
		},
		onError: ( mutationError, _variables, context ) => {
			queryClient.setQueryData( queryKey, context?.previous );
			toast.error( errorMessage( mutationError ) );
		},
		onSuccess: ( animal, { changes } ) => {
			queryClient.setQueryData( [ 'animal', animal.id ], animal );

			if ( changes.post_status === 'publish' ) {
				toast.success( __( 'Animal publicado no site.', 'resgata-wp' ) );
			} else if ( changes.post_status === 'draft' ) {
				toast.success( __( 'Animal voltou para rascunho e saiu do site.', 'resgata-wp' ) );
			} else {
				toast.success( __( 'Situação atualizada.', 'resgata-wp' ) );
			}
		},
	} );
	const { mutate: updateRow } = quickUpdate;

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
					<Link
						to={ `/animais/${ info.row.original.id }` }
						className="font-medium"
						onClick={ ( event ) => event.stopPropagation() }
					>
						{ info.getValue() || __( '(sem nome)', 'resgata-wp' ) }
					</Link>
				),
			} ),
			column.accessor( 'status', {
				header: () => __( 'Situação', 'resgata-wp' ),
				cell: ( info ) => (
					// O clique aqui não pode abrir a edição (a linha inteira é clicável).
					<div className="w-56 max-w-full" onClick={ ( event ) => event.stopPropagation() }>
						<OptionSelect
							ariaLabel={ sprintf(
								/* translators: %s: nome do animal. */
								__( 'Situação de %s', 'resgata-wp' ),
								info.row.original.name
							) }
							value={ info.getValue() }
							options={ config.options.statuses }
							onChange={ ( status ) => {
								if ( status && status !== info.getValue() ) {
									updateRow( { id: info.row.original.id, changes: { status } } );
								}
							} }
						/>
					</div>
				),
			} ),
			column.accessor( 'post_status', {
				header: () => __( 'No site', 'resgata-wp' ),
				cell: ( info ) => {
					const published = info.getValue() === 'publish';

					return (
						<label className="flex w-fit cursor-pointer items-center gap-3" onClick={ ( event ) => event.stopPropagation() }>
							<Switch
								checked={ published }
								aria-label={ sprintf(
									/* translators: %s: nome do animal. */
									__( 'Publicar %s no site', 'resgata-wp' ),
									info.row.original.name
								) }
								onCheckedChange={ ( checked ) =>
									updateRow( { id: info.row.original.id, changes: { post_status: checked ? 'publish' : 'draft' } } )
								}
							/>
							<span className={ published ? 'hidden text-sm sm:inline' : 'text-muted-foreground hidden text-sm sm:inline' }>
								{ published ? __( 'Publicado', 'resgata-wp' ) : __( 'Rascunho', 'resgata-wp' ) }
							</span>
						</label>
					);
				},
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

			<div className="mb-4 flex flex-wrap items-center gap-2">
				<form className="flex min-w-0 flex-1 items-center gap-2 sm:max-w-sm" role="search" onSubmit={ submitSearch }>
					<div className="relative min-w-0 flex-1">
						<Input
							type="search"
							enterKeyHint="search"
							className="pr-10 [&::-webkit-search-cancel-button]:hidden"
							placeholder={ __( 'Procurar pelo nome', 'resgata-wp' ) }
							aria-label={ __( 'Procurar animal pelo nome', 'resgata-wp' ) }
							value={ searchText }
							onChange={ ( event ) => setSearchText( event.target.value ) }
						/>
						{ searchText !== '' && (
							<button
								type="button"
								className="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 flex size-7 -translate-y-1/2 items-center justify-center rounded-md"
								aria-label={ __( 'Limpar busca', 'resgata-wp' ) }
								onClick={ () => {
									setSearchText( '' );
									if ( filters.search !== '' ) {
										applyFilters( { ...filters, search: '' } );
									}
								} }
							>
								<XIcon className="size-4" aria-hidden="true" />
							</button>
						) }
					</div>
					<Button type="submit" variant="outline" size="icon" aria-label={ __( 'Procurar', 'resgata-wp' ) }>
						<SearchIcon aria-hidden="true" />
					</Button>
				</form>
				<AnimalFiltersButton filters={ filters } onApply={ applyFilters } />
			</div>

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

			{ data && data.total === 0 && hasFilters && (
				<div className="bg-card flex flex-col items-center gap-3 rounded-xl border p-8 text-center">
					<SearchIcon className="text-brand size-10" aria-hidden="true" />
					<p className="font-medium">{ __( 'Nenhum animal encontrado com esses filtros.', 'resgata-wp' ) }</p>
					<Button type="button" variant="outline" onClick={ () => applyFilters( emptyFilters ) }>
						{ __( 'Limpar busca e filtros', 'resgata-wp' ) }
					</Button>
				</div>
			) }

			{ data && data.total === 0 && ! hasFilters && (
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
						<div className="text-muted-foreground flex items-center gap-2 text-sm">
							<span id="rwp-page-size">{ __( 'Itens por página', 'resgata-wp' ) }</span>
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
									/* translators: %d: quantidade de animais. */
									_n( '%d animal', '%d animais', data.total, 'resgata-wp' ),
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
