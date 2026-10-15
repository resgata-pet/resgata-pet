import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { ChevronDownIcon, FilterIcon } from 'lucide-react';
import { emptyFilters, type AnimalFilters } from '@/lib/api';
import { config, type OptionMap } from '@/lib/config';
import { useMediaQuery } from '@/hooks/use-media-query';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { DatePicker } from '@/components/date-picker';

const POST_STATUSES: OptionMap = {
	publish: __( 'Publicado', 'resgata-pet' ),
	draft: __( 'Rascunho', 'resgata-pet' ),
};

type ListKey = 'species' | 'status' | 'post_status' | 'sex' | 'size' | 'neutered';

/** Quantos filtros estão ativos (a busca por nome não conta: ela fica fora do painel). */
export function countFilters( filters: AnimalFilters ): number {
	const lists: ListKey[] = [ 'species', 'status', 'post_status', 'sex', 'size', 'neutered' ];

	return (
		lists.filter( ( key ) => filters[ key ].length > 0 ).length + ( filters.rescue_from || filters.rescue_to ? 1 : 0 )
	);
}

function CheckboxGroup( {
	title,
	options,
	selected,
	onChange,
}: {
	title: string;
	options: OptionMap;
	selected: string[];
	onChange: ( selected: string[] ) => void;
} ) {
	return (
		<fieldset>
			<legend className="mb-2 text-sm font-semibold">{ title }</legend>
			<div className="flex flex-col">
				{ Object.entries( options ).map( ( [ value, label ] ) => (
					<label key={ value } className="flex min-h-10 cursor-pointer items-center gap-3 text-sm">
						<Checkbox
							checked={ selected.includes( value ) }
							onCheckedChange={ ( checked ) =>
								onChange( checked ? [ ...selected, value ] : selected.filter( ( item ) => item !== value ) )
							}
						/>
						{ label }
					</label>
				) ) }
			</div>
		</fieldset>
	);
}

/** Campos do painel de filtros (iguais no computador e no celular). */
function FilterFields( { draft, setDraft }: { draft: AnimalFilters; setDraft: ( filters: AnimalFilters ) => void } ) {
	const set = < K extends keyof AnimalFilters >( key: K, value: AnimalFilters[ K ] ) => setDraft( { ...draft, [ key ]: value } );

	return (
		<div className="grid gap-x-8 gap-y-6 md:grid-cols-[minmax(20rem,1.5fr)_1fr_1.3fr_1fr]">
			<div className="flex flex-col gap-6">
				<fieldset className="min-w-0">
					<legend className="mb-2 text-sm font-semibold">{ __( 'Período do resgate', 'resgata-pet' ) }</legend>
					{ /* Colunas iguais: os botões de data têm largura mínima própria e não encolhem sozinhos. */ }
					<div className="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2">
						<DatePicker
							value={ draft.rescue_from }
							placeholder={ __( 'Início', 'resgata-pet' ) }
							ariaLabel={ __( 'Resgatado a partir de', 'resgata-pet' ) }
							onChange={ ( value ) => set( 'rescue_from', value ) }
						/>
						<span className="text-muted-foreground text-sm">{ __( 'até', 'resgata-pet' ) }</span>
						<DatePicker
							value={ draft.rescue_to }
							placeholder={ __( 'Fim', 'resgata-pet' ) }
							ariaLabel={ __( 'Resgatado até', 'resgata-pet' ) }
							onChange={ ( value ) => set( 'rescue_to', value ) }
						/>
					</div>
				</fieldset>
				<CheckboxGroup
					title={ __( 'Publicação', 'resgata-pet' ) }
					options={ POST_STATUSES }
					selected={ draft.post_status }
					onChange={ ( value ) => set( 'post_status', value ) }
				/>
			</div>

			<div className="flex flex-col gap-6">
				<CheckboxGroup
					title={ __( 'Espécie', 'resgata-pet' ) }
					options={ config.options.species }
					selected={ draft.species }
					onChange={ ( value ) => set( 'species', value ) }
				/>
				<CheckboxGroup
					title={ __( 'Sexo', 'resgata-pet' ) }
					options={ config.options.sexes }
					selected={ draft.sex }
					onChange={ ( value ) => set( 'sex', value ) }
				/>
			</div>

			<CheckboxGroup
				title={ __( 'Situação', 'resgata-pet' ) }
				options={ config.options.statuses }
				selected={ draft.status }
				onChange={ ( value ) => set( 'status', value ) }
			/>

			<div className="flex flex-col gap-6">
				<CheckboxGroup
					title={ __( 'Porte', 'resgata-pet' ) }
					options={ config.options.sizes }
					selected={ draft.size }
					onChange={ ( value ) => set( 'size', value ) }
				/>
				<CheckboxGroup
					title={ __( 'Castrado(a)', 'resgata-pet' ) }
					options={ config.options.yesNo }
					selected={ draft.neutered }
					onChange={ ( value ) => set( 'neutered', value ) }
				/>
			</div>
		</div>
	);
}

/**
 * Botão "Filtros" da lista de animais. No computador abre um painel logo
 * abaixo do botão; no celular, uma tela que desliza da direita para a esquerda.
 * Nada é aplicado até tocar em "Aplicar".
 */
export function AnimalFiltersButton( {
	filters,
	onApply,
}: {
	filters: AnimalFilters;
	onApply: ( filters: AnimalFilters ) => void;
} ) {
	const isDesktop = useMediaQuery( '(min-width: 768px)' );
	const [ open, setOpen ] = useState( false );
	const [ draft, setDraft ] = useState( filters );
	const active = countFilters( filters );

	// Ao abrir, o painel começa com os filtros que estão valendo.
	useEffect( () => {
		if ( open ) {
			setDraft( filters );
		}
	}, [ open, filters ] );

	const apply = () => {
		onApply( { ...draft, search: filters.search } );
		setOpen( false );
	};

	const clear = () => {
		onApply( { ...emptyFilters, search: filters.search } );
		setOpen( false );
	};

	const trigger = (
		<Button type="button" variant={ active ? 'secondary' : 'outline' } className={ active ? 'border-brand' : undefined } />
	);

	const triggerContent = (
		<>
			<FilterIcon aria-hidden="true" />
			{ __( 'Filtros', 'resgata-pet' ) }
			{ active > 0 && (
				<span className="bg-primary text-primary-foreground flex size-5 items-center justify-center rounded-full text-xs">
					{ active }
				</span>
			) }
			<ChevronDownIcon className="hidden md:block" aria-hidden="true" />
		</>
	);

	const actions = (
		<>
			<Button type="button" variant="outline" onClick={ clear }>
				{ __( 'Limpar', 'resgata-pet' ) }
			</Button>
			<Button type="button" onClick={ apply }>
				{ __( 'Aplicar', 'resgata-pet' ) }
			</Button>
		</>
	);

	if ( isDesktop ) {
		return (
			<Popover open={ open } onOpenChange={ setOpen }>
				<PopoverTrigger render={ trigger }>{ triggerContent }</PopoverTrigger>
				<PopoverContent align="start" className="w-[min(64rem,calc(100vw-20rem))] gap-0 p-0">
					<div className="p-6">
						<FilterFields draft={ draft } setDraft={ setDraft } />
					</div>
					<div className="flex justify-end gap-2 border-t p-4">{ actions }</div>
				</PopoverContent>
			</Popover>
		);
	}

	return (
		<Sheet open={ open } onOpenChange={ setOpen }>
			<SheetTrigger render={ trigger }>{ triggerContent }</SheetTrigger>
			<SheetContent
				side="right"
				className="gap-0 duration-300 data-ending-style:opacity-100 data-starting-style:opacity-100 data-[side=right]:w-[88%] data-[side=right]:data-ending-style:translate-x-full data-[side=right]:data-starting-style:translate-x-full"
			>
				<SheetHeader className="border-b">
					<SheetTitle>{ __( 'Filtros', 'resgata-pet' ) }</SheetTitle>
				</SheetHeader>
				<div className="flex-1 overflow-y-auto p-4">
					<FilterFields draft={ draft } setDraft={ setDraft } />
				</div>
				<SheetFooter className="grid grid-cols-2 gap-2 border-t pb-[max(1rem,env(safe-area-inset-bottom))]">{ actions }</SheetFooter>
			</SheetContent>
		</Sheet>
	);
}
