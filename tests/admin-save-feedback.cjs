// 表示コンポーネントを軽量なJSX代替で評価する。実HTTP・DBは使用しない。
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const ts = require('typescript');
const jsx = (type, props) => ({type, props});
const context = {exports: {}, require(name) {
  if (name === 'react/jsx-runtime') return {jsx, jsxs: jsx};
  if (name === '@wordpress/components') return {Button: 'Button', Dashicon: 'Dashicon'};
  if (name === '@wordpress/ui') return {Notice: {Root: 'Notice.Root', Description: 'Notice.Description', Actions: 'Notice.Actions'}};
  throw new Error(name);
}};
vm.runInNewContext(ts.transpileModule(fs.readFileSync(path.join(__dirname, '../admin/src/save-feedback.tsx'), 'utf8'), {
  compilerOptions: {module: ts.ModuleKind.CommonJS, jsx: ts.JsxEmit.ReactJSX},
}).outputText, context);
const messages = Object.fromEntries(['save_hint', 'save', 'saving', 'save_failed', 'unsaved_changes', 'saved', 'saved_button'].map(key => [key, key]));
const cases = [
  ['idle', false, 'save_hint', 'save', true, 'neutral'],
  ['idle', true, 'unsaved_changes', 'save', false, 'warning'],
  ['saving', true, 'saving', 'saving', true, 'neutral'],
  ['saved', false, 'saved', 'saved_button', true, 'success'],
  ['saved', true, 'unsaved_changes', 'save', false, 'warning'],
  ['failed', true, 'save_failed', 'save', false, 'error'],
  ['failed', false, 'save_failed', 'save', false, 'error'],
];
for (const [status, dirty, message, label, disabled, notice] of cases) {
  const tree = context.exports.default({status, dirty, messages, onSave() {}});
  const feedback = tree.props.children;
  const [description, actions] = feedback.props.children;
  const button = actions.props.children;
  assert.equal(feedback.type, 'Notice.Root');
  assert.equal(feedback.props.spokenMessage, message);
  assert.equal(description.props.children, message);
  assert.equal(feedback.props.intent, notice);
  assert.equal(actions.type, 'Notice.Actions');
  assert.equal(button.props.children, label);
  assert.equal(button.props.disabled, disabled);
  assert.equal(button.props.isBusy, status === 'saving');
  assert.equal(Boolean(button.props.icon), status === 'saved' && !dirty);
}
console.log('PASS: idle, dirty, saving, success, failure, edited-after-success feedback');
