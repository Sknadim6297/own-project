import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const tag = process.argv[2] || 'run';
const results = [];
let browserRef;
async function browser_ctx(w, h) {
  browserRef ??= await chromium.launch();
  const ctx = await browserRef.newContext({ viewport: { width: w, height: h } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  return ctx;
}
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);

const tools = {
  'monthly-money-planner': { path: 'money-planner', data: { monthly_income: 80000, essential_expenses: 50000, lifestyle_expenses: 10000, emis: 5000, other_recurring: 0, investments: 15000 } },
  'net-worth-calculator': { path: 'net-worth', data: { cash: 50000, bank: 200000, fd: 100000, mf: 150000, stocks: 80000, gold: 60000, property: 3000000, epf_nps: 400000, loans: 1500000, credit_cards: 20000, other_debt: 0 } },
  'emergency-fund-calculator': { path: 'emergency-fund', data: { monthly_expenses: 45000, coverage_months: 6, current_savings: 150000, monthly_saving_capacity: 10000 } },
  'financial-goal-calculator': { path: 'financial-goal', data: { goal_name: 'Dream flat', current_cost: 2500000, years: 10, inflation_percent: 6, expected_return_percent: 10, existing_savings: 300000 } },
  'debt-emi-calculator': { path: 'debt-emi', data: { principal: 2000000, annual_rate_percent: 9, tenure_years: 15, extra_payment: 5000 } },
  'retirement-calculator': { path: 'retirement', data: { current_age: 30, retirement_age: 60, life_expectancy: 85, current_monthly_expenses: 50000, current_assets: 500000, inflation_percent: 6, pre_retirement_return_percent: 10, post_retirement_return_percent: 7 } },
  'inflation-calculator': { path: 'inflation', data: { current_amount: 10000, inflation_percent: 6, years: 10 } },
  'savings-investment-calculator': { path: 'savings-investment', data: { initial_amount: 100000, monthly_contribution: 5000, expected_return_percent: 10, years: 15 } },
};

// A button counts as "visible" only if it has a real fill (colour or gradient) that differs from its text colour.
const buttonProbe = (page, selector) => page.evaluate((sel) => {
  const out = [];
  document.querySelectorAll(sel).forEach((el) => {
    if (!el.offsetParent) return;
    const cs = getComputedStyle(el);
    const bg = cs.backgroundColor;
    const alpha = bg.startsWith('rgba') ? parseFloat(bg.split(',')[3]) : 1;
    const filled = alpha > 0.05 || cs.backgroundImage !== 'none';
    const border = parseFloat(cs.borderTopWidth) > 0 && cs.borderTopColor !== 'rgba(0, 0, 0, 0)';
    const text = (el.innerText || el.value || '').trim().replace(/\s+/g, ' ');
    const white = cs.color === 'rgb(255, 255, 255)';
    out.push({ text, ok: (filled || border) && !(white && !filled), bg, color: cs.color, h: Math.round(el.getBoundingClientRect().height) });
  });
  return out;
}, selector);

const layoutProbe = (page) => page.evaluate(() => {
  const de = document.documentElement;
  const clipped = [];
  document.querySelectorAll('main h1, main h2, main p, main label, main button, main a, main dd, main dt').forEach((el) => {
    if (!el.offsetParent || el.closest('.visually-hidden') || el.matches('.yk-goal__type')) return;
    if (el.scrollWidth - el.clientWidth > 1 && getComputedStyle(el).display !== 'inline') clipped.push(el.tagName + '.' + el.className + ' ' + el.scrollWidth + '>' + el.clientWidth);
  });
  const f = document.querySelector('footer');
  return { h: de.scrollWidth - de.clientWidth, clipped, footer: !!f && f.getBoundingClientRect().height > 20, bodyText: document.querySelector('main')?.innerText ?? '' };
});

async function fillVisible(page, data) {
  for (const [name, value] of Object.entries(data)) {
    const loc = page.locator(`main input[name="${name}"]:not([type=radio]):not([type=hidden]), main textarea[name="${name}"]`).first();
    if ((await loc.count()) && (await loc.isVisible())) await loc.fill(String(value));
    const sel = page.locator(`main select[name="${name}"]`).first();
    if ((await sel.count()) && (await sel.isVisible())) await sel.selectOption(String(value)).catch(() => {});
  }
}

for (const [w, h] of [[1440, 900], [390, 844]]) {
  for (const [key, t] of Object.entries(tools)) {
    const ctx = await browser_ctx(w, h);
    const name = `[${key} ${w}]`;
    const errors = [];
    const page = await ctx.newPage();
    page.on('pageerror', (e) => errors.push(String(e)));
    page.on('console', (m) => { if (m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text())) errors.push(m.text()); });

    // Hub -> open tool
    await page.goto(base + '/tools/pfm', { waitUntil: 'networkidle' });
    const link = page.locator(`a[href$="/tools/pfm/${t.path}"]`).first();
    ok(`${name} hub has a link`, (await link.count()) === 1);
    if (!(await link.count())) { await ctx.close(); continue; }
    await Promise.all([page.waitForURL(new RegExp(`/tools/pfm/${t.path}$`)), link.click()]);
    await page.evaluate(() => document.fonts.ready);

    // Form page
    const formButtons = await buttonProbe(page, 'main button, main a.yk-pfm__btn, main .yk-goal__btn');
    const bad = formButtons.filter((b) => !b.ok);
    ok(`${name} form buttons are visible`, formButtons.length > 0 && bad.length === 0, bad.map((b) => `${b.text}(${b.bg}|${b.color})`).join(', '));
    let lay = await layoutProbe(page);
    ok(`${name} form: no horizontal overflow`, lay.h <= 0, String(lay.h));
    ok(`${name} form: no clipped text`, lay.clipped.length === 0, lay.clipped.slice(0, 2).join(' | '));
    if (w === 1440 || key === 'financial-goal-calculator') await page.screenshot({ path: `_pfm_qa/s-${tag}-${key}-form-${w}.png`, fullPage: true });

    // Drive the wizard / form
    let reached = false;
    for (let i = 0; i < 6 && !reached; i++) {
      await fillVisible(page, t.data);
      const next = page.locator('[data-pfm-next]:visible').first();
      if (await next.count()) { await next.click(); await page.waitForTimeout(150); continue; }
      const submit = page.locator('main [data-pfm-submit]:visible, main button[type=submit]:visible').first();
      await Promise.all([page.waitForURL(/\/results$|\/tools\/pfm\/[a-z-]+$/, { timeout: 8000 }).catch(() => {}), submit.click()]);
      reached = true;
    }
    await page.waitForLoadState('networkidle');
    ok(`${name} calculate reaches results`, /\/results$/.test(page.url()), page.url());

    if (/\/results$/.test(page.url())) {
      await page.evaluate(() => document.fonts.ready);
      lay = await layoutProbe(page);
      ok(`${name} results: no NaN/undefined/Infinity`, !/NaN|undefined|Infinity/.test(lay.bodyText), lay.bodyText.match(/.{0,30}(NaN|undefined|Infinity).{0,30}/)?.[0] ?? '');
      ok(`${name} results: has figures`, /\d/.test(lay.bodyText));
      ok(`${name} results: no horizontal overflow`, lay.h <= 0, String(lay.h));
      ok(`${name} results: no clipped text`, lay.clipped.length === 0, lay.clipped.slice(0, 2).join(' | '));
      ok(`${name} results: footer shown`, lay.footer);
      const rb = await buttonProbe(page, 'main button, main a.yk-pfm__btn, main .yk-goal__btn');
      const rbad = rb.filter((b) => !b.ok);
      ok(`${name} results: buttons visible`, rb.length >= 2 && rbad.length === 0, rbad.map((b) => `${b.text}(${b.bg}|${b.color})`).join(', '));
      ok(`${name} results: has Save (sign in) + Edit & recalculate`, rb.some((b) => /Save result \(sign in\)/.test(b.text)) && rb.some((b) => /Edit & recalculate/.test(b.text)), rb.map((b) => b.text).join(' / '));
      if (w === 1440 || key === 'financial-goal-calculator') await page.screenshot({ path: `_pfm_qa/s-${tag}-${key}-results-${w}.png`, fullPage: true });

      if (w === 1440) {
        await Promise.all([page.waitForURL(/\/login/, { timeout: 8000 }).catch(() => {}), page.getByRole('button', { name: /Save result \(sign in\)/ }).click()]);
        ok(`${name} guest save redirects to login`, /\/login/.test(page.url()), page.url());
      }
    }
    ok(`${name} no console/page errors`, errors.length === 0, errors.slice(0, 2).join(' | '));
    await ctx.close();
  }
}

await browserRef?.close();
console.log(results.join('\n'));
console.log(`\n${results.filter((r) => r.startsWith('PASS')).length} PASS, ${results.filter((r) => r.startsWith('FAIL')).length} FAIL`);
