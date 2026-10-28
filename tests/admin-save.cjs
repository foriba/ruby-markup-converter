// Unit checks for the TypeScript transport; no real HTTP or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const ts = require('typescript');
const source = fs.readFileSync(path.join(__dirname, '../admin/src/settings-api.ts'), 'utf8');
let handler;
const context = {
  exports: {}, AbortController, setTimeout, clearTimeout,
  require(name) {
    if (name === '@wordpress/api-fetch') return { __esModule: true, default: (...args) => handler(...args) };
    throw new Error('Unexpected import: ' + name);
  },
};
vm.runInNewContext(ts.transpileModule(source, {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 },
}).outputText, context);
const settings = { apply_mode: 'all', enabled_rule_ids: [], bouten_style: 'sesame', bouten_rendering_method: 'custom' };
const definitions = {
  apply_modes: [{ value: 'all' }],
  bouten_styles: [{ value: 'sesame' }],
  bouten_rendering_methods: [{ value: 'custom' }],
  markup_rules: [{ id: 'ruby' }],
};
(async () => {
  handler = async options => {
    // URL・nonce・_locale は apiFetch の標準ミドルウェアに任せ、ここでは指定しない.
    assert.deepEqual(Object.keys(options).sort(), ['data', 'method', 'path', 'signal']);
    assert.equal(options.path, '/rubymaco/v1/settings');
    assert.equal(options.method, 'POST');
    assert.deepEqual(JSON.parse(JSON.stringify(options.data)), settings);
    assert.equal(options.signal.aborted, false);
    return { settings };
  };
  assert.deepEqual(await context.exports.saveSettings(settings, definitions), settings);
  // apiFetch は HTTP エラー・通信失敗・不正な JSON を、エラー内容を持つオブジェクトで reject する.
  for (const code of ['rubymaco_invalid_settings', 'rest_forbidden', 'rubymaco_save_failed', 'fetch_error', 'invalid_json']) {
    handler = async () => { throw { code, message: code }; };
    await assert.rejects(context.exports.saveSettings(settings, definitions));
  }
  for (const body of [null, {}, { settings: {} }, { settings: { ...settings, enabled_rule_ids: {} } },
    { settings: { ...settings, enabled_rule_ids: ['unknown'] } }, { settings: { ...settings, apply_mode: 'invalid' } }]) {
    handler = async () => body;
    await assert.rejects(context.exports.saveSettings(settings, definitions));
  }
  assert.deepEqual(settings.enabled_rule_ids, []);
  console.log('PASS: apiFetch POST contract, empty selection, request/response failures');
})().catch(error => { console.error(error); process.exitCode = 1; });
