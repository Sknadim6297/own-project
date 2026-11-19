import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const tag = process.argv[2] || 'before';
const url = base + '/tools/pfm/money-planner';
const browser = await chromium.launch();

async function ctxFor(w, h) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  return ctx;
}

for (const [w, h] of [[1440, 900], [390, 844]]) {
  const ctx = await ctxFor(w, h);
  const page = await ctx.newPage();
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  await page.screenshot({ path: `_pfm_qa/mp-${tag}-${w}-1-form.png` });

  // Empty continue -> validation
  await page.locator('[data-pfm-next]').click();
  await page.waitForTimeout(300);
  await page.screenshot({ path: `_pfm_qa/mp-${tag}-${w}-2-validation.png` });

  await page.fill('#pfm-monthly-income', '80000');
  await page.locator('[data-pfm-next]').click();
  await page.waitForTimeout(200);
  await page.screenshot({ path: `_pfm_qa/mp-${tag}-${w}-3-step2.png` });
  await page.fill('#pfm-essential', '50000');
  await page.fill('#pfm-lifestyle', '10000');
  await page.fill('#pfm-emis', '5000');
  await page.locator('[data-pfm-next]').click();
  await page.waitForTimeout(200);
  await page.fill('#pfm-investments', '15000');
  await page.screenshot({ path: `_pfm_qa/mp-${tag}-${w}-4-step3.png` });
  await Promise.all([page.waitForURL(/results$/), page.locator('[data-pfm-submit]').click()]);
  await page.evaluate(() => document.fonts.ready);
  await page.screenshot({ path: `_pfm_qa/mp-${tag}-${w}-5-results.png`, fullPage: true });
  await ctx.close();
}
await browser.close();
