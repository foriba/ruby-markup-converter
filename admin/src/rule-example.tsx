import { memo } from '@wordpress/element';

type Props = {
	html?: string;
	messages: Record< string, string >;
	failed: boolean;
};
// HTMLは認証済みPHP APIが固定例文を変換した結果だけを使用する。
function RuleExample( { html, messages, failed }: Props ) {
	if ( html === undefined ) {
		return (
			<span>
				{ failed ? messages.preview_failed : messages.preview_loading }
			</span>
		);
	}
	return <span dangerouslySetInnerHTML={ { __html: html } } />;
}

// チェック操作で親が更新されても、同じ変換結果を描き直さない。
export default memo( RuleExample );
