import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const url = base + '/tools/psychology/mood';
const results = [];
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);

const browser = await chromium.launch();

// ---- Functional flow at the reference viewport ----
{
  const ctx = await browser.newContext({ viewport: { width: 1536, height: 1024 } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const page = await ctx.newPage();
  const errors = [];
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
  page.on('pageerror', (e) => errors.push(String(e)));
  await page.goto(url, { waitUntil: 'networkidle' });

  ok('Continue disabled before a mood is chosen', await page.locator('[data-mood-continue]').isDisabled());

  for (const key of ['very-happy', 'good', 'neutral', 'sad', 'stressed']) {
    await page.locator(`label.is-mood-${key}`).click();
    const state = await page.evaluate((key) => ({
      checked: document.querySelector(`input[value="${key}"]`).checked,
      selected: [...document.querySelectorAll('[data-mood-card].is-selected')].map((c) => c.querySelector('input').value),
      enabled: !document.querySelector('[data-mood-continue]').disabled,
    }), key);
    ok(`Click "${key}" selects only that row`, state.checked && state.selected.length === 1 && state.selected[0] === key && state.enabled, JSON.stringify(state.selected));
  }

  await page.locator('input[value="stressed"]').focus();
  await page.keyboard.press('ArrowUp');
  const kb = await page.evaluate(() => [...document.querySelectorAll('[data-mood-card].is-selected')].map((c) => c.querySelector('input').value));
  ok('Keyboard ArrowUp moves the selection', kb.length === 1 && kb[0] === 'sad', JSON.stringify(kb));
  const focusVisible = await page.evaluate(() => getComputedStyle(document.querySelector('input[value="sad"] + .yk-mood__face')).outlineStyle);
  ok('Keyboard focus ring visible on the focused mood', focusVisible !== 'none', focusVisible);

  await page.locator('label.is-mood-good').click();
  await Promise.all([page.waitForURL(/mood\/emotions/), page.locator('[data-mood-continue]').click()]);
  const step2 = await page.evaluate(() => ({
    label: document.querySelector('.yk-mood__progress-label').textContent.trim().replace(/\s+/g, ' '),
    current: document.querySelector('.yk-mood__steps li.is-current .yk-mood__step-label').textContent,
    done: document.querySelectorAll('.yk-mood__steps li.is-done').length,
    fill: document.querySelector('.yk-mood__progress-bar > span').style.width,
  }));
  ok('Continue goes to step 2 and the progress updates', step2.label.startsWith('Step 2 of 4') && step2.current === 'Emotions' && step2.done === 1, JSON.stringify(step2));
  await page.screenshot({ path: '_mood_qa/flow-step2.png' });

  await page.locator('.yk-emotion__chip').first().click();
  await Promise.all([page.waitForURL(/mood\/reflection/), page.locator('.yk-mood__continue').click()]);
  const step3 = await page.evaluate(() => document.querySelector('.yk-mood__progress-label').textContent.trim().replace(/\s+/g, ' '));
  ok('Emotions continue goes to step 3', step3.startsWith('Step 3 of 4'), step3);
  await page.screenshot({ path: '_mood_qa/flow-step3.png' });

  await page.goto(base + '/tools/psychology/mood/emotions', { waitUntil: 'networkidle' });
  await Promise.all([page.waitForURL(/psychology\/mood$/), page.locator('.yk-mood__back').click()]);
  const pre = await page.evaluate(() => document.querySelector('[data-mood-card][data-selected="true"] input')?.value);
  ok('Back navigation returns to step 1 with the saved mood preselected', pre === 'good', String(pre));

  const caret = page.locator('.yk-navbar__caret').first();
  if (await caret.count()) {
    await caret.click();
    const expanded = await caret.getAttribute('aria-expanded');
    ok('Navbar dropdown still opens', expanded === 'true', `aria-expanded=${expanded}`);
  }
  ok('No console errors during the flow', errors.length === 0, errors.join(' | '));
  await ctx.close();
}

// ---- Responsive checks ----
const viewports = [[1536, 1024], [1440, 900], [1280, 800], [1024, 768], [768, 1024], [390, 844], [375, 812]];
for (const [w, h] of viewports) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const page = await ctx.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(400);
  const m = await page.evaluate(() => {
    const box = (s) => { const e = document.querySelector(s); return e ? e.getBoundingClientRect() : null; };
    const hit = (a, b) => a && b && a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;
    const copyEls = ['.yk-mood__hero h1', '.yk-mood__intro', '.yk-mood__perks', '.yk-mood__pills'].map(box);
    const art = box('.yk-mood__hero-art');
    // Use the artwork's visible core (the asset has transparent margins) for overlap checks.
    const core = art && { left: art.left + art.width * 0.1, right: art.right - art.width * 0.02, top: art.top + art.height * 0.05, bottom: art.top + art.height * 0.935 };
    const clipped = [...document.querySelectorAll('.yk-mood h1, .yk-mood h2, .yk-mood__name, .yk-mood__caption, .yk-mood__step-label, .yk-mood__journey strong, .yk-mood__perks li, .yk-mood__progress-label')]
      .filter((e) => e.scrollWidth > e.clientWidth + 1 && getComputedStyle(e).overflow !== 'visible').length;
    const offscreen = [...document.querySelectorAll('.yk-mood__wrap *')].filter((e) => { const r = e.getBoundingClientRect(); return r.width && (r.right > innerWidth + 1 || r.left < -1); }).map((e) => e.className).slice(0, 5);
    const progress = box('.yk-mood__progress');
    return {
      scrollW: document.documentElement.scrollWidth, clientW: document.documentElement.clientWidth,
      textArtOverlap: copyEls.some((c) => hit(c, core)),
      artOverProgress: core && progress ? core.bottom > progress.top + 2 : false,
      clipped, offscreen,
      layoutCols: getComputedStyle(document.querySelector('.yk-mood__layout')).gridTemplateColumns.split(' ').length,
      asideCols: getComputedStyle(document.querySelector('.yk-mood__aside')).gridTemplateColumns.split(' ').length,
      rowH: box('.yk-mood__card').height,
      footerTop: Math.round(box('footer').top + scrollY),
      contentBottom: Math.round(Math.max(box('.yk-mood__form').bottom, box('.yk-mood__aside').bottom) + scrollY),
    };
  });
  const tag = `${w}x${h}`;
  ok(`${tag} no horizontal overflow`, m.scrollW <= m.clientW, `${m.scrollW}/${m.clientW} offscreen=${JSON.stringify(m.offscreen)}`);
  ok(`${tag} hero text not covered by artwork`, !m.textArtOverlap);
  ok(`${tag} artwork does not cover the progress card`, !m.artOverProgress);
  ok(`${tag} no clipped text`, m.clipped === 0, String(m.clipped));
  ok(`${tag} touch-friendly mood rows (>=44px)`, m.rowH >= 44, String(m.rowH));
  ok(`${tag} footer follows content in normal flow`, m.footerTop >= m.contentBottom, `content ends ${m.contentBottom}, footer ${m.footerTop}`);
  ok(`${tag} no console errors`, errors.length === 0, errors.join(' | '));
  results.push(`      layout columns=${m.layoutCols} aside columns=${m.asideCols}`);
  await page.screenshot({ path: `_mood_qa/vp-${tag}.png`, fullPage: w < 1100 });
  await ctx.close();
}

console.log(results.join('\n'));
await browser.close();
