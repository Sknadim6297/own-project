import { chromium } from 'playwright';
import fs from 'fs';

const cur = process.argv[2] || '_mood_qa/iter3.png';
const browser = await chromium.launch();
const page = await browser.newPage();
await page.setContent('<body></body>');
const res = await page.evaluate(async ({ ref, cur }) => {
  const load = (src) => new Promise((r) => { const i = new Image(); i.onload = () => r(i); i.src = src; });
  const get = async (src) => {
    const img = await load(src);
    const c = document.createElement('canvas'); c.width = 1536; c.height = 1024;
    const x = c.getContext('2d'); x.drawImage(img, 0, 0, 1536, 1024);
    return x.getImageData(0, 0, 1536, 1024).data;
  };
  const A = await get('data:image/jpeg;base64,' + ref);
  const B = await get('data:image/png;base64,' + cur);
  const feats = {
    yellowEmoji: [[950, 60, 1250, 260], (r, g, b) => r > 235 && g > 185 && b < 90],
    blueEmoji: [[1050, 150, 1300, 300], (r, g, b) => b > 200 && r < 160 && g < 200],
    heart: [[1050, 100, 1300, 260], (r, g, b) => r > 225 && g < 140 && b > 140],
    laptop: [[980, 240, 1320, 390], (r, g, b) => r > 120 && r < 175 && b > 140 && b < 200 && Math.abs(r - g) < 22],
    plant: [[690, 200, 900, 360], (r, g, b) => g > r + 35 && g > b + 25],
    sweater: [[840, 180, 1120, 380], (r, g, b) => r > 130 && r < 190 && b > 180 && g < 130],
    dashesR: [[1180, 180, 1320, 280], (r, g, b) => r > 200 && g < 130 && b > 100],
  };
  const out = {};
  for (const [k, [[x0, y0, x1, y1], f]] of Object.entries(feats)) {
    out[k] = [A, B].map((D) => {
      let mx = 1e9, my = 1e9, Mx = -1, My = -1, n = 0;
      for (let y = y0; y < y1; y++) for (let x = x0; x < x1; x++) {
        const i = (y * 1536 + x) * 4;
        if (f(D[i], D[i + 1], D[i + 2])) { n++; if (x < mx) mx = x; if (x > Mx) Mx = x; if (y < my) my = y; if (y > My) My = y; }
      }
      return n ? `${mx},${my}..${Mx},${My} (${n})` : 'none';
    });
  }
  return out;
}, { ref: fs.readFileSync('_mood_qa/reference.jpg').toString('base64'), cur: fs.readFileSync(cur).toString('base64') });
for (const [k, [a, b]] of Object.entries(res)) console.log(k.padEnd(12), 'ref', a.padEnd(28), 'cur', b);
await browser.close();
