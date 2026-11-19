import { chromium } from 'playwright';
import fs from 'fs';
const [src, out, gain] = process.argv.slice(2);
const b = await chromium.launch(); const p = await b.newPage();
const data = await p.evaluate(async ({ b64, gain }) => {
  const i = await new Promise((r) => { const x = new Image(); x.onload = () => r(x); x.src = 'data:image/jpeg;base64,' + b64; });
  const c = document.createElement('canvas'); c.width = i.width; c.height = i.height; const g = c.getContext('2d'); g.drawImage(i, 0, 0);
  const d = g.getImageData(0, 0, c.width, c.height);
  for (let k = 0; k < d.data.length; k += 4) for (let j = 0; j < 3; j++) d.data[k + j] = Math.max(0, 255 - (255 - d.data[k + j]) * gain);
  g.putImageData(d, 0, 0); return c.toDataURL('image/png').split(',')[1];
}, { b64: fs.readFileSync(src).toString('base64'), gain: +gain });
fs.writeFileSync(out, Buffer.from(data, 'base64')); await b.close();