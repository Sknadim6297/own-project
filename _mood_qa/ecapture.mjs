import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const dir = path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Z]:)/, '$1');
const tag = process.argv[2] || 'ecur';
const base = 'http://127.0.0.1:8000/tools/psychology/mood';

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1536, height: 1024 }, deviceScaleFactor: 1 });
await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: 'http://127.0.0.1:8000' }]);
const page = await ctx.newPage();
const errors = [];
page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
page.on('pageerror', (e) => errors.push(String(e)));
await page.goto(base, { waitUntil: 'networkidle' });
await page.locator('input[name="mood"][value="very-happy"]').check({ force: true });
await Promise.all([page.waitForURL('**/mood/emotions'), page.locator('[data-mood-continue]').click()]);
await page.waitForLoadState('networkidle');
await page.evaluate(() => document.fonts.ready);
if (process.env.CSS) await page.addStyleTag({ content: process.env.CSS });
for (const key of ['happy', 'excited', 'thoughtful', 'anxious']) {
  await page.locator(`input[name="emotions[]"][value="${key}"]`).check({ force: true });
}
await page.mouse.move(0, 1000);
await page.waitForTimeout(600);
const shot = path.join(dir, `${tag}.png`);
await page.screenshot({ path: shot });

const boxes = await page.evaluate(() => {
  const q = (s) => { const e = document.querySelector(s); if (!e) return null; const r = e.getBoundingClientRect(); return [Math.round(r.x), Math.round(r.y), Math.round(r.width), Math.round(r.height)]; };
  const tw = (s) => {
    const e = document.querySelector(s); if (!e) return null;
    const r = document.createRange(); r.selectNodeContents(e);
    const rects = Array.from(r.getClientRects());
    return [Math.round(Math.min(...rects.map((x) => x.left))), Math.round(rects[0].top), Math.round(Math.max(...rects.map((x) => x.right)) - Math.min(...rects.map((x) => x.left)))];
  };
  return {
    navbar: q('.yk-navbar__bar'), back: q('.yk-mood__back'), progress: q('.yk-mood__progress'), label: q('.yk-mood__progress-label'),
    dot1: q('.yk-mood__steps li:nth-child(1) .yk-mood__step-dot'), dot2: q('.yk-mood__steps li:nth-child(2) .yk-mood__step-dot'), dot4: q('.yk-mood__steps li:nth-child(4) .yk-mood__step-dot'),
    panel: q('.yk-emotion__panel') || q('.yk-mood__panel'), badge: q('.yk-mood__badge'), h1: tw('.yk-mood h1'), lead: tw('.yk-mood__lead'), hint: tw('.yk-mood__hint'),
    legend1: q('.yk-emotion__group legend'), chip1: q('.yk-emotion__chip'), chip5: q('.yk-emotion__chips .yk-emotion__chip:nth-child(5)'),
    chipRow2: q('.yk-emotion__group:nth-of-type(2) .yk-emotion__chip'), chipRow3: q('.yk-emotion__group:nth-of-type(3) .yk-emotion__chip'),
    emoji: q('.yk-emotion__emoji'), check: q('.yk-emotion__check'), count: q('.yk-emotion__count'), clear: q('.yk-emotion__clear'),
    cont: q('.yk-mood__continue'), art: q('.yk-emotion__art'), footer: q('footer'), scrollW: document.documentElement.scrollWidth,
  };
});

const ref = fs.readFileSync(path.join(dir, 'eref.jpg')).toString('base64');
const cur = fs.readFileSync(shot).toString('base64');
const cmp = await ctx.newPage();
const result = await cmp.evaluate(async ({ ref, cur }) => {
  const load = (src) => new Promise((r) => { const i = new Image(); i.onload = () => r(i); i.src = src; });
  const a = await load('data:image/jpeg;base64,' + ref);
  const b = await load('data:image/png;base64,' + cur);
  const W = 1024, H = 682;
  const ctxOf = () => { const c = document.createElement('canvas'); c.width = W; c.height = H; return c.getContext('2d'); };
  const ca = ctxOf(); ca.drawImage(a, 0, 0, W, H);
  const cb = ctxOf(); cb.drawImage(b, 0, 0, W, H);
  const da = ca.getImageData(0, 0, W, H).data, db = cb.getImageData(0, 0, W, H).data;
  const diff = ctxOf(); const dd = diff.createImageData(W, H);
  let sum = 0;
  for (let i = 0; i < da.length; i += 4) {
    const d = (Math.abs(da[i] - db[i]) + Math.abs(da[i + 1] - db[i + 1]) + Math.abs(da[i + 2] - db[i + 2])) / 3;
    sum += d;
    const g = (da[i] + da[i + 1] + da[i + 2]) / 3;
    dd.data[i] = Math.min(255, g * 0.35 + d * 2.5); dd.data[i + 1] = g * 0.35; dd.data[i + 2] = g * 0.35; dd.data[i + 3] = 255;
  }
  diff.putImageData(dd, 0, 0);
  const regions = { nav: [0, 0, 1536, 80], top: [117, 80, 1419, 250], left: [117, 258, 1040, 975], art: [1040, 258, 1419, 975], bg: [0, 250, 117, 1024] };
  const regionDiff = {};
  for (const [k, [x0, y0, x1, y1]] of Object.entries(regions)) {
    let s = 0, n = 0;
    for (let y = Math.round(y0 / 1.5); y < Math.min(H, Math.round(y1 / 1.5)); y++) for (let x = Math.round(x0 / 1.5); x < Math.min(W, Math.round(x1 / 1.5)); x++) {
      const i = (y * W + x) * 4;
      s += (Math.abs(da[i] - db[i]) + Math.abs(da[i + 1] - db[i + 1]) + Math.abs(da[i + 2] - db[i + 2])) / 3; n++;
    }
    regionDiff[k] = +(s / n).toFixed(1);
  }
  const ov = ctxOf(); ov.drawImage(a, 0, 0, W, H); ov.globalAlpha = 0.5; ov.drawImage(b, 0, 0, W, H);
  const side = document.createElement('canvas'); side.width = W * 2; side.height = H;
  const s = side.getContext('2d'); s.drawImage(a, 0, 0, W, H); s.drawImage(b, W, 0, W, H);
  return {
    meanDiff: +(sum / (W * H)).toFixed(2), regionDiff,
    diff: diff.canvas.toDataURL('image/png').split(',')[1],
    overlay: ov.canvas.toDataURL('image/png').split(',')[1],
    side: side.toDataURL('image/png').split(',')[1],
  };
}, { ref, cur });
for (const k of ['diff', 'overlay', 'side']) fs.writeFileSync(path.join(dir, `${tag}-${k}.png`), Buffer.from(result[k], 'base64'));
console.log(`meanDiff=${result.meanDiff} errors=${JSON.stringify(errors)}`);
console.log('regions', JSON.stringify(result.regionDiff));
if (!process.env.QUIET) for (const [k, v] of Object.entries(boxes)) console.log(k.padEnd(10), JSON.stringify(v));
await browser.close();
