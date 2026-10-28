// フックの状態・依存配列を模擬し、先読みとキャッシュ切り替えを検証する。
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const ts = require('typescript');
const states = [];
let cursor = 0;
let dependencies;
let cleanup;
let requests = 0;
let failCustomDot = false;
let pause = false;
const pending = [];
const signals = [];
const context = {
  exports: {}, AbortController,
  require(name) {
    if (name === './preview-api') return { fetchPreview: async (settings, _rules, signal) => {
      requests++;
      signals.push(signal);
      assert.equal(settings.apply_mode, 'shortcode');
      const fail = failCustomDot && settings.bouten_style === 'dot' && settings.bouten_rendering_method === 'custom';
      if (pause) await new Promise(resolve => pending.push(resolve));
      if (fail) throw new Error('offline');
      return [
        {id: 'ruby', examples: [{text: '例', html: '<ruby>例</ruby>'}]},
        {id: 'bouten', examples: [{text: '例', html: settings.bouten_style + ':' + settings.bouten_rendering_method}]},
      ];
    } };
    if (name === '@wordpress/element') return {
      useState(initial) {
        const index = cursor++;
        if (!(index in states)) states[index] = initial;
        return [states[index], value => { states[index] = typeof value === 'function' ? value(states[index]) : value; }];
      },
      useEffect(effect, next) {
        if (!dependencies || next.some((value, index) => !Object.is(value, dependencies[index]))) {
          cleanup?.();
          dependencies = next;
          cleanup = effect();
        }
      },
    };
    throw new Error('Unexpected import: ' + name);
  },
};
vm.runInNewContext(ts.transpileModule(fs.readFileSync(path.join(__dirname, '../admin/src/use-settings-preview.ts'), 'utf8'), {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 },
}).outputText, context);
const definitions = {
  markup_rules: [{ id: 'ruby', type: 'ruby' }, {id: 'bouten', type: 'bouten'}],
  bouten_styles: [{value: 'dot'}, {value: 'sesame'}],
  bouten_rendering_methods: [{value: 'text_emphasis'}, {value: 'custom'}],
};
let settings = { apply_mode: 'shortcode', enabled_rule_ids: ['ruby'], bouten_style: 'sesame', bouten_rendering_method: 'text_emphasis' };
const render = () => { cursor = 0; return context.exports.default(settings, definitions); };
const flush = () => new Promise(resolve => setImmediate(resolve));
(async () => {
  assert.equal(render().loading, true);
  assert.equal(requests, 4);
  await flush();
  const initial = render();
  assert.equal(initial.loading, false);
  for (const enabled_rule_ids of [[], ['ruby'], []]) {
    settings = { ...settings, enabled_rule_ids };
    const result = render();
    assert.equal(result.loading, false);
    assert.equal(result.previews, initial.previews);
  }
  settings = { ...settings, apply_mode: 'all' };
  assert.equal(render().previews, initial.previews);
  for (const method of ['custom', 'text_emphasis']) {
    for (const style of ['dot', 'sesame', 'dot']) {
      settings = { ...settings, bouten_style: style, bouten_rendering_method: method };
      const result = render();
      assert.equal(result.loading, false);
      assert.equal(result.failed, false);
      assert.equal(result.previews[0], initial.previews[0]);
      assert.equal(result.previews[1].examples[0].html, style + ':' + method);
    }
  }
  assert.equal(requests, 4, 'selection changes must not fetch');
  // 一つの組み合わせが失敗しても、取得済みのルビ・他の組み合わせを表示できる。
  failCustomDot = true;
  render().retry();
  render();
  await flush();
  settings = {...settings, bouten_style: 'dot', bouten_rendering_method: 'custom'};
  assert.equal(render().failed, true);
  assert.equal(render().previews[0].id, 'ruby');
  settings = {...settings, bouten_style: 'sesame'};
  assert.equal(render().failed, false);
  failCustomDot = false;
  render().retry();
  render();
  await flush();
  settings = {...settings, bouten_style: 'dot'};
  assert.equal(render().failed, false);
  assert.equal(requests, 12);
  // 中止された古い応答が、新しい取得結果を上書きしない。
  pause = true;
  render().retry();
  render();
  const oldSignals = signals.slice(-4);
  pause = false;
  render().retry();
  render();
  await flush();
  assert(oldSignals.every(signal => signal.aborted));
  const latest = render().previews;
  pending.forEach(resolve => resolve());
  await flush();
  assert.equal(render().previews, latest);
  cleanup();
  console.log('PASS: four variants prefetched; instant switches; stable ruby; failure/retry; stale response ignored');
})().catch(error => { console.error(error); process.exitCode = 1; });
