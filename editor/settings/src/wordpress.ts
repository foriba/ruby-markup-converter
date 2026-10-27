import type { ComponentType, ReactNode } from 'react';

// WordPress 6.8 以降が提供する API の利用範囲。PHP 側で対応ハンドルを依存登録する。
interface EditorSelectors {
	getCurrentPostType: () => string;
	getEditedPostAttribute: (
		attribute: 'meta'
	) => Record< string, unknown > | undefined;
	isSavingPost: () => boolean;
}
interface EditorActions {
	editPost: ( edits: { meta: Record< string, unknown > } ) => void;
}
interface WordPress {
	data: {
		useSelect: < T >(
			map: ( select: ( store: 'core/editor' ) => EditorSelectors ) => T,
			deps: unknown[]
		) => T;
		useDispatch: ( store: 'core/editor' ) => EditorActions;
	};
	editor: {
		PluginDocumentSettingPanel: ComponentType< {
			name: string;
			title: string;
			children: ReactNode;
		} >;
	};
	plugins: {
		registerPlugin: (
			name: string,
			settings: { render: ComponentType; icon: null }
		) => void;
	};
}

export const wordpress = ( window as unknown as { wp: WordPress } ).wp;

declare global {
	interface Window {
		rubymacoPostSettings?: {
			metaKey: string;
			options: { value: string; label: string }[];
			label: string;
			help: string;
		};
	}
}
