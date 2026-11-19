import { chromium } from 'playwright';
import fs from 'fs';
// Darkest (lowest luminance) pixel and median of the darkest 10% inside each region of a 1536px image.
const [img, spec] = process.argv.slice(2);
const regions = JSON.parse(spec);
const b = await chromium.launch(); const p = await b.newPage();
const out = await p.evaluate(async ({ b64, regions }) => {
  const i = await new Promise((r) => { const x = new Image(); x.onload = () => r(x); x.src = 'data:image/png;base64,' + b64; });
  const c = document.createElement('canvas'); c.width = 1536; c.height = 1024; const g = c.getContext('2d'); g.drawImage(i, 0, 0, 1536, 1024);
  const hex = (a) => '#' + a.map((v) => Math.round(v).toString(16).padStart(2, '0')).join('');
  const res = {};
  for (const [k, [x0, y0, x1, y1]] of Object.entries(regions)) {
    const d = g.getImageData(x0, y0, x1 - x0, y1 - y0).data; const px = [];
    for (let j = 0; j < d.length; j += 4) px.push([d[j], d[j + 1], d[j + 2]]);
    px.sort((a, b2) => (a[0] + a[1] + a[2]) - (b2[0] + b2[1] + b2[2]));
    const top = px.slice(0, Math.max(1, Math.floor(px.length * 0.04)));
    const avg = [0, 1, 2].map((n) => top.reduce((s, q) => s + q[n], 0) / top.length);
    res[k] = `darkest ${hex(px[0])} avgDark4% ${hex(avg)} median ${hex(px[Math.floor(px.length / 2)])}`;
  }
  return res;
}, { b64: fs.readFileSync(img).toString('base64'), regions });
for (const [k, v] of Object.entries(out)) console.log(k.padEnd(10), v);
await b.close();