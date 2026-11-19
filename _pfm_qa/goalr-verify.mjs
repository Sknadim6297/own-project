import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const form = base + '/tools/pfm/financial-goal';
const results = [];
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
const browser = await chromium.launch();

const inr = (v) => {
  let n = String(Math.round(Math.abs(v)));
  if (n.length > 3) n = n.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + n.slice(-3);
  return (v < 0 ? '-' : '') + '₹' + n;
};
const maths = ({ cost, years, infl, ret, existing }) => {
  const corpus = cost * Math.pow(1 + infl / 100, years);
  const fv = existing * Math.pow(1 + ret / 100, years);
  const gap = Math.max(corpus - fv, 0);
  const n = Math.round(years * 12);
  const r = ret / 100 / 12;
  const pmt = gap <= 0 ? 0 : r === 0 ? gap / n : (gap * r) / (Math.pow(1 + r, n) - 1);
  return { corpus, fv, gap, pmt, n };
};

async function openResults(ctx, s) {
  const page = await ctx.newPage();
  await page.goto(form, { waitUntil: 'networkidle' });
  const status = await page.evaluate(async (s) => {
    const token = document.querySelector('input[name=_token]').value;
    const body = new URLSearchParams({
      _token: token, goal_type: s.type, goal_name: s.name, current_cost: String(s.cost), years: String(s.years),
      inflation_percent: String(s.infl), expected_return_percent: String(s.ret), existing_savings: String(s.existing),
    });
    const res = await fetch(location.pathname + '/calculate', { method: 'POST', body, credentials: 'same-origin', redirect: 'follow' });
    return res.url + ' ' + res.status;
  }, s);
  await page.goto(base + '/tools/pfm/financial-goal/results', { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  return { page, status };
}

const scenarios = {
  gap: { type: 'home', name: 'Dream flat', cost: 2500000, years: 10, infl: 8, ret: 10, existing: 300000 },
  ok: { type: 'vacation', name: 'Bali trip', cost: 100000, years: 3, infl: 5, ret: 12, existing: 500000 },
  zero: { type: 'custom', name: 'A very long goal name that keeps going to test how the hero heading wraps on small screens', cost: 98765432, years: 25, infl: 6.5, ret: 0, existing: 0 },
};

// ---- Content & figures ----
for (const [key, s] of Object.entries(scenarios)) {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const errors = [];
  const { page } = await openResults(ctx, s);
  page.on('pageerror', (e) => errors.push(String(e)));
  const m = maths(s);
  const body = (await page.locator('main').innerText()).replace(/\s+/g, ' ');
  ok(`[${key}] heading is the goal name`, (await page.locator('h1').innerText()).trim() === s.name);
  ok(`[${key}] monthly figure matches the formula`, (await page.locator('[data-goalr=monthly] strong').innerText()).trim() === inr(m.pmt), `${await page.locator('[data-goalr=monthly] strong').innerText()} vs ${inr(m.pmt)}`);
  ok(`[${key}] goal cost matches`, (await page.locator('[data-goalr=corpus]').innerText()).trim() === inr(m.corpus), `${await page.locator('[data-goalr=corpus]').innerText()} vs ${inr(m.corpus)}`);
  ok(`[${key}] savings growth matches`, (await page.locator('[data-goalr=future-existing]').innerText()).trim() === inr(m.fv));
  const gapText = (await page.locator('[data-goalr=gap]').innerText()).trim();
  ok(`[${key}] gap/surplus matches`, gapText === (m.gap > 0.5 ? inr(m.gap) : inr(Math.max(0, m.fv - m.corpus))), gapText);
  ok(`[${key}] status chip`, body.toLowerCase().includes(m.gap > 0.5 ? 'gap to fund' : 'on track'));
  ok(`[${key}] funding bar present`, (await page.locator('.yk-goalr__bar').count()) === 1);
  ok(`[${key}] chart visible`, await page.locator('.yk-goalr__svg--wide').isVisible() && !(await page.locator('.yk-goalr__svg--compact').isVisible()));
  ok(`[${key}] disclaimer shown`, body.includes('educational estimates only and are not financial advice'));
  for (const label of ['Save result (sign in)', 'Edit & recalculate']) {
    ok(`[${key}] button "${label}"`, (await page.getByRole('button', { name: label }).count()) === 1);
  }
  const btn = await page.getByRole('button', { name: 'Edit & recalculate' }).evaluate((el) => {
    const cs = getComputedStyle(el); return { bg: cs.backgroundColor, color: cs.color, h: el.getBoundingClientRect().height };
  });
  ok(`[${key}] buttons are visible and tappable`, btn.h >= 44 && btn.bg !== 'rgba(0, 0, 0, 0)' && btn.color !== btn.bg, JSON.stringify(btn));
  ok(`[${key}] no console/page errors`, errors.length === 0, errors.join(' | '));
  if (key === 'gap') {
    await page.screenshot({ path: '_pfm_qa/results-gap-1440.png', fullPage: true });
    // Recalculate returns to a prefilled form
    await Promise.all([page.waitForURL(/financial-goal$/), page.getByRole('button', { name: 'Edit & recalculate' }).click()]);
    ok('Edit & recalculate returns to a fresh form (existing behaviour)', /financial-goal$/.test(page.url()) && (await page.locator('[data-pfm-wizard]').count()) === 1);
  }
  await ctx.close();
}

if (process.env.SKIP_LAYOUT) { await browser.close(); console.log(results.join('\n')); process.exit(0); }
// ---- Layout across viewports ----
const viewports = [[1536, 1024], [1440, 900], [1280, 800], [1024, 768], [768, 1024], [390, 844], [375, 812], [320, 700]];
for (const [key, s] of Object.entries(scenarios)) {
  for (const [w, h] of viewports) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h } });
    await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
    const { page } = await openResults(ctx, s);
    const r = await page.evaluate(() => {
      const de = document.documentElement;
      const out = { hOverflow: de.scrollWidth - de.clientWidth, clipped: [], overlaps: [] };
      document.querySelectorAll('.yk-goalr h1, .yk-goalr h2, .yk-goalr p, .yk-goalr dd, .yk-goalr dt, .yk-goalr b, .yk-goalr li, .yk-goalr button, .yk-goalr a').forEach((el) => {
        if (el.closest('.visually-hidden') || !el.offsetParent) return;
        if (el.closest('svg')) return;
        if (el.scrollWidth - el.clientWidth > 1 && getComputedStyle(el).display !== 'inline') out.clipped.push(el.tagName + '.' + el.className + ' ' + el.scrollWidth + '>' + el.clientWidth);
      });
      const vis = (sel) => { const e = document.querySelector(sel); return e && e.offsetParent !== null && e.getBoundingClientRect().width > 0; };
      out.wide = vis('.yk-goalr__svg--wide'); out.compact = vis('.yk-goalr__svg--compact');
      const svg = document.querySelector('.yk-goalr__svg--wide'); const svgc = document.querySelector('.yk-goalr__svg--compact');
      const act = [svg, svgc].find((e) => e && e.getBoundingClientRect().width > 0);
      out.svgW = act ? Math.round(act.getBoundingClientRect().width) : 0;
      out.fontPx = act ? Math.round(12.5 * act.getBoundingClientRect().width / (act === svgc ? 340 : 640) * 10) / 10 : 0;
      const cards = [...document.querySelectorAll('.yk-goalr__main > section, .yk-goalr__aside > section')];
      cards.forEach((c) => { const b = c.getBoundingClientRect(); if (b.right > innerWidth + 1 || b.left < -1) out.overlaps.push(c.className + ' ' + Math.round(b.left) + '..' + Math.round(b.right)); });
      const f = document.querySelector('footer'); out.footer = !!f && getComputedStyle(f).display !== 'none' && f.getBoundingClientRect().height > 20;
      out.pageH = de.scrollHeight;
      return out;
    });
    const tag = `[${key} ${w}x${h}]`;
    ok(`${tag} no horizontal overflow`, r.hOverflow <= 0, String(r.hOverflow));
    ok(`${tag} no clipped text`, r.clipped.length === 0, r.clipped.slice(0, 3).join(' | '));
    ok(`${tag} cards inside viewport`, r.overlaps.length === 0, r.overlaps.join(' | '));
    ok(`${tag} correct chart variant (${w < 640 ? 'compact' : 'wide'})`, w < 640 ? r.compact && !r.wide : r.wide && !r.compact, `svgW=${r.svgW} fontPx≈${r.fontPx}`);
    ok(`${tag} footer still shown`, r.footer);
    if (key === 'gap' && [1280, 768, 390, 320].includes(w)) await page.screenshot({ path: `_pfm_qa/results-gap-${w}.png`, fullPage: true });
    await ctx.close();
  }
}

await browser.close();
console.log(results.join('\n'));
console.log(`\n${results.filter((r) => r.startsWith('PASS')).length} PASS, ${results.filter((r) => r.startsWith('FAIL')).length} FAIL`);
