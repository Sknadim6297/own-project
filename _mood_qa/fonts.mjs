import { chromium } from 'playwright';

const fams = ['DM Sans', 'Poppins', 'Plus Jakarta Sans', 'Outfit', 'Manrope', 'Figtree', 'Urbanist', 'Lexend', 'Nunito Sans', 'Inter'];
const q = fams.map((f) => 'family=' + f.replace(/ /g, '+') + ':wght@500;600;700;800').join('&');
const browser = await chromium.launch();
const page = await browser.newPage();
await page.setContent(`<link href="https://fonts.googleapis.com/css2?${q}&display=block" rel="stylesheet"><body></body>`);
await page.waitForTimeout(2500);
const out = await page.evaluate(async (fams) => {
  // Reference (1536 scale): h1 "Daily Mood Check" width 394 / cap 32.5;
  // card h2 "How are you feeling today?" width 391 / cap 25.8; row "Very happy" width 93.
  const tests = [
    ['Daily Mood Check', 800, 394, 32.5],
    ['How are you feeling today?', 700, 391, 25.8],
  ];
  const res = {};
  for (const f of fams) {
    await document.fonts.load(`700 40px "${f}"`);
    await document.fonts.load(`800 40px "${f}"`);
    const c = document.createElement('canvas').getContext('2d');
    res[f] = tests.map(([t, w, refW, refCap]) => {
      c.font = `${w} 100px "${f}"`;
      const m = c.measureText(t);
      const capM = c.measureText('H');
      const cap = capM.actualBoundingBoxAscent / 100;
      const width = m.width / 100;
      const sizeByCap = refCap / cap;
      return { sizeByWidth: +(refW / width).toFixed(1), sizeByCap: +sizeByCap.toFixed(1), ratio: +((refW / width) / sizeByCap).toFixed(3) };
    });
  }
  return res;
}, fams);
for (const [f, r] of Object.entries(out)) console.log(f.padEnd(18), JSON.stringify(r));
await browser.close();
