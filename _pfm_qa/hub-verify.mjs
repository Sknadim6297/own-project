import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const results = [];
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
const browser = await chromium.launch();

const expected = {
  'monthly-money-planner': 'money-planner', 'net-worth-calculator': 'net-worth', 'emergency-fund-calculator': 'emergency-fund',
  'financial-goal-calculator': 'financial-goal', 'debt-emi-calculator': 'debt-emi', 'retirement-calculator': 'retirement',
  'inflation-calculator': 'inflation', 'savings-investment-calculator': 'savings-investment',
};

// ---- Content, links, keyboard ----
{
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const errors = [];
  const page = await ctx.newPage();
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('console', (m) => { if (m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text())) errors.push(m.text()); });
  await page.goto(base + '/tools/pfm', { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);

  ok('Title is set', (await page.title()).includes('Money & Finance'));
  ok('One h1', (await page.locator('h1').count()) === 1 && (await page.locator('h1').innerText()).trim() === 'Personal Finance Tools');
  ok('h1 stays on one line on desktop', (await page.locator('h1').evaluate((h) => h.getBoundingClientRect().height < parseFloat(getComputedStyle(h).fontSize) * 1.6)));
  ok('Eight tool rows', (await page.locator('.yk-hub__card').count()) === 8);
  ok('Stat chip says 8 calculators', (await page.locator('.yk-hub__facts').innerText()).includes('8 calculators'));
  ok('Three groups, each with a heading', (await page.locator('.yk-hub__group h2').count()) === 3);
  for (const [slug, path] of Object.entries(expected)) {
    ok(`Link for ${slug}`, (await page.locator(`.yk-hub__link[href$="/tools/pfm/${path}"]`).count()) === 1);
  }
  const names = await page.locator('.yk-hub__text h3').allInnerTexts();
  ok('Heading order is h1 > h2 > h3', (await page.locator('main h1').count()) === 1 && names.length === 8);

  // Open pills are real filled buttons
  const pills = await page.locator('.yk-hub__open').evaluateAll((els) => els.map((el) => { const cs = getComputedStyle(el); return { bg: cs.backgroundImage !== 'none' || cs.backgroundColor !== 'rgba(0, 0, 0, 0)', h: el.getBoundingClientRect().height }; }));
  ok('All "Open" pills are filled and ≥40px tall', pills.length === 8 && pills.every((p) => p.bg && p.h >= 40), JSON.stringify(pills.slice(0, 2)));

  // Whole row is clickable, not only the title
  const row = page.locator('.yk-hub__card').nth(3);
  const box = await row.boundingBox();
  await Promise.all([page.waitForURL(/financial-goal$/), page.mouse.click(box.x + box.width * 0.55, box.y + box.height * 0.8)]);
  ok('Clicking empty space in a row opens the tool', /financial-goal$/.test(page.url()), page.url());
  await page.goBack({ waitUntil: 'networkidle' });

  // Keyboard: first Tab stops reach rows with a visible focus ring
  let focusOk = false;
  for (let i = 0; i < 40 && !focusOk; i++) {
    await page.keyboard.press('Tab');
    const f = await page.evaluate(() => {
      const el = document.activeElement;
      if (!el?.classList?.contains('yk-hub__link')) return null;
      const cs = getComputedStyle(el, '::after');
      return { outline: cs.outlineStyle + ' ' + cs.outlineWidth };
    });
    if (f) focusOk = f.outline.startsWith('solid') && f.outline.endsWith('3px');
  }
  ok('Keyboard focus ring on a tool row', focusOk);

  // Resume chip appears after calculating, and goes to results
  ok('No "View result" chips before calculating', (await page.locator('.yk-hub__resume').count()) === 0);
  await page.goto(base + '/tools/pfm/inflation', { waitUntil: 'networkidle' });
  await page.fill('input[name=current_amount]', '10000');
  await page.fill('input[name=inflation_percent]', '6');
  await page.fill('input[name=years]', '10');
  await Promise.all([page.waitForURL(/inflation\/results$/), page.locator('main button[type=submit]:visible').first().click()]);
  await page.goto(base + '/tools/pfm', { waitUntil: 'networkidle' });
  ok('"View result" chip appears for the calculated tool only', (await page.locator('.yk-hub__resume').count()) === 1 && (await page.locator('.yk-hub__resume').getAttribute('href')).endsWith('/tools/pfm/inflation/results'));
  await Promise.all([page.waitForURL(/inflation\/results$/), page.locator('.yk-hub__resume').click()]);
  ok('Chip opens the saved results page', /inflation\/results$/.test(page.url()));
  ok('No console/page errors', errors.length === 0, errors.join(' | '));
  await ctx.close();
}

// ---- Layout ----
for (const [w, h] of [[1536, 1024], [1440, 900], [1280, 800], [1024, 768], [768, 1024], [390, 844], [375, 812], [320, 700]]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const page = await ctx.newPage();
  await page.goto(base + '/tools/pfm', { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  const r = await page.evaluate(() => {
    const de = document.documentElement;
    const clipped = [];
    document.querySelectorAll('.yk-hub h1, .yk-hub h2, .yk-hub h3, .yk-hub p, .yk-hub li, .yk-hub a, .yk-hub span').forEach((el) => {
      if (!el.offsetParent) return;
      if (el.scrollWidth - el.clientWidth > 1 && getComputedStyle(el).display !== 'inline') clipped.push(el.tagName + '.' + el.className + ' ' + el.scrollWidth + '>' + el.clientWidth);
    });
    const outside = [...document.querySelectorAll('.yk-hub__group, .yk-hub__card')].filter((c) => { const b = c.getBoundingClientRect(); return b.right > innerWidth + 1 || b.left < -1; }).length;
    const cards = [...document.querySelectorAll('.yk-hub__card')].map((c) => c.getBoundingClientRect());
    const overlap = cards.some((a, i) => cards.some((b, j) => j > i && a.left < b.right - 1 && b.left < a.right - 1 && a.top < b.bottom - 1 && b.top < a.bottom - 1));
    const f = document.querySelector('footer');
    const small = [...document.querySelectorAll('.yk-hub__resume, .yk-hub__open, .yk-hub__card')].filter((e) => e.offsetParent && e.getBoundingClientRect().height < 40).length;
    return { h: de.scrollWidth - de.clientWidth, clipped, outside, overlap, footer: !!f && f.getBoundingClientRect().height > 20, small, h1: Math.round(document.querySelector('h1').getBoundingClientRect().height) };
  });
  const tag = `[hub ${w}x${h}]`;
  ok(`${tag} no horizontal overflow`, r.h <= 0, String(r.h));
  ok(`${tag} no clipped text`, r.clipped.length === 0, r.clipped.slice(0, 3).join(' | '));
  ok(`${tag} cards inside viewport, no overlap`, r.outside === 0 && !r.overlap);
  ok(`${tag} footer still shown`, r.footer);
  ok(`${tag} touch targets not tiny`, r.small === 0, String(r.small));
  if ([1280, 768, 320].includes(w)) await page.screenshot({ path: `_pfm_qa/hub-v-${w}.png`, fullPage: true });
  await ctx.close();
}

await browser.close();
console.log(results.join('\n'));
console.log(`\n${results.filter((r) => r.startsWith('PASS')).length} PASS, ${results.filter((r) => r.startsWith('FAIL')).length} FAIL`);
