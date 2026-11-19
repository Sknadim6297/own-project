import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const tag = process.argv[2] || 'before';
const browser = await chromium.launch();
for (const [w, h] of [[1440, 900], [768, 1024], [390, 844]]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const page = await ctx.newPage();
  await page.goto(base + '/tools/pfm', { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  await page.screenshot({ path: `_pfm_qa/hub-${tag}-${w}.png`, fullPage: w === 390 });
  await ctx.close();
}
await browser.close();
