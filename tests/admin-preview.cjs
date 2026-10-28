// プレビュー通信の検証。実HTTP・データベースには接続しない。
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const ts = require('typescript');
let handler;
const context = {
  exports: {}, AbortController, setTimeout, clearTimeout,
  require(name) {
    if (name === '@wordpress/api-fetch') return { __esModule: true, default: (...args) => handler(...args) };
    throw new Error('Unexpected import: ' + name);
  },
};
vm.runInNewContext(ts.transpileModule(fs.readFileSync(path.join(__dirname, '../admin/src/preview-api.ts'), 'utf8'), {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 },
}).outputText, context);
const settings = { apply_mode: 'all', enabled_rule_ids: [], bouten_style: 'sesame', bouten_rendering_method: 'custom' };
const rules = [{ id: 'ruby', examples: ['漢字《かんじ》'] }];
const previews = [{ id: 'ruby', examples: [{ text: rules[0].examples[0], html: rules[0].examples[0] }] }];
const run = signal => context.exports.fetchPreview(settings, rules, signal || new AbortController().signal);
(async () => {
  handler = async options => {
    // URL・nonce・_locale は apiFetch の標準ミドルウェアに任せ、ここでは指定しない.
    assert.deepEqual(Object.keys(options).sort(), ['data', 'method', 'path', 'signal']);
    assert.equal(options.path, '/rubymaco/v1/settings/preview');
    assert.equal(options.method, 'POST');
    assert.deepEqual(JSON.parse(JSON.stringify(options.data)), settings);
    return { previews };
  };
  assert.deepEqual(JSON.parse(JSON.stringify(await run())), previews);
  for (const body of [null, {}, { previews: [] }, { previews: [{ id: 'wrong', examples: [] }] },
    { previews: [{ id: 'ruby', examples: [{ text: 'wrong', html: '' }] }] },
    { previews: [{ id: 'ruby', examples: [{ text: rules[0].examples[0], html: 123 }] }] }]) {
    handler = async () => body;
    await assert.rejects(run());
  }
  // apiFetch は HTTP エラー・通信失敗・不正な JSON を、エラー内容を持つオブジェクトで reject する.
  for (const code of ['rubymaco_invalid_settings', 'fetch_error', 'invalid_json']) {
    handler = async () => { throw { code, message: code }; };
    await assert.rejects(run());
  }
  handler = options => new Promise((resolve, reject) => {
    options.signal.addEventListener('abort', () => reject(new Error('aborted')), { once: true });
  });
  const controller = new AbortController();
  const pending = run(controller.signal);
  controller.abort();
  await assert.rejects(pending);
  console.log('PASS: apiFetch preview POST, no selection, response validation, errors, cancellation');
})().catch(error => { console.error(error); process.exitCode = 1; });
