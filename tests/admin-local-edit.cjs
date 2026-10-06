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
const packages = JSON.parse(execFileSync(process.env.PHP_BINARY || 'php', ['-r',
  'echo json_encode(require getenv("WP_ROOT") . "/wp-includes/assets/script-loader-packages.php");'
], { env: process.env, encoding: 'utf8' }));
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
      const loaded = new Set();
      const loadScript = async handle => {
        if (loaded.has(handle)) return;
        loaded.add(handle);
        const isPackage = handle.startsWith('wp-') && packages[handle.slice(3) + '.js'];
        for (const dependency of isPackage ? packages[handle.slice(3) + '.js'].dependencies : []) {
          await loadScript(dependency);
        }
        const file = isPackage ? handle.slice(3) + '.min.js' : 'vendor/' + handle + '.min.js';
        await page.addScriptTag({ path: path.join(wp, 'wp-includes/js/dist', file) });
      };
      await loadScript('wp-components');
      await page.evaluate(config => {
        window.rubymacoAdmin = config;
      }, { url: 'https://example.invalid/settings', previewUrl: 'https://example.invalid/preview', nonce: 'test', messages: data.messages });
      await page.addStyleTag({ path: path.join(wp, 'wp-admin/css/common.min.css') });
      await page.addStyleTag({ path: path.join(wp, 'wp-admin/css/forms.min.css') });
      await page.addStyleTag({ path: path.join(wp, 'wp-includes/css/dist/components/style.min.css') });
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
      await page.locator('.rubymaco-field').filter({ hasText: data.messages.conversion_scope }).getByRole('radio').last().check();
      await page.locator('.rubymaco-field').filter({ hasText: data.messages.bouten_style }).getByRole('radio').last().check();
      await page.getByRole('tab', { name: data.messages.compatibility_tab, exact: true }).click();
      await page.locator('.rubymaco-field').filter({ hasText: data.messages.bouten_rendering_method }).getByRole('radio').last().check();
      await page.getByRole('tab', { name: data.messages.basic_tab, exact: true }).click();
      assert.equal(await page.locator('input[type=checkbox]:checked').count(), 0);
      assert.equal(await page.locator('.rubymaco-field').filter({ hasText: data.messages.bouten_style }).getByRole('radio').last().isChecked(), true);
      await page.getByRole('tab', { name: data.messages.basic_tab, exact: true }).press('ArrowRight');
      assert.equal(await page.locator('.rubymaco-field').filter({ hasText: data.messages.bouten_rendering_method }).getByRole('radio').last().isChecked(), true);
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
