import { TabPanel } from '@wordpress/components';
import type { ReactNode } from 'react';

/**
 * 標準コンポーネントにタブの表示・選択・キーボード操作を委ねる。
 *
 * @param props          コンポーネントのプロパティ。
 * @param props.messages 画面文言。
 * @param props.children 選択中のタブに対応する内容。
 */
export default function SettingsTabs( {
	messages,
	children,
}: {
	messages: Record< string, string >;
	children: ( tab: string ) => ReactNode;
} ) {
	return (
		<TabPanel
			className="rubymaco-tabs"
			initialTabName="basic"
			tabs={ [
				{ name: 'basic', title: messages.basic_tab },
				{ name: 'compatibility', title: messages.compatibility_tab },
			] }
		>
			{ ( tab ) => children( tab.name ) }
		</TabPanel>
	);
}
