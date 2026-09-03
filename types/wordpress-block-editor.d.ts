declare module '@wordpress/block-editor' {
	export const InnerBlocks: React.ComponentType & {
		Content: React.ComponentType;
	};

	export function useBlockProps(): React.HTMLAttributes< HTMLElement >;

	namespace useBlockProps {
		function save(): React.HTMLAttributes< HTMLElement >;
	}
}