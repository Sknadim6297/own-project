import { chromium } from 'playwright';

const url = 'http://127.0.0.1:8000/_qa_complete.html';
const tagSuffix = process.env.SUFFIX || '';
const results = [];
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
const browser = await chromium.launch();

const viewports = [[1536, 1024], [1440, 900], [1280, 800], [1024, 768], [768, 1024], [390, 844], [375, 812]];
for (const [w, h] of viewports) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: w < 500 ? 2 : 1 });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url }]);
  const errors = [];
  const page = await ctx.newPage();
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('console', (m) => { if (m.type() === 'error' && !/favicon|404/.test(m.text())) errors.push(m.text()); });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(700);
  const m = await page.evaluate(() => {
    const box = (s) => document.querySelector(s)?.getBoundingClientRect();
    const clipped = [...document.querySelectorAll('.yk-mood h1, .yk-mood h2, .yk-complete__chip, .yk-complete__mood, .yk-complete__when, .yk-mood__continue, .yk-complete__history, .yk-psych-connect__cta, .yk-mood__step-label, .yk-mood__progress-label')]
      .filter((e) => e.scrollWidth > e.clientWidth + 1).map((e) => e.textContent.trim().slice(0, 20));
    const panel = box('.yk-complete__panel');
    const outside = [...document.querySelectorAll('.yk-complete__block, .yk-complete__chip')].filter((e) => {
      const r = e.getBoundingClientRect();
      return r.right > panel.right + 1 || r.left < panel.left - 1;
    }).length;
    const targets = [...document.querySelectorAll('.yk-complete__actions a, .yk-complete__links a, .yk-psych-connect__cta')].map((e) => e.getBoundingClientRect().height);
    return {
      scrollW: document.documentElement.scrollWidth, clientW: document.documentElement.clientWidth,
      clipped, outside,
      minTarget: Math.round(Math.min(...targets)),
      footerTop: Math.round(box('footer').top + scrollY),
      contentBottom: Math.round(Math.max(box('.yk-complete__links').bottom, box('.yk-mood__aside').bottom) + scrollY),
      progress: document.querySelector('.yk-mood__progress-label').textContent.trim().replace(/\s+/g, ' '),
    };
  });
  const tag = `${w}x${h}`;
  ok(`${tag} shows Step 4 of 4`, m.progress.startsWith('Step 4 of 4'), m.progress);
  ok(`${tag} no horizontal overflow`, m.scrollW <= m.clientW, `${m.scrollW}/${m.clientW}`);
  ok(`${tag} no clipped text`, m.clipped.length === 0, JSON.stringify(m.clipped));
  ok(`${tag} summary stays inside the card`, m.outside === 0, String(m.outside));
  ok(`${tag} touch-friendly links (>=44px)`, m.minTarget >= 44, String(m.minTarget));
  ok(`${tag} footer follows content in normal flow`, m.footerTop >= m.contentBottom, `content ends ${m.contentBottom}, footer ${m.footerTop}`);
  ok(`${tag} no console errors`, errors.length === 0, errors.join(' | '));
  await page.screenshot({ path: `_mood_qa/cvp-${tag}${tagSuffix}.png`, fullPage: w < 1100 && w >= 500 });
  await ctx.close();
}

console.log(results.join('\n'));
await browser.close();
