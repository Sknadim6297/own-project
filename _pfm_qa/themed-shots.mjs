import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const browser = await chromium.launch();
const shots = [
  ['debt-emi', { principal: 2000000, annual_rate_percent: 9, tenure_years: 15, extra_payment: 5000 }, 'debt-emi'],
  ['retirement', { current_age: 30, retirement_age: 60, life_expectancy: 85, current_monthly_expenses: 50000, current_assets: 500000, inflation_percent: 6, pre_retirement_return_percent: 10, post_retirement_return_percent: 7 }, 'retirement'],
  ['money-planner', { monthly_income: 80000, essential_expenses: 50000, lifestyle_expenses: 10000, emis: 5000, other_recurring: 0, investments: 15000 }, 'money-planner'],
];
for (const [w, h, tag] of [[1440, 900, 'd'], [390, 844, 'm']]) {
  for (const [path, data] of shots.map((s) => [s[0], s[1]])) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h } });
    await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
    const page = await ctx.newPage();
    await page.goto(`${base}/tools/pfm/${path}`, { waitUntil: 'networkidle' });
    await page.screenshot({ path: `_pfm_qa/th-${path}-form-${tag}.png` });
    await page.evaluate(async (data) => {
      const token = document.querySelector('input[name=_token]').value;
      await fetch(location.pathname + '/calculate', { method: 'POST', body: new URLSearchParams({ _token: token, ...data }), credentials: 'same-origin' });
    }, data);
    await page.goto(`${base}/tools/pfm/${path}/results`, { waitUntil: 'networkidle' });
    await page.screenshot({ path: `_pfm_qa/th-${path}-res-${tag}.png` });
    await ctx.close();
  }
}
await browser.close();
