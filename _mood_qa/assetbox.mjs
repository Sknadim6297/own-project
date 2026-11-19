import { chromium } from 'playwright';
import fs from 'fs';
const b = await chromium.launch(); const p = await b.newPage();
const out = await p.evaluate(async (b64) => {
  const i = await new Promise((r) => { const x = new Image(); x.onload = () => r(x); x.src = 'data:image/png;base64,' + b64; });
  const c = document.createElement('canvas'); c.width = i.width; c.height = i.height;
  const g = c.getContext('2d'); g.drawImage(i, 0, 0);
  const D = g.getImageData(0, 0, i.width, i.height).data;
  const box = (test) => { let lo = 1e9, hi = -1, t = 1e9, bt = -1; for (let y = 0; y < i.height; y++) for (let x = 0; x < i.width; x++) { const k = (y * i.width + x) * 4; if (test(D[k], D[k + 1], D[k + 2], D[k + 3])) { lo = Math.min(lo, x); hi = Math.max(hi, x); t = Math.min(t, y); bt = Math.max(bt, y); } } return [lo, t, hi, bt]; };
  return {
    size: [i.width, i.height],
    alpha: box((r, g, bb, a) => a > 20),
    colored: box((r, g, bb, a) => a > 60 && Math.max(r, g, bb) - Math.min(r, g, bb) > 35),
    pink: box((r, g, bb, a) => a > 100 && r > 220 && g < 140 && bb > 150),
    corner: Array.from(D.slice(0, 4)),
  };
}, fs.readFileSync('public/frontend/assets/img/Cozy Lavender Portrait with Mug and Plant.png').toString('base64'));
console.log(JSON.stringify(out));
await b.close();