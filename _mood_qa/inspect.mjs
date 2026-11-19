import { chromium } from 'playwright';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1536, height: 1024 } });
await page.goto('http://127.0.0.1:8000/tools/psychology/mood', { waitUntil: 'networkidle' });
const info = await page.evaluate((sels) => sels.map((s) => {
  const e = document.querySelector(s);
  if (!e) return [s, null];
  const c = getComputedStyle(e);
  return [s, { h: e.getBoundingClientRect().height, minH: c.minHeight, pad: c.padding, gap: c.rowGap, margin: c.margin, display: c.display }];
}), process.argv.slice(2));
for (const [s, v] of info) console.log(s.padEnd(40), JSON.stringify(v));
await browser.close();
