import { Tabs } from '@wordpress/ui';

// Tabs の書き方を確認するための参考例。本画面では使用しない。
export const TabTest = () => {
	return (
		<Tabs.Root
			onValueChange={ ( tab ) =>
				// 参考例では、タブ変更通知をコンソールで確認する。
				// eslint-disable-next-line no-console
				console.log( 'New selected tab: ', tab )
			}
			defaultValue="tab2"
		>
			<Tabs.List>
				<Tabs.Tab value="tab1">Tab 1</Tabs.Tab>
				<Tabs.Tab value="tab2">Tab 2</Tabs.Tab>
				<Tabs.Tab value="tab3">Tab 3</Tabs.Tab>
			</Tabs.List>
			<Tabs.Panel value="tab1">
				<p>Selected tab: Tab 1</p>
			</Tabs.Panel>
			<Tabs.Panel value="tab2">
				<p>Selected tab: Tab 2</p>
			</Tabs.Panel>
			<Tabs.Panel value="tab3">
				<p>Selected tab: Tab 3</p>
			</Tabs.Panel>
		</Tabs.Root>
	);
};
