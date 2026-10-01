// プレビュー通信の検証。実HTTP・データベースには接続しない。
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const ts = require('typescript');
let handler;
const context = { exports: {}, AbortController, setTimeout, clearTimeout, fetch: (...args) => handler(...args) };
vm.runInNewContext(ts.transpileModule(fs.readFileSync(path.join(__dirname, '../admin/src/preview-api.ts'), 'utf8'), {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 },
}).outputText, context);
const config = { previewUrl: 'https://example.invalid/?rest_route=/rubymaco/v1/settings/preview&_locale=user', nonce: 'test' };
const settings = { apply_mode: 'all', enabled_rule_ids: [], bouten_style: 'sesame', bouten_rendering_method: 'custom' };
const rules = [{ id: 'ruby', examples: ['漢字《かんじ》'] }];
const previews = [{ id: 'ruby', examples: [{ text: rules[0].examples[0], html: rules[0].examples[0] }] }];
const run = signal => context.exports.fetchPreview(config, settings, rules, signal || new AbortController().signal);
(async () => {
  handler = async (url, options) => {
    assert.equal(url, config.previewUrl);
    assert.equal(options.method, 'POST');
    assert.equal(options.credentials, 'same-origin');
    assert.equal(options.headers['X-WP-Nonce'], 'test');
    assert.deepEqual(JSON.parse(options.body), settings);
    return { ok: true, json: async () => ({ previews }) };
  };
  assert.deepEqual(JSON.parse(JSON.stringify(await run())), previews);
  for (const body of [null, {}, { previews: [] }, { previews: [{ id: 'wrong', examples: [] }] },
    { previews: [{ id: 'ruby', examples: [{ text: 'wrong', html: '' }] }] },
    { previews: [{ id: 'ruby', examples: [{ text: rules[0].examples[0], html: 123 }] }] }]) {
    handler = async () => ({ ok: true, json: async () => body });
    await assert.rejects(run());
  }
  handler = async () => ({ ok: false });
  await assert.rejects(run());
  handler = async () => { throw new Error('offline'); };
  await assert.rejects(run());
  handler = async () => ({ ok: true, json: async () => { throw new Error('invalid JSON'); } });
  await assert.rejects(run());
  handler = (_, options) => new Promise((resolve, reject) => {
    options.signal.addEventListener('abort', () => reject(new Error('aborted')), { once: true });
  });
  const controller = new AbortController();
  const pending = run(controller.signal);
  controller.abort();
  await assert.rejects(pending);
  console.log('PASS: preview POST, no selection, response validation, errors, cancellation');
})().catch(error => { console.error(error); process.exitCode = 1; });
