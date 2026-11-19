import { chromium } from 'playwright';
import fs from 'fs';

// Prints the vertical bands of "ink" (dark or colored pixels) inside a region, in 1536px coordinates.
const cur = process.argv[2];
const regions = JSON.parse(process.argv[3] || process.env.REGIONS);
const browser = await chromium.launch();
const page = await browser.newPage();
const res = await page.evaluate(async ({ ref, cur, regions }) => {
  const load = (src) => new Promise((r) => { const i = new Image(); i.onload = () => r(i); i.src = src; });
  const get = async (src) => {
    const img = await load(src);
    const c = document.createElement('canvas'); c.width = 1536; c.height = 1024;
    const x = c.getContext('2d'); x.imageSmoothingQuality = 'high'; x.drawImage(img, 0, 0, 1536, 1024);
    return x.getImageData(0, 0, 1536, 1024).data;
  };
  const A = await get('data:image/jpeg;base64,' + ref);
  const B = await get('data:image/png;base64,' + cur);
  const out = {};
  for (const [name, [x0, y0, x1, y1, thr, mode]] of Object.entries(regions)) {
    out[name] = [A, B].map((D) => {
      const bands = []; let start = -1;
      for (let y = y0; y < y1; y++) {
        let n = 0;
        for (let x = x0; x < x1; x++) {
          const i = (y * 1536 + x) * 4; const r = D[i], g = D[i + 1], b = D[i + 2];
          const hit = mode === 'color' ? (Math.max(r, g, b) - Math.min(r, g, b) > thr) : (r + g + b) / 3 < thr;
          if (hit) n++;
        }
        if (n >= 2 && start < 0) start = y;
        if (n < 2 && start >= 0) { if (y - start > 2) bands.push(`${start}-${y - 1}`); start = -1; }
      }
      return bands.join(' ');
    });
  }
  return out;
}, { ref: fs.readFileSync(process.env.REF || '_mood_qa/reference.jpg').toString('base64'), cur: fs.readFileSync(cur).toString('base64'), regions });
for (const [k, [a, b]] of Object.entries(res)) { console.log(k); console.log('  ref', a); console.log('  cur', b); }
await browser.close();
