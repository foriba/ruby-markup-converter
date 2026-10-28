// 投稿メタの編集はモックし、実サイトへの書き込みなしで UI の契約を検証する。
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const ts = require('typescript');
let render;
let postType = 'post';
let meta = { _rubymaco_conversion_scope: 'inherit', other: 'keep' };
let saving = false;
const edits = [];
const wordpress = {
	data: {
		useSelect: (callback) => callback(() => ({
			getCurrentPostType: () => postType,
			getEditedPostAttribute: () => meta,
			isSavingPost: () => saving,
		})),
		useDispatch: () => ({ editPost: (edit) => edits.push(edit) }),
	},
	editor: { PluginDocumentSettingPanel: 'panel' },
	plugins: { registerPlugin: (name, settings) => {
		assert.equal(name, 'rubymaco-post-settings');
		render = settings.render;
	} },
};
const source = fs.readFileSync(path.join(__dirname, '../editor/settings/src/index.tsx'), 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: {
	module: ts.ModuleKind.CommonJS, jsx: ts.JsxEmit.ReactJSX,
} }).outputText;
vm.runInNewContext(compiled, {
	exports: {},
	window: { rubymacoPostSettings: {
		metaKey: '_rubymaco_conversion_scope',
		options: ['inherit', 'full_content', 'none'].map(value => ({ value, label: value })),
		label: 'conversion', help: 'help',
	} },
	require: (name) => {
		if (name === './wordpress') return { wordpress };
		if (name === '@wordpress/components') return { RadioControl: 'radio' };
		return require(name);
	},
});
for (const type of ['post', 'page']) {
	postType = type;
	for (const value of ['inherit', 'full_content', 'none']) {
		meta = { _rubymaco_conversion_scope: value, other: 'keep' };
		const control = render().props.children.props;
		assert.equal(control.selected, value);
		control.onChange('none');
		assert.equal(JSON.stringify(edits.at(-1)), JSON.stringify({ meta: { _rubymaco_conversion_scope: 'none' } }));
		control.onChange('full_content');
		assert.equal(JSON.stringify(edits.at(-1)), JSON.stringify({ meta: { _rubymaco_conversion_scope: 'full_content' } }));
		// 継承に戻すときは null を送り、保存時に REST API がメタを削除する.
		control.onChange('inherit');
		assert.equal(JSON.stringify(edits.at(-1)), JSON.stringify({ meta: { _rubymaco_conversion_scope: null } }));
		assert.equal(meta.other, 'keep');
	}
}
const before = edits.length;
saving = true;
assert.equal(render().props.children.props.disabled, true);
render().props.children.props.onChange('full_content');
assert.equal(edits.length, before);
saving = false;
render().props.children.props.onChange('invalid');
assert.equal(edits.length, before);
meta = { _rubymaco_conversion_scope: 'invalid' };
assert.equal(render().props.children.props.selected, 'inherit');
for (const missing of [null, undefined, '']) {
	meta = { _rubymaco_conversion_scope: missing };
	assert.equal(render().props.children.props.selected, 'inherit', 'deleted or missing meta inherits');
}
meta = {};
assert.equal(render().props.children.props.selected, 'inherit', 'unregistered meta key inherits');
postType = 'attachment';
assert.equal(render(), null);
postType = 'post';
meta = undefined;
assert.equal(render(), null);
console.log('PASS: post/page, three states, inherit deletes meta, isolated meta edits, saving lock, invalid input and unsupported contexts');
