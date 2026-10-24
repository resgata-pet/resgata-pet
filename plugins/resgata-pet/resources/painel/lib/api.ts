/**
 * Acesso à API do painel (`resgata-pet/v1`). O nonce é enviado pelo
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
	weight: number | null;
	tagline: string;
	intro: string;
	age_years: number | null;
	age_months: number | null;
	rescue_date: string;
	temperaments: string[];
	neutered: string;
	dewormed_date: string;
	fiv: string;
	felv: string;
	vet_checked: string;
	vaccinated: string;
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
	sponsorship_email: string;
	/** ID da página de agradecimento do apadrinhamento (texto; vazio se não há). */
	sponsorship_thanks_page: string;
	sponsorship_thanks_title: string;
	sponsorship_thanks_text: string;
	privacy_contact: string;
	privacy_text: string;
	pix_key_type: string;
	pix_key: string;
	pix_holder: string;
}

/** Módulo do kit, como a tela "Recursos do site" mostra. */
export interface SiteModule {
	slug: string;
	label: string;
	description: string;
	/** Falso quando ainda não existe ("em breve") ou falta algo no servidor. */
	available: boolean;
	/** Módulo de que este depende, ou vazio. */
	requires: string;
	enabled: boolean;
}

export interface ModulesResponse {
	modules: SiteModule[];
}

export interface SettingsResponse {
	settings: OrganizationSettings;
	choices: { states: Record< string, string >; pixKeyTypes: Record< string, string >; pages: Record< string, string > };
	contact_page: { id: number; url: string; published: boolean } | null;
	/** Página "Como ajudar" (nasce quando a chave Pix é cadastrada). */
	help_page: { id: number; url: string; published: boolean } | null;
	/** Página para onde o padrinho volta depois de pagar (nasce com o primeiro plano à venda). */
	thanks_page: { id: number; url: string; published: boolean } | null;
	whatsapp_url: string;
}

/** Números da tela Início. Cada parte é `null` para quem não tem acesso àquela área. */
export interface DashboardSummary {
	animals: { total: number; drafts: number; by_status: Record< string, number > } | null;
	adoptions: { new: number } | null;
	sponsorship: { active: number; total: number } | null;
}

/** Banner da página inicial do site. */
export interface Banner {
	image_id: number;
	image: AnimalImage | null;
	active: boolean;
	title: string;
	highlight: string;
	text: string;
	button_label: string;
	button_url: string;
	button2_label: string;
	button2_url: string;
}

export interface BannersResponse {
	banners: Banner[];
	max: number;
}

/** Plano de apadrinhamento: um produto da Stripe com o seu preço mensal em vigor. */
export interface SponsorshipPlan {
	id: string;
	price_id: string;
	/** Valor mensal em centavos. */
	amount: number;
	/** Valor formatado, ex.: "R$ 15". */
	label: string;
	name: string;
	description: string;
	benefits: string[];
	featured: boolean;
	/** Se aparece no site. */
	active: boolean;
	can_reprice: boolean;
	subscribers: number;
}

export type PlanInput = Partial< Pick< SponsorshipPlan, 'name' | 'amount' | 'description' | 'benefits' | 'featured' | 'active' > >;

/** O que a chave em uso consegue fazer na Stripe; `checked` é falso enquanto não há chave. */
export interface StripePermissions {
	checked: boolean;
	permissions: Record< string, boolean >;
}

export type StripeKeySource = '' | 'constant' | 'panel';

/** Chaves a gravar; texto vazio apaga a que estava salva. */
export interface StripeKeysInput {
	secret_key?: string;
	webhook_secret?: string;
}

/** Resumo do apadrinhamento: estado da Stripe, planos e contador público. */
export interface SponsorshipOverview {
	/** As chaves nunca chegam ao navegador: só se estão definidas. */
	stripe: {
		secret_key: boolean;
		webhook_secret: boolean;
		mode: '' | 'test' | 'live';
		/** De onde vem cada chave: do wp-config.php, do painel ou de lugar nenhum. */
		sources: { secret_key: StripeKeySource; webhook_secret: StripeKeySource };
		/** Começo e fim da chave em uso, ex.: "rk_live_…Ab12". */
		hint: string;
		/** Chaves salvas que não puderam ser lidas e precisam ser informadas de novo. */
		unreadable: string[];
		/** Se o site consegue guardar as chaves cifradas. */
		can_store: boolean;
	};
	webhook_url: string;
	/** Arrecadação dos padrinhos, já sem estornos e contestações. Valores prontos para mostrar ("R$ 150"). */
	revenue: {
		month: string;
		last_month: string;
		total: string;
		/** Pagamentos contestados no banco que ainda esperam resposta. */
		open_disputes: number;
		/** Início do registro dos pagamentos, quando o site já tinha padrinhos antes (senão vazio). */
		since: string;
	};
	plans: SponsorshipPlan[];
	limits: { plans: number; amount_min: number; amount_max: number; name: number; description: number; benefit: number; benefits: number };
	/** Aviso da última gravação (ex.: plano salvo, mas a área do padrinho não foi atualizada). */
	warning: string;
	counter: { active: number; adjustment: number; total: number; updated_at: string };
}

export interface Sponsor {
	id: number;
	name: string;
	email: string;
	status: string;
	/** Nome do plano (vazio em planos sem nome). */
	plan: string;
	/** Valor que a pessoa paga por mês, ex.: "R$ 15". */
	plan_amount: string;
	payment_failed: boolean;
	last_paid_at: string;
	/** Total doado, ex.: "R$ 150" (vazio se ainda não há pagamento registrado). */
	total_paid: string;
	/** Data até quando segue ativo, se pediu o cancelamento (senão vazio). */
	cancel_at: string;
	created_at: string;
}

export type PaymentStatus = 'paid' | 'partially_refunded' | 'refunded' | 'disputed' | 'dispute_lost' | 'dispute_won';

export interface Payment {
	id: number;
	/** Nome do padrinho. */
	sponsor: string;
	/** Valor pago, ex.: "R$ 30". */
	amount: string;
	/** Valor devolvido ao padrinho (vazio se nada). */
	refunded: string;
	status: PaymentStatus;
	paid_at: string;
}

export interface PaymentList {
	items: Payment[];
	total: number;
	total_pages: number;
}

export interface SponsorList {
	items: Sponsor[];
	total: number;
	total_pages: number;
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

	getModules: () => apiFetch< ModulesResponse >( { path: `/${ config.restNamespace }/modules` } ),

	saveModules: ( modules: string[] ) =>
		apiFetch< ModulesResponse >( { path: `/${ config.restNamespace }/modules`, method: 'PUT', data: { modules } } ),

	getDashboard: () => apiFetch< DashboardSummary >( { path: `/${ config.restNamespace }/dashboard` } ),

	getBanners: () => apiFetch< BannersResponse >( { path: `/${ config.restNamespace }/banners` } ),

	saveBanners: ( banners: Omit< Banner, 'image' >[] ) =>
		apiFetch< BannersResponse >( { path: `/${ config.restNamespace }/banners`, method: 'PUT', data: { banners } } ),

	createPlan: ( data: PlanInput ) =>
		apiFetch< SponsorshipOverview >( { path: `/${ config.restNamespace }/sponsorship/plans`, method: 'POST', data } ),

	updatePlan: ( id: string, data: PlanInput ) =>
		apiFetch< SponsorshipOverview >( { path: `/${ config.restNamespace }/sponsorship/plans/${ encodeURIComponent( id ) }`, method: 'PUT', data } ),

	deletePlan: ( id: string ) =>
		apiFetch< SponsorshipOverview >( { path: `/${ config.restNamespace }/sponsorship/plans/${ encodeURIComponent( id ) }`, method: 'DELETE' } ),

	getSponsorship: () => apiFetch< SponsorshipOverview >( { path: `/${ config.restNamespace }/sponsorship` } ),

	saveSponsorship: ( data: { adjustment: number } ) =>
		apiFetch< SponsorshipOverview >( { path: `/${ config.restNamespace }/sponsorship`, method: 'PUT', data } ),

	getStripePermissions: () => apiFetch< StripePermissions >( { path: `/${ config.restNamespace }/sponsorship/permissions` } ),

	saveStripeKeys: ( data: StripeKeysInput ) =>
		apiFetch< SponsorshipOverview >( { path: `/${ config.restNamespace }/sponsorship/keys`, method: 'PUT', data } ),

	listSponsors: ( page: number, perPage: number, status = '' ) => {
		const params = new URLSearchParams( { page: String( page ), per_page: String( perPage ) } );

		if ( status !== '' ) {
			params.set( 'status', status );
		}

		return apiFetch< SponsorList >( { path: `/${ config.restNamespace }/sponsors?${ params.toString() }` } );
	},

	listPayments: ( page: number, perPage: number ) => {
		const params = new URLSearchParams( { page: String( page ), per_page: String( perPage ) } );

		return apiFetch< PaymentList >( { path: `/${ config.restNamespace }/payments?${ params.toString() }` } );
	},

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

	return __( 'Algo deu errado. Confira sua conexão e tente de novo.', 'resgata-pet' );
}
