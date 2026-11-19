import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const prefix = process.env.PREFIX || 'before';
const browser = await chromium.launch();
for (const [w, h] of [[1440, 900], [390, 844]]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const page = await ctx.newPage();
  await page.goto(base + '/tools/pfm/financial-goal', { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  await page.screenshot({ path: `_pfm_qa/${prefix}-s1-${w}.png`, fullPage: true });
  await page.locator('[data-pfm-next]').click();
  await page.fill('#pfm-current-cost', '2500000');
  await page.fill('#pfm-years', '8');
  await page.screenshot({ path: `_pfm_qa/${prefix}-s2-${w}.png`, fullPage: true });
  await page.locator('[data-pfm-next]').click();
  await page.screenshot({ path: `_pfm_qa/${prefix}-s3-${w}.png`, fullPage: true });
  await ctx.close();
}
await browser.close();
