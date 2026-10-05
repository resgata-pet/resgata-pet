/**
 * Acesso à API do painel (`resgata-wp/v1`). O nonce é enviado pelo
 * `wp-api-fetch`, configurado pelo próprio WordPress na página.
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { config } from '@/lib/config';
import { resizeImage } from '@/lib/image';

export type PostStatus = 'publish' | 'draft';
export type PhotoType = 'before' | 'after';

export interface AnimalImage {
	id: number;
	url: string;
}

export interface GalleryPhoto extends AnimalImage {
	type: PhotoType;
}

export interface Vaccine {
	name: string;
	date: string;
}

export interface Animal {
	id: number;
	name: string;
	post_status: PostStatus;
	permalink: string;
	story: string;
	species: string;
	status: string;
	sex: string;
	size: string;
	color: string;
	age_years: number | null;
	age_months: number | null;
	rescue_date: string;
	temperaments: string[];
	neutered: string;
	dewormed_date: string;
	fiv: string;
	felv: string;
	vaccines: Vaccine[];
	featured_image: AnimalImage | null;
	gallery: GalleryPhoto[];
}

/** Campos aceitos na criação e na edição. */
export type AnimalInput = Partial<
	Omit< Animal, 'id' | 'permalink' | 'featured_image' | 'gallery' > & {
		featured_media: number;
		gallery: { id: number; type: PhotoType }[];
	}
>;

export interface AnimalListItem {
	id: number;
	name: string;
	post_status: PostStatus;
	status: string;
	status_label: string;
	thumbnail: string;
	permalink: string;
}

export interface AnimalList {
	items: AnimalListItem[];
	total: number;
	total_pages: number;
}

/** Animal ligado a um pedido de adoção. */
export interface AdoptionAnimal {
	id: number;
	name: string;
	thumbnail: string;
	status: string;
	status_label: string;
	trashed: boolean;
}

export interface AdoptionListItem {
	id: number;
	name: string;
	status: string;
	date: string;
	animal: AdoptionAnimal | null;
}

export interface AdoptionList {
	items: AdoptionListItem[];
	total: number;
	total_pages: number;
	new_count: number;
}

export interface Adoption extends AdoptionListItem {
	answers: { key: string; label: string; value: string }[];
	whatsapp: string;
	whatsapp_url: string;
	email: string;
	notes: string;
	consent_at: string;
	consent_text: string;
}

/** Dados da protetora (todos em texto). */
export interface OrganizationSettings {
	name: string;
	tagline: string;
	cnpj: string;
	whatsapp: string;
	whatsapp_message: string;
	phone: string;
	email: string;
	hours: string;
	instagram: string;
	facebook: string;
	tiktok: string;
	youtube: string;
	city: string;
	state: string;
	neighborhood: string;
	adoption_email: string;
	adoption_fee: string;
	adoption_rules: string;
	privacy_contact: string;
	privacy_text: string;
	pix_key_type: string;
	pix_key: string;
	pix_holder: string;
}

export interface SettingsResponse {
	settings: OrganizationSettings;
	choices: { states: Record< string, string >; pixKeyTypes: Record< string, string > };
	contact_page: { id: number; url: string; published: boolean } | null;
	whatsapp_url: string;
}

/** Filtros da listagem de animais. Listas vazias e textos vazios significam "sem filtro". */
export interface AnimalFilters {
	search: string;
	species: string[];
	status: string[];
	post_status: string[];
	sex: string[];
	size: string[];
	neutered: string[];
	rescue_from: string;
	rescue_to: string;
}

export const emptyFilters: AnimalFilters = {
	search: '',
	species: [],
	status: [],
	post_status: [],
	sex: [],
	size: [],
	neutered: [],
	rescue_from: '',
	rescue_to: '',
};

const base = `/${ config.restNamespace }/animals`;

export const api = {
	listAnimals: ( page: number, perPage: number, filters: AnimalFilters = emptyFilters ) => {
		const params = new URLSearchParams( { page: String( page ), per_page: String( perPage ) } );

		for ( const [ key, value ] of Object.entries( filters ) ) {
			// A API aceita listas separadas por vírgula.
			const text = Array.isArray( value ) ? value.join( ',' ) : value.trim();
			if ( text !== '' ) {
				params.set( key, text );
			}
		}

		return apiFetch< AnimalList >( { path: `${ base }?${ params.toString() }` } );
	},

	getAnimal: ( id: number ) => apiFetch< Animal >( { path: `${ base }/${ id }` } ),

	createAnimal: ( data: AnimalInput ) => apiFetch< Animal >( { path: base, method: 'POST', data } ),

	updateAnimal: ( id: number, data: AnimalInput ) =>
		apiFetch< Animal >( { path: `${ base }/${ id }`, method: 'PUT', data } ),

	trashAnimal: ( id: number ) =>
		apiFetch< { id: number; trashed: boolean } >( { path: `${ base }/${ id }`, method: 'DELETE' } ),

	listAdoptions: ( page: number, perPage: number, status = '' ) => {
		const params = new URLSearchParams( { page: String( page ), per_page: String( perPage ) } );

		if ( status !== '' ) {
			params.set( 'status', status );
		}

		return apiFetch< AdoptionList >( { path: `/${ config.restNamespace }/adoptions?${ params.toString() }` } );
	},

	getAdoption: ( id: number ) => apiFetch< Adoption >( { path: `/${ config.restNamespace }/adoptions/${ id }` } ),

	updateAdoption: ( id: number, data: { status?: string; notes?: string } ) =>
		apiFetch< Adoption >( { path: `/${ config.restNamespace }/adoptions/${ id }`, method: 'PUT', data } ),

	deleteAdoption: ( id: number ) =>
		apiFetch< { id: number; deleted: boolean } >( { path: `/${ config.restNamespace }/adoptions/${ id }`, method: 'DELETE' } ),

	getSettings: () => apiFetch< SettingsResponse >( { path: `/${ config.restNamespace }/settings` } ),

	saveSettings: ( data: OrganizationSettings ) =>
		apiFetch< SettingsResponse >( { path: `/${ config.restNamespace }/settings`, method: 'PUT', data } ),

	/** Reduz a foto no aparelho e envia para a biblioteca de mídia do WordPress. */
	uploadPhoto: async ( file: File, animalId?: number ): Promise< AnimalImage > => {
		const body = new FormData();
		const resized = await resizeImage( file );

		body.append( 'file', resized, resized.name );
		if ( animalId ) {
			body.append( 'post', String( animalId ) );
		}

		const media = await apiFetch< {
			id: number;
			source_url: string;
			media_details?: { sizes?: { medium?: { source_url: string } } };
		} >( { path: '/wp/v2/media', method: 'POST', body } );

		return { id: media.id, url: media.media_details?.sizes?.medium?.source_url ?? media.source_url };
	},
};

/** Mensagem legível de um erro da API. */
export function errorMessage( error: unknown ): string {
	if ( error && typeof error === 'object' && 'message' in error && typeof error.message === 'string' ) {
		return error.message;
	}

	return __( 'Algo deu errado. Confira sua conexão e tente de novo.', 'resgata-wp' );
}
