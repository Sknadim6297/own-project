import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const s = { type: 'home', name: 'Dream flat', cost: 2500000, years: 10, infl: 8, ret: 10, existing: 300000 };
const browser = await chromium.launch();
for (const [w, h, name, full] of [[1440, 1000, 'a', false], [1440, 1000, 'b', 'scroll'], [390, 844, 'm1', false], [390, 844, 'm2', 'scroll']]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const page = await ctx.newPage();
  await page.goto(base + '/tools/pfm/financial-goal', { waitUntil: 'networkidle' });
  await page.evaluate(async (s) => {
    const token = document.querySelector('input[name=_token]').value;
    const body = new URLSearchParams({ _token: token, goal_type: s.type, goal_name: s.name, current_cost: s.cost, years: s.years, inflation_percent: s.infl, expected_return_percent: s.ret, existing_savings: s.existing });
    await fetch(location.pathname + '/calculate', { method: 'POST', body, credentials: 'same-origin' });
  }, s);
  await page.goto(base + '/tools/pfm/financial-goal/results', { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  if (full === 'scroll') await page.evaluate(() => document.querySelector('#goalr-funding').scrollIntoView({ block: 'start' }));
  await page.waitForTimeout(1200);
  await page.screenshot({ path: `_pfm_qa/rs-${name}.png` });
  await ctx.close();
}
await browser.close();
