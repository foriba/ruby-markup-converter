import { Button, Dashicon } from '@wordpress/components';
import { Notice } from '@wordpress/ui';

type Props = {
	status: 'idle' | 'saving' | 'saved' | 'failed';
	dirty: boolean;
	messages: Record< string, string >;
	onSave: () => void;
};

// 保存状態の通知と操作を一か所にまとめ、色だけに頼らず状態を伝える。
export default function SaveFeedback( {
	status,
	dirty,
	messages,
	onSave,
}: Props ) {
	const saving = status === 'saving';
	const saved = status === 'saved' && ! dirty;
	let message = messages.save_hint;
	let label = messages.save;
	let notice: 'neutral' | 'warning' | 'success' | 'error' = 'neutral';
	if ( saving ) {
		message = messages.saving;
		label = messages.saving;
	} else if ( status === 'failed' ) {
		message = messages.save_failed;
		notice = 'error';
	} else if ( dirty ) {
		message = messages.unsaved_changes;
		notice = 'warning';
	} else if ( saved ) {
		message = messages.saved;
		label = messages.saved_button;
		notice = 'success';
	}
	return (
		<footer className="rubymaco-actions">
			<Notice.Root
				className="rubymaco-save-feedback"
				intent={ notice }
				spokenMessage={ message }
			>
				<Notice.Description className="rubymaco-save-message">
					{ message }
				</Notice.Description>
				<Notice.Actions className="rubymaco-save-buttons">
					<Button
						type="button"
						variant="primary"
						isBusy={ saving }
						disabled={
							saving || ( ! dirty && status !== 'failed' )
						}
						icon={ saved ? <Dashicon icon="yes" /> : undefined }
						onClick={ onSave }
					>
						{ label }
					</Button>
				</Notice.Actions>
			</Notice.Root>
		</footer>
	);
}
