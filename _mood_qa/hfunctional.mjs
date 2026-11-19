import { chromium } from 'playwright';

const pages = { full: 'http://127.0.0.1:8000/_qa_history.html', empty: 'http://127.0.0.1:8000/_qa_history_empty.html' };
const results = [];
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
const browser = await chromium.launch();

const viewports = [[1536, 1024], [1440, 900], [1280, 800], [1024, 768], [768, 1024], [390, 844], [375, 812]];
for (const [kind, url] of Object.entries(pages)) {
  for (const [w, h] of viewports) {
    if (kind === 'empty' && ![1536, 390].includes(w)) continue;
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: w < 500 ? 2 : 1 });
    await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url }]);
    const errors = [];
    const page = await ctx.newPage();
    page.on('pageerror', (e) => errors.push(String(e)));
    page.on('console', (m) => { if (m.type() === 'error' && !/favicon/.test(m.text())) errors.push(m.text()); });
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(500);
    const m = await page.evaluate(() => {
      const box = (s) => document.querySelector(s)?.getBoundingClientRect();
      const clipped = [...document.querySelectorAll('.yk-mood h1, .yk-mood h2, .yk-history__chip, .yk-history__top time, .yk-history__pages a, .yk-history__page-label, .yk-mood__continue, .yk-history__legend li, .yk-history__numbers dd')]
        .filter((e) => e.scrollWidth > e.clientWidth + 1).map((e) => e.textContent.trim().slice(0, 20));
      const panel = box('.yk-history__panel');
      const outside = [...document.querySelectorAll('.yk-history__card, .yk-history__chip, .yk-history__note')].filter((e) => {
        const r = e.getBoundingClientRect();
        return r.right > panel.right + 1 || r.left < panel.left - 1;
      }).length;
      const targets = [...document.querySelectorAll('.yk-history__pages a, .yk-mood__continue, .yk-complete__links a, .yk-mood__back')].map((e) => e.getBoundingClientRect().height);
      return {
        scrollW: document.documentElement.scrollWidth, clientW: document.documentElement.clientWidth,
        clipped, outside, cards: document.querySelectorAll('.yk-history__card').length,
        minTarget: Math.round(Math.min(...targets)),
        footerTop: Math.round(box('footer').top + scrollY),
        contentBottom: Math.round(Math.max(box('.yk-complete__links').bottom, box('.yk-mood__aside').bottom) + scrollY),
      };
    });
    const tag = `${kind} ${w}x${h}`;
    ok(`${tag} no horizontal overflow`, m.scrollW <= m.clientW, `${m.scrollW}/${m.clientW}`);
    ok(`${tag} no clipped text`, m.clipped.length === 0, JSON.stringify(m.clipped));
    ok(`${tag} entries stay inside the card`, m.outside === 0, `${m.outside} (cards=${m.cards})`);
    ok(`${tag} touch-friendly links (>=24px back, >=44px buttons)`, m.minTarget >= 24, String(m.minTarget));
    ok(`${tag} footer follows content in normal flow`, m.footerTop >= m.contentBottom, `content ends ${m.contentBottom}, footer ${m.footerTop}`);
    ok(`${tag} no console errors`, errors.length === 0, errors.join(' | '));
    await page.screenshot({ path: `_mood_qa/hvp-${kind}-${w}x${h}.png`, fullPage: w >= 500 && w < 1100 });
    await ctx.close();
  }
}

console.log(results.join('\n'));
await browser.close();
