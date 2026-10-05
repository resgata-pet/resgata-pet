/**
 * Dados que o PHP entrega à aplicação (ver Modules/Panel/Page.php).
 */
export type OptionMap = Record< string, string >;

export interface PanelConfig {
	basePath: string;
	restNamespace: string;
	siteName: string;
	user: { name: string; email: string; isAdmin: boolean; canManageSettings: boolean; canManageAdoptions: boolean };
	urls: { site: string; admin: string; logout: string; lostPassword: string };
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
	};
}

declare global {
	interface Window {
		rwpPainel: PanelConfig;
	}
}

export const config: PanelConfig = window.rwpPainel;
