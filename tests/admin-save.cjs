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
  fetch: (...args) => handler(...args),
};
vm.runInNewContext(ts.transpileModule(source, {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 },
}).outputText, context);
const config = { url: 'https://example.invalid/settings?_locale=user', nonce: 'test' };
const settings = { apply_mode: 'all', enabled_rule_ids: [], bouten_style: 'sesame', bouten_rendering_method: 'custom' };
const definitions = {
  apply_modes: [{ value: 'all' }],
  bouten_styles: [{ value: 'sesame' }],
  bouten_rendering_methods: [{ value: 'custom' }],
  markup_rules: [{ id: 'ruby' }],
};
(async () => {
  handler = async (url, options) => {
    assert.equal(url, config.url);
    assert.equal(options.method, 'POST');
    assert.equal(options.credentials, 'same-origin');
    assert.equal(options.headers['X-WP-Nonce'], 'test');
    assert.equal(options.headers['Content-Type'], 'application/json');
    assert.deepEqual(JSON.parse(options.body), settings);
    return { ok: true, json: async () => ({ settings }) };
  };
  assert.deepEqual(await context.exports.saveSettings(config, settings, definitions), settings);
  for (const status of [400, 401, 403, 500]) {
    handler = async () => ({ ok: false, status });
    await assert.rejects(context.exports.saveSettings(config, settings, definitions));
  }
  for (const body of [null, {}, { settings: {} }, { settings: { ...settings, enabled_rule_ids: {} } },
    { settings: { ...settings, enabled_rule_ids: ['unknown'] } }, { settings: { ...settings, apply_mode: 'invalid' } }]) {
    handler = async () => ({ ok: true, json: async () => body });
    await assert.rejects(context.exports.saveSettings(config, settings, definitions));
  }
  handler = async () => { throw new Error('offline'); };
  await assert.rejects(context.exports.saveSettings(config, settings, definitions));
  handler = async () => ({ ok: true, json: async () => { throw new Error('not JSON'); } });
  await assert.rejects(context.exports.saveSettings(config, settings, definitions));
  assert.deepEqual(settings.enabled_rule_ids, []);
  console.log('PASS: POST contract, empty selection, HTTP/network/JSON/response failures');
})().catch(error => { console.error(error); process.exitCode = 1; });
