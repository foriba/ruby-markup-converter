// Browser smoke test with the real PHP page data and mocked read-only HTTP.
const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const wp = process.env.WP_ROOT;
if (!wp) throw new Error('Set WP_ROOT to a WordPress installation.');
const data = JSON.parse(execFileSync(process.env.PHP_BINARY || 'php', ['-r',
  'require "tests/bootstrap.php"; echo json_encode(\\Foriba\\RubyMarkupConverter\\Admin\\Admin_Page_Data::create_default()->get());'
], { cwd: root, env: process.env, encoding: 'utf8' }));
(async () => {
  const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
  try {
    const page = await browser.newPage();
    const errors = [];
    const requests = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('https://example.invalid/settings', route => {
      requests.push(route.request().method());
      return route.fulfill({ json: data });
    });
    await page.route('https://example.invalid/preview', route => {
      requests.push('PREVIEW');
      assert.equal(route.request().method(), 'POST');
      const previews = data.definitions.markup_rules.map(rule => ({
        id: rule.id, examples: rule.examples.map(text => ({
          text, html: text.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;'),
        })),
      }));
      return route.fulfill({ json: { previews } });
    });
    const mount = async () => {
      await page.goto('about:blank');
      await page.setContent('<div class="wrap" id="rubymaco-admin-root"></div>');
      await page.addScriptTag({ path: path.join(wp, 'wp-includes/js/dist/vendor/react.min.js') });
      await page.addScriptTag({ path: path.join(wp, 'wp-includes/js/dist/vendor/react-dom.min.js') });
      await page.addScriptTag({ path: path.join(wp, 'wp-includes/js/dist/vendor/react-jsx-runtime.min.js') });
      await page.evaluate(config => {
        window.wp = { element: { ...window.React, createRoot: window.ReactDOM.createRoot } };
        window.rubymacoAdmin = config;
      }, { url: 'https://example.invalid/settings', previewUrl: 'https://example.invalid/preview', nonce: 'test', messages: data.messages });
      await page.addStyleTag({ path: path.join(wp, 'wp-admin/css/common.min.css') });
      await page.addStyleTag({ path: path.join(wp, 'wp-admin/css/forms.min.css') });
      await page.addStyleTag({ path: path.join(root, 'admin/build/index.css') });
      await page.addScriptTag({ path: path.join(root, 'admin/build/index.js') });
      await page.getByRole('tab', { name: data.messages.basic_tab, exact: true }).waitFor();
    };
    for (const width of [1280, 390]) {
      await page.setViewportSize({ width, height: 900 });
      await mount();
      const checks = page.getByRole('checkbox');
      assert.equal(await checks.count(), data.definitions.markup_rules.length);
      for (const check of await checks.all()) await check.uncheck();
      assert.equal(await page.locator('input[type=checkbox]:checked').count(), 0);
      await page.locator('input[name=apply_mode]').last().check();
      await page.locator('input[name=bouten_style]').last().check();
      await page.getByRole('tab', { name: data.messages.compatibility_tab, exact: true }).click();
      await page.locator('input[name=bouten_rendering_method]').last().check();
      await page.getByRole('tab', { name: data.messages.basic_tab, exact: true }).click();
      assert.equal(await page.locator('input[type=checkbox]:checked').count(), 0);
      assert.equal(await page.locator('input[name=bouten_style]').last().isChecked(), true);
      await page.getByRole('tab', { name: data.messages.basic_tab, exact: true }).press('ArrowRight');
      assert.equal(await page.locator('input[name=bouten_rendering_method]').last().isChecked(), true);
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
      await page.screenshot({ path: '/tmp/rubymaco-edit-' + width + '.png', fullPage: true });
      await mount();
      assert.equal(await page.locator('input[type=checkbox]:checked').count(), data.settings.enabled_rule_ids.length);
    }
    assert.deepEqual(errors, []);
    assert(requests.length > 0 && requests.every(method => method === 'GET' || method === 'PREVIEW'));
    console.log('PASS: desktop/mobile tabs, all unchecked, retained edits, reload reset, no save');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
