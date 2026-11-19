import { chromium } from 'playwright';
const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 375, height: 812 } });
await p.goto('http://127.0.0.1:8000/tools/psychology/mood', { waitUntil: 'networkidle' });
console.log(JSON.stringify(await p.evaluate(() => [...document.querySelectorAll('.yk-mood__perks li, .yk-mood__step-label, .yk-mood__progress-label, .yk-mood h2')].filter(e => e.scrollWidth > e.clientWidth + 1).map(e => [e.className, e.textContent.trim().slice(0,20), e.scrollWidth, e.clientWidth]))));
await b.close();