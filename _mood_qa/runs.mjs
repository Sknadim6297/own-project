// Usage: node runs.mjs <image> <scale> h|v <coord> [from] [to] [threshold]
// Prints color runs along a horizontal (h, y=coord) or vertical (v, x=coord) line, in scaled coordinates.
import { chromium } from 'playwright';
import fs from 'fs';

const [img, scaleArg, dirn, coordArg, fromArg, toArg, thrArg] = process.argv.slice(2);
const scale = +scaleArg, coord = +coordArg, thr = +(thrArg || 18);
const browser = await chromium.launch();
const page = await browser.newPage();
const b64 = fs.readFileSync(img).toString('base64');
const mime = img.endsWith('.png') ? 'image/png' : 'image/jpeg';
const out = await page.evaluate(async ({ b64, mime, scale, dirn, coord, from, to, thr }) => {
  const i = await new Promise((r) => { const x = new Image(); x.onload = () => r(x); x.src = `data:${mime};base64,${b64}`; });
  const W = Math.round(i.width * scale), H = Math.round(i.height * scale);
  const c = document.createElement('canvas'); c.width = W; c.height = H;
  const g = c.getContext('2d'); g.imageSmoothingQuality = 'high'; g.drawImage(i, 0, 0, W, H);
  const len = dirn === 'h' ? W : H;
  const a = from ?? 0, z = to ?? len;
  const px = (p) => { const d = dirn === 'h' ? g.getImageData(p, coord, 1, 1).data : g.getImageData(coord, p, 1, 1).data; return [d[0], d[1], d[2]]; };
  const runs = []; let start = a, ref = px(a);
  for (let p = a + 1; p < z; p++) {
    const c2 = px(p);
    const d = Math.max(Math.abs(c2[0] - ref[0]), Math.abs(c2[1] - ref[1]), Math.abs(c2[2] - ref[2]));
    if (d > thr) { runs.push(`${start}-${p - 1} #${ref.map((v) => v.toString(16).padStart(2, '0')).join('')}`); start = p; ref = c2; }
  }
  runs.push(`${start}-${z - 1} #${ref.map((v) => v.toString(16).padStart(2, '0')).join('')}`);
  return runs;
}, { b64, mime, scale, dirn, coord, from: fromArg ? +fromArg : undefined, to: toArg ? +toArg : undefined, thr });
console.log(out.join('\n'));
await browser.close();
