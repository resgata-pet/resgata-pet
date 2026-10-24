/**
 * Dados que o PHP entrega à aplicação (ver Modules/Panel/Page.php).
 */
export type OptionMap = Record< string, string >;

export interface PanelConfig {
	basePath: string;
	restNamespace: string;
	siteName: string;
	user: {
		name: string;
		email: string;
		isAdmin: boolean;
		canManageSettings: boolean;
		canManageAdoptions: boolean;
		canManageSponsorship: boolean;
	};
	urls: { site: string; admin: string; logout: string; lostPassword: string };
	/** Módulos ligados e em funcionamento (`animals`, `adoptions`, `sponsorship`). */
	modules: string[];
	options: {
		statuses: OptionMap;
		sexes: OptionMap;
		sizes: OptionMap;
		yesNo: OptionMap;
		tests: OptionMap;
		photoTypes: OptionMap;
		species: OptionMap;
		temperaments: string[];
		adoptionStatuses: OptionMap;
		sponsorStatuses: OptionMap;
	};
}

declare global {
	interface Window {
		resgataPainel: PanelConfig;
	}
}

export const config: PanelConfig = window.resgataPainel;

/** Informa se um módulo está ligado (o menu e as telas acompanham). */
export const hasModule = ( slug: string ): boolean => config.modules.includes( slug );
