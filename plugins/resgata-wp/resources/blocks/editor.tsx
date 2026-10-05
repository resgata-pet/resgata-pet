/**
 * Registro dos blocos do Resgata WP no editor.
 *
 * Os blocos são renderizados no servidor (render.php); aqui ficam só os
 * controles da barra lateral e a prévia.
 */
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
		rwpBlocks?: { species: OptionMap; statuses: OptionMap };
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
	section: 'all' | 'facts' | 'health' | 'gallery';
};

type ContactAttributes = {
	showPix: boolean;
};

interface EditProps< T > {
	attributes: T;
	setAttributes: ( attributes: Partial< T > ) => void;
	context?: { postId?: number };
}

const data = window.rwpBlocks ?? { species: {}, statuses: {} };

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
				<PanelBody title={ __( 'Quais animais mostrar', 'resgata-wp' ) }>
					<SelectControl
						label={ __( 'Espécie', 'resgata-wp' ) }
						value={ attributes.species }
						options={ toOptions( data.species, __( 'Todas', 'resgata-wp' ) ) }
						onChange={ ( species: string ) => setAttributes( { species } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<p style={ { marginTop: '16px', marginBottom: '8px' } }>{ __( 'Situação', 'resgata-wp' ) }</p>
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
				<PanelBody title={ __( 'Aparência', 'resgata-wp' ) }>
					<RangeControl
						label={ __( 'Animais por página', 'resgata-wp' ) }
						value={ attributes.perPage }
						min={ 1 }
						max={ 48 }
						onChange={ ( value?: number ) => setAttributes( { perPage: value || 12 } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<RangeControl
						label={ __( 'Colunas', 'resgata-wp' ) }
						value={ attributes.columns }
						min={ 1 }
						max={ 6 }
						onChange={ ( value?: number ) => setAttributes( { columns: value || 3 } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Mostrar filtros para o visitante', 'resgata-wp' ) }
						checked={ attributes.showFilters }
						onChange={ ( showFilters: boolean ) => setAttributes( { showFilters } ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Mostrar paginação', 'resgata-wp' ) }
						checked={ attributes.showPagination }
						onChange={ ( showPagination: boolean ) => setAttributes( { showPagination } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender
						block="resgata-wp/animals-list"
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
				<PanelBody title={ __( 'O que mostrar', 'resgata-wp' ) }>
					<SelectControl
						label={ __( 'Parte da ficha', 'resgata-wp' ) }
						value={ attributes.section }
						options={ [
							{ value: 'all', label: __( 'Tudo (dados, saúde e fotos)', 'resgata-wp' ) },
							{ value: 'facts', label: __( 'Somente os dados', 'resgata-wp' ) },
							{ value: 'health', label: __( 'Somente a saúde', 'resgata-wp' ) },
							{ value: 'gallery', label: __( 'Somente as fotos (antes e depois)', 'resgata-wp' ) },
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
						block="resgata-wp/animal-details"
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
					block="resgata-wp/adoption-form"
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
				<PanelBody title={ __( 'O que mostrar', 'resgata-wp' ) }>
					<ToggleControl
						label={ __( 'Mostrar a chave Pix', 'resgata-wp' ) }
						help={ __( 'Os dados vêm das configurações do painel Resgata.', 'resgata-wp' ) }
						checked={ attributes.showPix }
						onChange={ ( showPix: boolean ) => setAttributes( { showPix } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender block="resgata-wp/contact" attributes={ attributes } skipBlockSupportAttributes />
				</Disabled>
			</div>
		</>
	);
}

/*
 * Os metadados (título, atributos, etc.) vêm do block.json registrado no PHP;
 * por isso os tipos do pacote, que esperam a configuração completa, não se aplicam aqui.
 */
const register = registerBlockType as unknown as (
	name: string,
	settings: { edit: ( props: any ) => React.JSX.Element; save: () => null }
) => void;

register( 'resgata-wp/animals-list', { edit: ListEdit, save: () => null } );
register( 'resgata-wp/animal-details', { edit: DetailsEdit, save: () => null } );
register( 'resgata-wp/contact', { edit: ContactEdit, save: () => null } );
register( 'resgata-wp/adoption-form', { edit: AdoptionFormEdit, save: () => null } );
