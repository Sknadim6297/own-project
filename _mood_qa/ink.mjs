import { chromium } from 'playwright';
import fs from 'fs';
// Print first/last x of "ink" per horizontal band [y0,y1] in the reference at 1536 scale.
const [img, spec] = process.argv.slice(2);
const bands = JSON.parse(spec);
const b = await chromium.launch(); const p = await b.newPage();
const out = await p.evaluate(async ({ b64, bands }) => {
  const i = await new Promise((r) => { const x = new Image(); x.onload = () => r(x); x.src = 'data:image/png;base64,' + b64; });
  const c = document.createElement('canvas'); c.width = 1536; c.height = 1024;
  const g = c.getContext('2d'); g.imageSmoothingQuality = 'high'; g.drawImage(i, 0, 0, 1536, 1024);
  const D = g.getImageData(0, 0, 1536, 1024).data;
  const res = {};
  for (const [name, [x0, x1, y0, y1, thr, mode]] of Object.entries(bands)) {
    let lo = 1e9, hi = -1, top = 1e9, bot = -1;
    for (let y = y0; y <= y1; y++) for (let x = x0; x <= x1; x++) {
      const k = (y * 1536 + x) * 4, r = D[k], gg = D[k + 1], bb = D[k + 2];
      const hit = mode === 'color' ? Math.max(r, gg, bb) - Math.min(r, gg, bb) > thr : (r + gg + bb) / 3 < thr;
      if (hit) { lo = Math.min(lo, x); hi = Math.max(hi, x); top = Math.min(top, y); bot = Math.max(bot, y); }
    }
    res[name] = `x ${lo}-${hi} (w ${hi - lo + 1}) y ${top}-${bot}`;
  }
  return res;
}, { b64: fs.readFileSync(img).toString('base64'), bands });
for (const [k, v] of Object.entries(out)) console.log(k.padEnd(10), v);
await b.close();