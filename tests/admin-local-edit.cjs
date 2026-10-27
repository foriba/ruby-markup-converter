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
    // wp-api-fetch は本体と同じくルート URL・nonce・nonce 再取得先を設定し、_locale=user を自動で付ける.
    const settingsUrl = /^https:\/\/example\.invalid\/wp-json\/rubymaco\/v1\/settings\?_locale=user$/;
    const previewUrl = /^https:\/\/example\.invalid\/wp-json\/rubymaco\/v1\/settings\/preview\?_locale=user$/;
    let expireNonceOnce = false;
    let nonceRefreshes = 0;
    let savedWithNonce = null;
    await page.route(settingsUrl, route => {
      requests.push(route.request().method());
      const nonce = route.request().headers()['x-wp-nonce'];
      if (route.request().method() === 'POST') {
        // 開いたままの画面で nonce が期限切れになった場合を再現する.
        if (expireNonceOnce) {
          expireNonceOnce = false;
          return route.fulfill({ status: 403, json: { code: 'rest_cookie_invalid_nonce', message: 'Cookie check failed', data: { status: 403 } } });
        }
        savedWithNonce = nonce;
        data.settings = route.request().postDataJSON();
        return route.fulfill({ json: { settings: data.settings } });
      }
      assert.equal(nonce, 'test');
      return route.fulfill({ json: data });
    });
    await page.route('https://example.invalid/wp-admin/admin-ajax.php?action=rest-nonce', route => {
      nonceRefreshes++;
      return route.fulfill({ body: 'refreshed' });
    });
    await page.route(previewUrl, route => {
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
      await loadScript('wp-api-fetch');
      await page.evaluate(config => {
        // WordPress 本体が wp-api-fetch に追加するインラインスクリプトと同じ設定.
        wp.apiFetch.use(wp.apiFetch.createRootURLMiddleware('https://example.invalid/wp-json/'));
        wp.apiFetch.nonceMiddleware = wp.apiFetch.createNonceMiddleware('test');
        wp.apiFetch.use(wp.apiFetch.nonceMiddleware);
        wp.apiFetch.nonceEndpoint = 'https://example.invalid/wp-admin/admin-ajax.php?action=rest-nonce';
        window.rubymacoAdmin = config;
      }, { messages: data.messages });
      await page.addStyleTag({ path: path.join(wp, 'wp-admin/css/common.min.css') });
      await page.addStyleTag({ path: path.join(wp, 'wp-admin/css/forms.min.css') });
      await page.addStyleTag({ path: path.join(wp, 'wp-includes/css/dist/components/style.min.css') });
      await page.addStyleTag({ path: path.join(root, 'admin/build/index.css') });
      await page.addScriptTag({ path: path.join(root, 'admin/build/index.js') });
      await page.getByRole('tab', { name: data.messages.basic_tab, exact: true }).waitFor();
    };
    // 記法のチェックボックスだけを数える. 互換性タブのスイッチ (role=switch) は含めない.
    const checkedRules = () => page.getByRole('checkbox', { checked: true });
    const basicTab = () => page.getByRole('tab', { name: data.messages.basic_tab, exact: true });
    const compatibilityTab = () => page.getByRole('tab', { name: data.messages.compatibility_tab, exact: true });
    const initialSettings = structuredClone(data.settings);
    for (const width of [1280, 390]) {
      data.settings = structuredClone(initialSettings);
      await page.setViewportSize({ width, height: 900 });
      await mount();
      const checks = page.getByRole('checkbox', {name: /.+/});
      assert.equal(await checks.count(), data.definitions.markup_rules.length);
      for (const check of await checks.all()) await check.uncheck();
      assert.equal(await checkedRules().count(), 0);
      await page.getByRole('region', { name: data.messages.conversion_scope, exact: true }).getByRole('radio', {name: /.+/}).last().check();
      await page.locator('.rubymaco-bouten-style').getByRole('radio', {name: /.+/}).last().check();
      await page.getByRole('tab', { name: data.messages.compatibility_tab, exact: true }).click();
      await page.getByRole('switch').check();
      await basicTab().click();
      // パネルの切り替え完了 (互換性タブのスイッチが消える) を待ってから数える.
      await page.getByRole('switch').waitFor({ state: 'hidden' });
      assert.equal(await basicTab().getAttribute('aria-selected'), 'true');
      assert.equal(await checkedRules().count(), 0);
      assert.equal(await page.locator('.rubymaco-bouten-style').getByRole('radio', {name: /.+/}).last().isChecked(), true);
      // Tabs は手動アクティベーション: 矢印キーはフォーカス移動のみで、Enter で選択する.
      await basicTab().press('ArrowRight');
      assert.equal(await compatibilityTab().evaluate(tab => tab === document.activeElement), true);
      assert.equal(await basicTab().getAttribute('aria-selected'), 'true');
      await compatibilityTab().press('Enter');
      await page.getByRole('switch').waitFor();
      assert.equal(await page.getByRole('switch').isChecked(), true);
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
      await page.screenshot({ path: '/tmp/rubymaco-edit-' + width + '.png', fullPage: true });
      expireNonceOnce = true;
      await page.getByRole('button', { name: data.messages.save, exact: true }).click();
      // #a11y-speak-polite の読み上げ用テキストと重複しないよう、管理画面の範囲内に絞る.
      await page.locator('#rubymaco-admin-root').getByText(data.messages.saved, {exact: true}).waitFor();
      assert.equal(savedWithNonce, 'refreshed', 'expired nonce is refreshed and the save is retried');
      assert.deepEqual(data.settings.enabled_rule_ids, []);
      assert.equal(data.settings.bouten_rendering_method, 'custom');
      await mount();
      assert.equal(await checkedRules().count(), 0);
    }
    assert.deepEqual(errors, []);
    assert.equal(nonceRefreshes, 2);
    assert(requests.length > 0 && requests.every(method => method === 'GET' || method === 'PREVIEW' || method === 'POST'));
    console.log('PASS: desktop/mobile tabs, all unchecked, retained edits, save with nonce refresh, reload persistence (mock HTTP)');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
