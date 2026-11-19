import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const results = [];
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
const browser = await chromium.launch();

async function toEmotions(ctx) {
  const page = await ctx.newPage();
  await page.goto(base + '/tools/psychology/mood', { waitUntil: 'networkidle' });
  await page.locator('label.is-mood-good').click();
  await Promise.all([page.waitForURL(/mood\/emotions/), page.locator('[data-mood-continue]').click()]);
  await page.waitForLoadState('networkidle');
  await page.evaluate(() => document.fonts.ready);
  return page;
}

const state = (page) => page.evaluate(() => ({
  count: document.querySelector('[data-emotion-count]').textContent.trim(),
  selected: [...document.querySelectorAll('[data-emotion-chip].is-selected')].map((c) => c.querySelector('input').value),
  marked: document.querySelectorAll('[data-emotion-chip][data-selected="true"]').length,
  locked: document.querySelectorAll('[data-emotion-chip].is-locked').length,
  disabledInputs: document.querySelectorAll('input[name="emotions[]"]:disabled').length,
  clearDisabled: document.querySelector('[data-emotion-clear]').disabled,
  checkOpacity: [...document.querySelectorAll('[data-emotion-chip].is-selected .yk-emotion__check')].map((c) => getComputedStyle(c).opacity),
}));

// ---- Functional flow ----
{
  const ctx = await browser.newContext({ viewport: { width: 1536, height: 1024 } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const errors = [];
  ctx.on('page', (p) => { p.on('pageerror', (e) => errors.push(String(e))); p.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); }); });
  const page = await toEmotions(ctx);

  let s = await state(page);
  ok('Starts with none selected and Clear all disabled', s.count === '0 of 6 selected' && s.selected.length === 0 && s.clearDisabled, JSON.stringify(s));

  await page.locator('label.is-emotion-happy').click();
  await page.waitForTimeout(300);
  s = await state(page);
  ok('Clicking a chip selects it, shows the check badge and updates the count', s.selected.join() === 'happy' && s.marked === 1 && s.count === '1 of 6 selected' && !s.clearDisabled && s.checkOpacity[0] === '1', JSON.stringify(s));

  await page.locator('label.is-emotion-happy').click();
  s = await state(page);
  ok('Clicking again deselects it', s.selected.length === 0 && s.count === '0 of 6 selected', JSON.stringify(s));

  for (const k of ['happy', 'calm', 'excited', 'thoughtful', 'tired', 'anxious']) await page.locator(`label.is-emotion-${k}`).click();
  s = await state(page);
  ok('Six selected locks the other nine chips', s.selected.length === 6 && s.locked === 9 && s.disabledInputs === 9 && s.count === '6 of 6 selected', JSON.stringify({ locked: s.locked, count: s.count }));
  await page.locator('label.is-emotion-lonely').click({ force: true });
  s = await state(page);
  ok('A seventh chip cannot be selected', s.selected.length === 6 && !s.selected.includes('lonely'), JSON.stringify(s.selected));

  await page.locator('[data-emotion-clear]').click();
  s = await state(page);
  const focused = await page.evaluate(() => document.activeElement?.value);
  ok('Clear all empties the selection, unlocks chips and disables itself', s.selected.length === 0 && s.locked === 0 && s.disabledInputs === 0 && s.clearDisabled && s.count === '0 of 6 selected', JSON.stringify(s));
  ok('Clear all moves focus back to the first emotion', focused === 'happy', String(focused));

  await page.keyboard.press('Tab');
  await page.keyboard.press('Space');
  s = await state(page);
  const ring = await page.evaluate(() => getComputedStyle(document.querySelector('label.is-emotion-calm')).outlineStyle);
  ok('Keyboard Tab + Space toggles a chip', s.selected.join() === 'calm', JSON.stringify(s.selected));
  ok('Keyboard focus ring visible on the focused chip', ring !== 'none', ring);

  await page.locator('label.is-emotion-thoughtful').click();
  await Promise.all([page.waitForURL(/mood\/reflection/), page.locator('.yk-mood__continue').click()]);
  const step3 = await page.evaluate(() => document.querySelector('.yk-mood__progress-label').textContent.trim().replace(/\s+/g, ' '));
  ok('Continue submits and goes to step 3', step3.startsWith('Step 3 of 4'), step3);

  await page.goto(base + '/tools/psychology/mood/emotions', { waitUntil: 'networkidle' });
  s = await state(page);
  ok('Returning to step 2 keeps the saved emotions selected', s.selected.join() === 'calm,thoughtful' && s.count === '2 of 6 selected' && !s.clearDisabled, JSON.stringify(s));

  await Promise.all([page.waitForURL(/psychology\/mood$/), page.locator('.yk-mood__back').click()]);
  const pre = await page.evaluate(() => document.querySelector('[data-mood-card][data-selected="true"] input')?.value);
  ok('Back returns to step 1 with the saved mood', pre === 'good', String(pre));
  ok('No console errors during the flow', errors.length === 0, errors.join(' | '));
  await ctx.close();
}

// ---- Responsive checks ----
const viewports = [[1536, 1024], [1440, 900], [1280, 800], [1024, 768], [768, 1024], [390, 844], [375, 812]];
for (const [w, h] of viewports) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const errors = [];
  ctx.on('page', (p) => { p.on('pageerror', (e) => errors.push(String(e))); p.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); }); });
  const page = await toEmotions(ctx);
  for (const k of ['happy', 'excited', 'thoughtful', 'anxious']) await page.locator(`label.is-emotion-${k}`).click();
  await page.mouse.move(0, 0);
  await page.waitForTimeout(400);
  const m = await page.evaluate(() => {
    const box = (e) => (typeof e === 'string' ? document.querySelector(e) : e)?.getBoundingClientRect();
    const hit = (a, b) => a && b && a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;
    const img = document.querySelector('.yk-emotion__art img');
    const visible = img && getComputedStyle(img.parentElement).display !== 'none';
    const r = visible ? box(img) : null;
    // Visible core of the portrait (the asset has transparent margins).
    const core = r && { left: r.left + r.width * 0.19, right: r.left + r.width * 0.91, top: r.top + r.height * 0.02, bottom: r.top + r.height * 0.93 };
    const content = [...document.querySelectorAll('.yk-emotion__main h1, .yk-emotion__main .yk-mood__lead, .yk-emotion__main .yk-mood__hint, .yk-emotion__chip, .yk-emotion__footer > *, .yk-mood__continue')];
    const clipped = [...document.querySelectorAll('.yk-mood h1, .yk-emotion__group legend, .yk-emotion__label, .yk-emotion__count, .yk-emotion__clear, .yk-mood__continue, .yk-mood__step-label, .yk-mood__progress-label')]
      .filter((e) => e.scrollWidth > e.clientWidth + 1).map((e) => e.textContent.trim().slice(0, 20));
    const panel = box('.yk-emotion__panel');
    const chips = [...document.querySelectorAll('.yk-emotion__chip')].map((c) => c.getBoundingClientRect());
    return {
      scrollW: document.documentElement.scrollWidth, clientW: document.documentElement.clientWidth,
      artOverlap: core ? content.filter((e) => hit(box(e), core)).map((e) => e.className || e.tagName).slice(0, 4) : [],
      artShown: !!visible,
      clipped,
      chipsOutside: chips.filter((c) => c.right > panel.right + 1 || c.left < panel.left - 1).length,
      minChipH: Math.round(Math.min(...chips.map((c) => c.height))),
      footerTop: Math.round(box('footer').top + scrollY),
      contentBottom: Math.round(panel.bottom + scrollY),
    };
  });
  const tag = `${w}x${h}`;
  ok(`${tag} no horizontal overflow`, m.scrollW <= m.clientW, `${m.scrollW}/${m.clientW}`);
  ok(`${tag} portrait does not cover text or chips`, m.artOverlap.length === 0, `shown=${m.artShown} ${JSON.stringify(m.artOverlap)}`);
  ok(`${tag} no clipped text`, m.clipped.length === 0, JSON.stringify(m.clipped));
  ok(`${tag} chips stay inside the card`, m.chipsOutside === 0, String(m.chipsOutside));
  ok(`${tag} touch-friendly chips (>=44px)`, m.minChipH >= 44, String(m.minChipH));
  ok(`${tag} footer follows content in normal flow`, m.footerTop >= m.contentBottom, `content ends ${m.contentBottom}, footer ${m.footerTop}`);
  ok(`${tag} no console errors`, errors.length === 0, errors.join(' | '));
  await page.screenshot({ path: `_mood_qa/evp-${tag}.png`, fullPage: w < 1100 });
  await ctx.close();
}

console.log(results.join('\n'));
await browser.close();
