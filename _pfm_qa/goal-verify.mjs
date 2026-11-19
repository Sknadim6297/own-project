import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const url = base + '/tools/pfm/financial-goal';
const results = [];
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
const browser = await chromium.launch();
const text = (page, sel) => page.locator(sel).first().innerText().then((t) => t.trim().replace(/\s+/g, ' '));

// ---- Functional flow ----
{
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const errors = [];
  const page = await ctx.newPage();
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('console', (m) => { if (m.type() === 'error' && !/favicon/.test(m.text())) errors.push(m.text()); });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);

  const initFocus = await page.evaluate(() => document.activeElement?.tagName);
  ok('No focus stolen on page load', initFocus === 'BODY', initFocus);
  ok('Custom is preselected', await page.locator('input[name=goal_type][value=custom]').isChecked());

  await page.locator('label.yk-goal__type', { hasText: 'Home' }).click();
  ok('Picking Home updates the summary', (await text(page, '[data-goal-out=type]')) === 'Home' && (await text(page, '[data-goal-out=name]')) === 'Home');
  await page.fill('#pfm-goal-name', 'Dream flat');
  ok('Goal name flows into the summary', (await text(page, '[data-goal-out=name]')) === 'Dream flat');

  await page.locator('[data-pfm-next]').click();
  ok('Continue moves to step 2', await page.locator('#pfm-current-cost').isVisible());
  await page.locator('[data-pfm-next]').click();
  ok('Empty required fields block step 2', await page.locator('#pfm-current-cost').isVisible() && !(await page.locator('#pfm-inflation').isVisible()));

  await page.fill('#pfm-current-cost', '2500000');
  ok('Amount shows in words', (await text(page, '[data-goal-out=cost-words]')).includes('25 lakh'), await text(page, '[data-goal-out=cost-words]'));
  await page.locator('[data-goal-years="10"]').click();
  ok('Quick year fills the field', (await page.inputValue('#pfm-years')) === '10');
  ok('Quick year is marked active', (await page.locator('[data-goal-years="10"]').getAttribute('aria-pressed')) === 'true');
  const year = new Date(); year.setMonth(year.getMonth() + 120);
  ok('Target year is computed', (await text(page, '[data-goal-out=year]')) === String(year.getFullYear()), await text(page, '[data-goal-out=year]'));
  const expected = Math.round(2500000 * Math.pow(1.06, 10)).toLocaleString('en-IN');
  ok('Estimated future cost matches the formula', (await text(page, '[data-goal-out=future]')) === '₹' + expected, await text(page, '[data-goal-out=future]'));

  await page.locator('[data-pfm-next]').click();
  ok('Step 3 shows the Calculate button', await page.locator('[data-pfm-submit]').isVisible() && !(await page.locator('[data-pfm-next]').isVisible()));
  await page.fill('#pfm-inflation', '8');
  const expected8 = Math.round(2500000 * Math.pow(1.08, 10)).toLocaleString('en-IN');
  ok('Changing inflation updates the estimate', (await text(page, '[data-goal-out=future]')) === '₹' + expected8, await text(page, '[data-goal-out=future]'));
  ok('Review strip shows the inputs', (await text(page, '.yk-goal__review')).includes('Dream flat') && (await text(page, '.yk-goal__review')).includes('10 years'));

  await page.locator('[data-pfm-prev]').click();
  ok('Back returns to step 2 keeping values', (await page.inputValue('#pfm-years')) === '10' && (await page.inputValue('#pfm-current-cost')) === '2500000');
  await page.locator('[data-pfm-next]').click();

  await Promise.all([page.waitForURL(/financial-goal\/results/), page.locator('[data-pfm-submit]').click()]);
  const body = await page.locator('main').innerText();
  ok('Calculate reaches the results page', /results/.test(page.url()));
  ok('Results use the entered goal', body.includes('Dream flat'), body.slice(0, 120).replace(/\s+/g, ' '));
  ok('Server cost matches the live estimate', body.replace(/,/g, '').includes(String(Math.round(2500000 * Math.pow(1.08, 10)))), 'looking for ' + Math.round(2500000 * Math.pow(1.08, 10)));

  await page.goto(url, { waitUntil: 'networkidle' });
  ok('Returning keeps the saved goal prefilled', (await page.inputValue('#pfm-goal-name')) === 'Dream flat');
  ok('"You have a recent result" notice shows', await page.locator('.yk-goal__notice').isVisible());
  ok('No console errors during the flow', errors.length === 0, errors.join(' | '));
  await ctx.close();
}

// ---- Responsive ----
for (const [w, h] of [[1536, 1024], [1440, 900], [1280, 800], [1024, 768], [768, 1024], [390, 844], [375, 812], [320, 700]]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: w < 500 ? 2 : 1 });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const page = await ctx.newPage();
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  const tag = `${w}x${h}`;
  const measure = () => page.evaluate(() => {
    const box = (s) => document.querySelector(s)?.getBoundingClientRect();
    const visible = (e) => e.getClientRects().length > 0;
    const clipped = [...document.querySelectorAll('.yk-goal h1, .yk-goal h2, .yk-goal h3, .yk-goal__btn, .yk-goal__type-name, .yk-goal__future strong, .yk-goal__review dd, .yk-goal__rows dd, .yk-goal__summary h2')]
      .filter((e) => visible(e) && !e.classList.contains('visually-hidden') && e.scrollWidth > e.clientWidth + 1 && getComputedStyle(e).textOverflow !== 'ellipsis').map((e) => e.textContent.trim().slice(0, 18));
    const card = box('.yk-goal__card');
    const outside = [...document.querySelectorAll('.yk-goal__card .yk-goal__type, .yk-goal__card .yk-goal__control, .yk-goal__card .yk-goal__btn')].filter((e) => visible(e)).filter((e) => {
      const r = e.getBoundingClientRect();
      return r.right > card.right + 1 || r.left < card.left - 1;
    }).length;
    const small = [...document.querySelectorAll('.yk-goal__btn, .yk-goal__control, .yk-goal__type, .yk-goal__quick button')].filter(visible)
      .map((e) => Math.round(e.getBoundingClientRect().height)).filter((v) => v < 36);
    return { sw: document.documentElement.scrollWidth, cw: document.documentElement.clientWidth, clipped, outside, small, footerTop: Math.round(box('footer').top + scrollY), contentBottom: Math.round(Math.max(box('.yk-goal__card').bottom, box('.yk-goal__aside').bottom) + scrollY) };
  });
  let m = await measure();
  ok(`${tag} step 1: no horizontal overflow`, m.sw <= m.cw, `${m.sw}/${m.cw}`);
  ok(`${tag} step 1: no clipped text`, m.clipped.length === 0, JSON.stringify(m.clipped));
  ok(`${tag} step 1: controls inside the card`, m.outside === 0, String(m.outside));
  ok(`${tag} step 1: footer follows content`, m.footerTop >= m.contentBottom, `${m.contentBottom} vs ${m.footerTop}`);

  await page.locator('label.yk-goal__type', { hasText: 'Vacation' }).click();
  await page.locator('[data-pfm-next]').click();
  await page.fill('#pfm-current-cost', '123456789');
  await page.locator('[data-goal-years="20"]').click();
  m = await measure();
  ok(`${tag} step 2: no overflow / clipping with a large amount`, m.sw <= m.cw && m.clipped.length === 0 && m.outside === 0, JSON.stringify({ sw: m.sw, cw: m.cw, c: m.clipped }));
  ok(`${tag} step 2: touch targets >= 36px`, m.small.length === 0, JSON.stringify(m.small));
  await page.screenshot({ path: `_pfm_qa/gv-s2-${tag}.png` });
  await page.locator('[data-pfm-next]').click();
  m = await measure();
  ok(`${tag} step 3: no overflow / clipping`, m.sw <= m.cw && m.clipped.length === 0 && m.outside === 0, JSON.stringify({ sw: m.sw, cw: m.cw, c: m.clipped }));
  await ctx.close();
}

console.log(results.join('\n'));
await browser.close();
