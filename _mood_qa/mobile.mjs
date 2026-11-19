import { chromium } from 'playwright';
import fs from 'fs';

const tag = process.argv[2] || 'm';
const width = +(process.argv[3] || 390);
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true });
await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: 'http://127.0.0.1:8000' }]);
const page = await ctx.newPage();
const errors = [];
page.on('pageerror', (e) => errors.push(String(e)));
await page.goto('http://127.0.0.1:8000/tools/psychology/mood', { waitUntil: 'networkidle' });
await page.evaluate(() => document.fonts.ready);
await page.locator('label.is-mood-very-happy').click();
await page.waitForTimeout(600);
await page.screenshot({ path: `_mood_qa/${tag}-full.png`, fullPage: true });
const m = await page.evaluate(() => {
  const q = (s) => { const e = document.querySelector(s); if (!e) return null; const r = e.getBoundingClientRect(); return [Math.round(r.x), Math.round(r.y + scrollY), Math.round(r.width), Math.round(r.height)]; };
  const perks = [...document.querySelectorAll('.yk-mood__perks li')].map((e) => Math.round(e.getBoundingClientRect().top));
  return {
    scrollW: document.documentElement.scrollWidth, header: q('.yk-navbar'), back: q('.yk-mood__back'), pills: q('.yk-mood__pills'), h1: q('.yk-mood__hero h1'),
    intro: q('.yk-mood__intro'), art: q('.yk-mood__hero-visual'), perks: q('.yk-mood__perks'), perkRows: new Set(perks).size,
    progress: q('.yk-mood__progress'), label: q('.yk-mood__progress-label'), steps: q('.yk-mood__steps'),
    card: q('.yk-mood__panel--select'), h2: q('.yk-mood__heading h2'), row1: q('.yk-mood__card'),
    clipped: [...document.querySelectorAll('.yk-mood__perks li, .yk-mood__step-label, .yk-mood__progress-label, .yk-mood h2')].filter((e) => e.scrollWidth > e.clientWidth + 1).length,
  };
});
console.log(JSON.stringify(m));
if (errors.length) console.log('errors', errors);
await browser.close();
