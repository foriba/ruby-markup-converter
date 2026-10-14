export interface Choice {
	value: string;
	label: string;
	description: string;
}

export interface Settings {
	apply_mode: string;
	enabled_rule_ids: string[];
	bouten_style: string;
	bouten_rendering_method: string;
}

export interface PageData {
	settings: Settings;
	definitions: {
		apply_modes: Choice[];
		bouten_styles: Choice[];
		bouten_rendering_methods: Choice[];
		markup_rules: {
			id: string;
			type: string;
			titles: string[];
			description: string;
			examples: string[];
		}[];
	};
	links: { user_guide: string };
	messages: Record< string, string >;
}

export interface AdminConfig {
	url: string;
	previewUrl: string;
	nonce: string;
	messages: Record< string, string >;
}

export interface RulePreview {
	id: string;
	examples: { text: string; html: string }[];
}

declare global {
	interface Window {
		rubymacoAdmin?: AdminConfig;
	}
}
