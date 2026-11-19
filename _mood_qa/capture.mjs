import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const dir = path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Z]:)/, '$1');
const url = process.env.URL || 'http://127.0.0.1:8000/tools/psychology/mood';
const tag = process.argv[2] || 'current';

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1536, height: 1024 }, deviceScaleFactor: 1 });
await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: 'http://127.0.0.1:8000' }]);
const page = await ctx.newPage();
const errors = [];
page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
page.on('pageerror', (e) => errors.push(String(e)));
await page.goto(url, { waitUntil: 'networkidle' });
await page.evaluate(() => document.fonts.ready);
if (process.env.CSS) await page.addStyleTag({ content: process.env.CSS });
// Reference shows "Very happy" selected.
const vh = page.locator('input[name="mood"][value="very-happy"]');
if (await vh.count()) { await vh.check({ force: true }); }
await page.mouse.move(0, 1000);
await page.waitForTimeout(700);
const shot = path.join(dir, `${tag}.png`);
await page.screenshot({ path: shot });

const boxes = await page.evaluate(() => {
  const q = (s) => { const e = document.querySelector(s); if (!e) return null; const r = e.getBoundingClientRect(); return [Math.round(r.x), Math.round(r.y), Math.round(r.width), Math.round(r.height)]; };
  const out = {
    header: q('header, .header, nav'),
    back: q('.yk-mood__back'),
    h1: q('.yk-mood__hero h1'),
    intro: q('.yk-mood__intro'),
    perks: q('.yk-mood__perks'),
    art: q('.yk-mood__hero-art'),
    pills: q('.yk-mood__pill--accent'),
    pill2: q('.yk-mood__pills .yk-mood__pill:nth-child(2)'),
    perk1: q('.yk-mood__perks li:nth-child(1)'),
    perk2: q('.yk-mood__perks li:nth-child(2)'),
    perk3: q('.yk-mood__perks li:nth-child(3)'),
    stepLabel: q('.yk-mood__progress-label'),
    step1: q('.yk-mood__steps li:nth-child(1) .yk-mood__step-dot'),
    step4: q('.yk-mood__steps li:nth-child(4) .yk-mood__step-dot'),
    cardH2: q('.yk-mood__heading h2'),
    sun: q('.yk-mood__heading-icon'),
    face: q('.yk-mood__face'),
    radio: q('.yk-mood__radio'),
    jActive: q('.yk-mood__journey li.is-current'),
    jLast: q('.yk-mood__journey li:last-child'),
    spark: q('.yk-mood__spark'),
    cont: q('.yk-mood__continue'),
    footer: q('footer'),
    navbar: q('.yk-navbar__bar'),
    progress: q('.yk-mood__progress'),
    form: q('.yk-mood__panel--select'),
    firstCard: q('.yk-mood__card'),
    card2: q('.yk-mood__options li:nth-child(2) .yk-mood__card'),
    lastCard: q('.yk-mood__options li:last-child .yk-mood__card'),
    aside: q('.yk-mood__aside'),
    side1: q('.yk-mood__side-card'),
    side2: q('.yk-mood__side-card--chart'),
    scrollW: document.documentElement.scrollWidth,
  };
  const tw = (s, ref) => {
    const e = document.querySelector(s); if (!e) return null;
    const r = document.createRange(); r.selectNodeContents(e);
    const rects = Array.from(r.getClientRects());
    const w = Math.round(Math.max(...rects.map((x) => x.right)) - Math.min(...rects.map((x) => x.left)));
    return `${w} (ref ${ref}) top=${Math.round(rects[0].top)}`;
  };
  out.text = {
    h1: tw('.yk-mood__hero h1', 394),
    intro: tw('.yk-mood__intro', 475),
    h2: tw('.yk-mood__heading h2', 391),
    lead: tw('.yk-mood__heading .yk-mood__lead', 500),
    name: tw('.yk-mood__name', 93),
    sideH: tw('.yk-mood__side-card h2', 191),
    sideLead: tw('.yk-mood__side-lead', 225),
    perk1: tw('.yk-mood__perks li:nth-child(1)', 87),
    stepLbl: tw('.yk-mood__progress-label', 0),
    jStrong: tw('.yk-mood__journey strong', 0),
  };
  return out;
});

// Compare against reference (1024x682) inside the browser via canvas.
const ref = fs.readFileSync(path.join(dir, 'reference.jpg')).toString('base64');
const cur = fs.readFileSync(shot).toString('base64');
const cmp = await ctx.newPage();
await cmp.setContent('<canvas id=c></canvas>');
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
  let sum = 0, bad = 0;
  for (let i = 0; i < da.length; i += 4) {
    const d = (Math.abs(da[i] - db[i]) + Math.abs(da[i + 1] - db[i + 1]) + Math.abs(da[i + 2] - db[i + 2])) / 3;
    sum += d; if (d > 40) bad++;
    const g = (da[i] + da[i + 1] + da[i + 2]) / 3;
    dd.data[i] = Math.min(255, g * 0.35 + d * 2.5); dd.data[i + 1] = g * 0.35; dd.data[i + 2] = g * 0.35; dd.data[i + 3] = 255;
  }
  diff.putImageData(dd, 0, 0);
  const regions = { nav: [0, 0, 1536, 69], heroText: [150, 69, 700, 383], heroArt: [700, 69, 1388, 383], progress: [150, 383, 1388, 477], card: [149, 503, 959, 1001], side: [988, 501, 1388, 1001], bgLeft: [0, 383, 149, 1024], bgRight: [1388, 69, 1536, 1024] };
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
    meanDiff: +(sum / (W * H)).toFixed(2),
    regionDiff,
    pctOver40: +((bad / (W * H)) * 100).toFixed(2),
    diff: diff.canvas.toDataURL('image/png').split(',')[1],
    overlay: ov.canvas.toDataURL('image/png').split(',')[1],
    side: side.toDataURL('image/png').split(',')[1],
  };
}, { ref, cur });
for (const k of ['diff', 'overlay', 'side']) fs.writeFileSync(path.join(dir, `${tag}-${k}.png`), Buffer.from(result[k], 'base64'));
console.log(`meanDiff=${result.meanDiff} pctOver40=${result.pctOver40}% errors=${JSON.stringify(errors)}`);
console.log('regions', JSON.stringify(result.regionDiff));
if (process.env.QUIET) { await browser.close(); process.exit(0); }
for (const [k, v] of Object.entries(boxes)) console.log(k.padEnd(10), JSON.stringify(v));
await browser.close();
