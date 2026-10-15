/**
 * Registro dos blocos do Resgata Pet no editor.
 *
 * Os blocos são renderizados no servidor (render.php); aqui ficam só os
 * controles da barra lateral e a prévia.
 */
import * as wpBlocks from '@wordpress/blocks';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	CheckboxControl,
	Disabled,
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

type OptionMap = Record< string, string >;

declare global {
	interface Window {
		resgataBlocks?: {
			species: OptionMap;
			statuses: OptionMap;
			bindings?: { links: OptionMap; organization: OptionMap };
		};
	}
}

type ListAttributes = {
	species: string;
	statuses: string[];
	perPage: number;
	columns: number;
	showFilters: boolean;
	showPagination: boolean;
};

type DetailsAttributes = {
	section: 'all' | 'breadcrumb' | 'gallery' | 'summary' | 'story' | 'health' | 'help' | 'related' | 'facts';
};

type ContactAttributes = {
	showPix: boolean;
	compact: boolean;
};

type SponsorshipPlansAttributes = {
	showCounter: boolean;
};

interface EditProps< T > {
	attributes: T;
	setAttributes: ( attributes: Partial< T > ) => void;
	context?: { postId?: number };
}

const data = window.resgataBlocks ?? { species: {}, statuses: {} };

function toOptions( map: OptionMap, emptyLabel?: string ) {
	const options = Object.entries( map ).map( ( [ value, label ] ) => ( { value, label } ) );

	return emptyLabel ? [ { value: '', label: emptyLabel }, ...options ] : options;
}

function ListEdit( { attributes, setAttributes }: EditProps< ListAttributes > ) {
	const toggleStatus = ( status: string, checked: boolean ) => {
		const next = attributes.statuses.filter( ( item ) => item !== status );
		setAttributes( { statuses: checked ? [ ...next, status ] : next } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Quais animais mostrar', 'resgata-pet' ) }>
					<SelectControl
						label={ __( 'Espécie', 'resgata-pet' ) }
						value={ attributes.species }
						options={ toOptions( data.species, __( 'Todas', 'resgata-pet' ) ) }
						onChange={ ( species: string ) => setAttributes( { species } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<p style={ { marginTop: '16px', marginBottom: '8px' } }>{ __( 'Situação', 'resgata-pet' ) }</p>
					{ Object.entries( data.statuses ).map( ( [ status, label ] ) => (
						<CheckboxControl
							key={ status }
							label={ label }
							checked={ attributes.statuses.includes( status ) }
							onChange={ ( checked: boolean ) => toggleStatus( status, checked ) }
							__nextHasNoMarginBottom
						/>
					) ) }
				</PanelBody>
				<PanelBody title={ __( 'Aparência', 'resgata-pet' ) }>
					<RangeControl
						label={ __( 'Animais por página', 'resgata-pet' ) }
						value={ attributes.perPage }
						min={ 1 }
						max={ 48 }
						onChange={ ( value?: number ) => setAttributes( { perPage: value || 12 } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<RangeControl
						label={ __( 'Colunas', 'resgata-pet' ) }
						value={ attributes.columns }
						min={ 1 }
						max={ 6 }
						onChange={ ( value?: number ) => setAttributes( { columns: value || 3 } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Mostrar filtros para o visitante', 'resgata-pet' ) }
						checked={ attributes.showFilters }
						onChange={ ( showFilters: boolean ) => setAttributes( { showFilters } ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Mostrar paginação', 'resgata-pet' ) }
						checked={ attributes.showPagination }
						onChange={ ( showPagination: boolean ) => setAttributes( { showPagination } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender
						block="resgata-pet/animals-list"
						attributes={ attributes }
						skipBlockSupportAttributes
					/>
				</Disabled>
			</div>
		</>
	);
}

function DetailsEdit( { attributes, setAttributes, context }: EditProps< DetailsAttributes > ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'O que mostrar', 'resgata-pet' ) }>
					<SelectControl
						label={ __( 'Parte da página', 'resgata-pet' ) }
						help={ __( 'Deixe em "Página inteira" para usar o layout pronto.', 'resgata-pet' ) }
						value={ attributes.section }
						options={ [
							{ value: 'all', label: __( 'Página inteira', 'resgata-pet' ) },
							{ value: 'breadcrumb', label: __( 'Somente o caminho (Início › Animais)', 'resgata-pet' ) },
							{ value: 'gallery', label: __( 'Somente as fotos', 'resgata-pet' ) },
							{ value: 'summary', label: __( 'Somente o resumo (nome, dados e botões)', 'resgata-pet' ) },
							{ value: 'story', label: __( 'Somente a história', 'resgata-pet' ) },
							{ value: 'health', label: __( 'Somente a saúde', 'resgata-pet' ) },
							{ value: 'help', label: __( 'Somente "Não pode adotar?"', 'resgata-pet' ) },
							{ value: 'related', label: __( 'Somente outros animais', 'resgata-pet' ) },
							{ value: 'facts', label: __( 'Somente os dados, sem o nome', 'resgata-pet' ) },
						] }
						onChange={ ( section: string ) =>
							setAttributes( { section: section as DetailsAttributes[ 'section' ] } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender
						block="resgata-pet/animal-details"
						attributes={ attributes }
						skipBlockSupportAttributes
						urlQueryArgs={ context?.postId ? { post_id: context.postId } : {} }
					/>
				</Disabled>
			</div>
		</>
	);
}

function AdoptionFormEdit( { attributes, context }: EditProps< Record< string, never > > ) {
	return (
		<div { ...useBlockProps() }>
			<Disabled>
				<ServerSideRender
					block="resgata-pet/adoption-form"
					attributes={ attributes }
					skipBlockSupportAttributes
					urlQueryArgs={ context?.postId ? { post_id: context.postId } : {} }
				/>
			</Disabled>
		</div>
	);
}

function ContactEdit( { attributes, setAttributes }: EditProps< ContactAttributes > ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'O que mostrar', 'resgata-pet' ) }>
					<ToggleControl
						label={ __( 'Mostrar a chave Pix', 'resgata-pet' ) }
						help={ __( 'Os dados vêm das configurações do painel Resgata.', 'resgata-pet' ) }
						checked={ attributes.showPix }
						onChange={ ( showPix: boolean ) => setAttributes( { showPix } ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Versão resumida', 'resgata-pet' ) }
						help={ __( 'Só WhatsApp, telefone, e-mail, local e redes sociais. Boa para o rodapé.', 'resgata-pet' ) }
						checked={ attributes.compact }
						onChange={ ( compact: boolean ) => setAttributes( { compact } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender block="resgata-pet/contact" attributes={ attributes } skipBlockSupportAttributes />
				</Disabled>
			</div>
		</>
	);
}

function SponsorshipPlansEdit( { attributes, setAttributes }: EditProps< SponsorshipPlansAttributes > ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'O que mostrar', 'resgata-pet' ) }>
					<ToggleControl
						label={ __( 'Mostrar o número de padrinhos', 'resgata-pet' ) }
						help={ __( 'O número é atualizado sozinho a cada apadrinhamento novo ou cancelado.', 'resgata-pet' ) }
						checked={ attributes.showCounter }
						onChange={ ( showCounter: boolean ) => setAttributes( { showCounter } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender block="resgata-pet/sponsorship-plans" attributes={ attributes } skipBlockSupportAttributes />
				</Disabled>
			</div>
		</>
	);
}

function SponsorshipThanksEdit( { attributes }: EditProps< Record< string, never > > ) {
	return (
		<div { ...useBlockProps() }>
			<Disabled>
				<ServerSideRender block="resgata-pet/sponsorship-thanks" attributes={ attributes } skipBlockSupportAttributes />
			</Disabled>
		</div>
	);
}

function SponsorAreaEdit( { attributes }: EditProps< Record< string, never > > ) {
	return (
		<div { ...useBlockProps() }>
			<Disabled>
				<ServerSideRender block="resgata-pet/sponsor-area" attributes={ attributes } skipBlockSupportAttributes />
			</Disabled>
		</div>
	);
}

function LinksEdit( { attributes, setAttributes }: EditProps< { group: 'site' | 'help' } > ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'O que mostrar', 'resgata-pet' ) }>
					<SelectControl
						label={ __( 'Lista', 'resgata-pet' ) }
						help={ __( 'Só aparecem as páginas que já existem no site.', 'resgata-pet' ) }
						value={ attributes.group }
						options={ [
							{ value: 'site', label: __( 'O site (início, animais, contato)', 'resgata-pet' ) },
							{ value: 'help', label: __( 'Formas de ajudar', 'resgata-pet' ) },
						] }
						onChange={ ( group: string ) => setAttributes( { group: group as 'site' | 'help' } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender block="resgata-pet/links" attributes={ attributes } skipBlockSupportAttributes />
				</Disabled>
			</div>
		</>
	);
}

/** Blocos sem opções: só a prévia montada no servidor. */
function previewOnly( block: string ) {
	return function Edit( { attributes }: EditProps< Record< string, never > > ) {
		return (
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender block={ block } attributes={ attributes } skipBlockSupportAttributes />
				</Disabled>
			</div>
		);
	};
}

/*
 * Os metadados (título, atributos, etc.) vêm do block.json registrado no PHP;
 * por isso os tipos do pacote, que esperam a configuração completa, não se aplicam aqui.
 */
const register = registerBlockType as unknown as (
	name: string,
	settings: { edit: ( props: any ) => React.JSX.Element; save: () => null }
) => void;

/*
 * Ligações de blocos (`resgata-pet/link` e `resgata-pet/organization`): no site, o valor é
 * resolvido no servidor (Core\Bindings). Aqui o editor recebe os valores atuais para mostrar
 * o texto e o link de verdade; os campos ligados continuam travados, porque vêm dos cadastros.
 */
const registerSource = ( wpBlocks as unknown as { registerBlockBindingsSource?: ( source: Record< string, unknown > ) => void } )
	.registerBlockBindingsSource;
const bindingValues = window.resgataBlocks?.bindings;

if ( registerSource && bindingValues ) {
	const source = ( name: string, label: string, values: OptionMap ) =>
		registerSource( {
			name,
			label,
			getValues: ( { bindings }: { bindings: Record< string, { args?: { key?: string } } > } ) =>
				Object.fromEntries( Object.entries( bindings ).map( ( [ attribute, binding ] ) => [ attribute, values[ binding.args?.key ?? '' ] ?? '' ] ) ),
			canUserEditValue: () => false,
		} );

	source( 'resgata-pet/link', __( 'Página do Resgata Pet', 'resgata-pet' ), bindingValues.links );
	source( 'resgata-pet/organization', __( 'Dados da protetora', 'resgata-pet' ), bindingValues.organization );
}

register( 'resgata-pet/animals-list', { edit: ListEdit, save: () => null } );
register( 'resgata-pet/animal-details', { edit: DetailsEdit, save: () => null } );
register( 'resgata-pet/contact', { edit: ContactEdit, save: () => null } );
register( 'resgata-pet/links', { edit: LinksEdit, save: () => null } );
register( 'resgata-pet/banners', { edit: previewOnly( 'resgata-pet/banners' ), save: () => null } );
register( 'resgata-pet/pix-donation', { edit: previewOnly( 'resgata-pet/pix-donation' ), save: () => null } );
register( 'resgata-pet/pix-once', { edit: previewOnly( 'resgata-pet/pix-once' ), save: () => null } );
register( 'resgata-pet/donate-banner', { edit: previewOnly( 'resgata-pet/donate-banner' ), save: () => null } );
register( 'resgata-pet/impact', { edit: previewOnly( 'resgata-pet/impact' ), save: () => null } );
register( 'resgata-pet/adoption-form', { edit: AdoptionFormEdit, save: () => null } );
register( 'resgata-pet/sponsorship-plans', { edit: SponsorshipPlansEdit, save: () => null } );
register( 'resgata-pet/sponsor-area', { edit: SponsorAreaEdit, save: () => null } );
register( 'resgata-pet/sponsorship-thanks', { edit: SponsorshipThanksEdit, save: () => null } );
