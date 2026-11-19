// Finds the DM Sans font size whose rendered ink width matches a target width.
import { chromium } from 'playwright';

const items = [
  ['FREE TOOL', 700, '0.06em', 83, 'upper'],
  ['What emotions are you feeling?', 800, '-0.02em', 563],
  ['What emotions are you feeling?', 700, '-0.02em', 563],
  ['Choose up to 6 emotions that describe how you feel right now.', 400, '0', 600],
  ['Optional — pick what fits. You can continue with none selected.', 400, '0', 537],
  ['Neutral', 700, '0', 74],
  ['Difficult', 700, '0', 81],
  ['Calm', 500, '0', 43],
  ['Happy', 500, '0', 55],
  ['Calm', 600, '0', 43],
  ['Happy', 600, '0', 55],
  ['3 of 6 selected', 700, '0', 131],
  ['Clear all', 600, '0', 60],
  ['Continue', 700, '0', 99],
  ['Back', 700, '0', 40],
  ['STEP 2 OF 4', 800, '0.02em', 115],
  ['Mood', 500, '0', 49],
  ['Emotions', 700, '0', 88],
  ['Write', 500, '0', 46],
];
const b = await chromium.launch();
const p = await b.newPage();
await p.goto('http://127.0.0.1:8000/tools/psychology/mood', { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
const res = await p.evaluate((items) => items.map(([t, w, ls, target, mode]) => {
  const s = document.createElement('span');
  s.style.cssText = `position:absolute;left:0;top:0;white-space:nowrap;font-family:"DM Sans";font-weight:${w};letter-spacing:${ls};${mode === 'upper' ? 'text-transform:uppercase;' : ''}`;
  s.textContent = t; document.body.appendChild(s);
  const width = (px) => { s.style.fontSize = px + 'px'; const r = document.createRange(); r.selectNodeContents(s); return r.getBoundingClientRect().width; };
  let lo = 8, hi = 60;
  for (let k = 0; k < 30; k++) { const m = (lo + hi) / 2; if (width(m) < target) lo = m; else hi = m; }
  s.remove();
  return `${t.slice(0, 28).padEnd(28)} w${w} ls${ls} -> ${lo.toFixed(2)}px`;
}), items);
console.log(res.join('\n'));
await b.close();
