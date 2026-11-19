import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const results = [];
const ok = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`);
const browser = await chromium.launch();

async function toReflection(ctx, emotions = ['calm', 'grateful', 'tired']) {
  const page = await ctx.newPage();
  await page.goto(base + '/tools/psychology/mood', { waitUntil: 'networkidle' });
  await page.locator('label.is-mood-good').click();
  await Promise.all([page.waitForURL(/mood\/emotions/), page.locator('[data-mood-continue]').click()]);
  await page.waitForLoadState('networkidle');
  for (const k of emotions) await page.locator(`label.is-emotion-${k}`).click();
  await Promise.all([page.waitForURL(/mood\/reflection/), page.locator('.yk-mood__continue').click()]);
  await page.waitForLoadState('networkidle');
  await page.evaluate(() => document.fonts.ready);
  return page;
}

// ---- Functional flow ----
{
  const ctx = await browser.newContext({ viewport: { width: 1536, height: 1024 } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const errors = [];
  ctx.on('page', (p) => { p.on('pageerror', (e) => errors.push(String(e))); p.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); }); });
  const page = await toReflection(ctx);

  const step = await page.evaluate(() => document.querySelector('.yk-mood__progress-label').textContent.trim().replace(/\s+/g, ' '));
  ok('Shows Step 3 of 4', step.startsWith('Step 3 of 4'), step);

  const picked = await page.evaluate(() => ({
    mood: document.querySelector('.yk-reflect__picked-text strong')?.textContent.trim(),
    tags: [...document.querySelectorAll('.yk-reflect__tag')].map((t) => t.textContent.trim()),
  }));
  ok('Sidebar shows the mood and emotions picked earlier', picked.mood === 'Good' && picked.tags.length === 3, JSON.stringify(picked));

  const input = page.locator('[data-reflection-input]');
  await page.locator('[data-reflection-prompt]').first().click();
  let v = await input.inputValue();
  ok('Prompt fills an empty note', v === 'Something good happened today', v);
  ok('Counter updates', (await page.locator('[data-reflection-count]').textContent()).trim() === `${v.length} / 1,000`);

  await page.locator('[data-reflection-prompt]').nth(1).click();
  const confirmShown = await page.locator('[data-prompt-confirm]').isVisible();
  ok('Second prompt asks before adding', confirmShown);
  await page.locator('[data-prompt-add]').click();
  v = await input.inputValue();
  ok('Add prompt appends on a new line', v === 'Something good happened today\nSomething is bothering me', JSON.stringify(v));
  ok('Confirm box hides after adding', !(await page.locator('[data-prompt-confirm]').isVisible()));

  await page.locator('[data-reflection-prompt]').nth(2).click();
  await page.locator('[data-prompt-cancel]').click();
  ok('Keep my writing leaves the note unchanged', (await input.inputValue()) === v);

  await input.focus();
  const ring = await page.evaluate(() => getComputedStyle(document.querySelector('.yk-reflect__field')).boxShadow);
  ok('Writing box shows a focus ring', ring !== 'none', ring);

  await page.locator('.yk-reflect__edit').nth(1).click();
  await page.waitForURL(/mood\/emotions/);
  const kept = await page.evaluate(() => [...document.querySelectorAll('[data-emotion-chip].is-selected input')].map((i) => i.value).join());
  ok('Change emotions link opens step 2 with selections kept', kept === 'grateful,calm,tired' || kept.split(',').sort().join() === 'calm,grateful,tired', kept);
  await Promise.all([page.waitForURL(/mood\/reflection/), page.locator('.yk-mood__continue').click()]);

  await Promise.all([page.waitForURL(/mood\/emotions/), page.locator('.yk-mood__back').click()]);
  ok('Back returns to step 2', /mood\/emotions/.test(page.url()));
  await Promise.all([page.waitForURL(/mood\/reflection/), page.locator('.yk-mood__continue').click()]);

  await Promise.all([page.waitForURL((u) => !/mood\/reflection$/.test(u.pathname)), page.locator('.yk-reflect__skip').click()]);
  ok('Skip submits the step', !/mood\/reflection$/.test(new URL(page.url()).pathname), page.url());
  ok('No console errors during the flow', errors.length === 0, errors.join(' | '));
  await ctx.close();
}

// ---- Responsive checks ----
const viewports = [[1536, 1024], [1440, 900], [1280, 800], [1024, 768], [768, 1024], [390, 844], [375, 812]];
for (const [w, h] of viewports) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const errors = [];
  ctx.on('page', (p) => { p.on('pageerror', (e) => errors.push(String(e))); p.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); }); });
  const page = await toReflection(ctx, ['happy', 'calm', 'thoughtful', 'tired', 'worried', 'anxious']);
  await page.mouse.move(0, 0);
  await page.waitForTimeout(400);
  const m = await page.evaluate(() => {
    const box = (s) => document.querySelector(s)?.getBoundingClientRect();
    const clipped = [...document.querySelectorAll('.yk-mood h1, .yk-mood h2, .yk-reflect__label, .yk-reflect__prompt-text, .yk-reflect__meta span, .yk-reflect__tag, .yk-mood__continue, .yk-reflect__skip, .yk-mood__step-label, .yk-mood__progress-label')]
      .filter((e) => e.scrollWidth > e.clientWidth + 1).map((e) => e.textContent.trim().slice(0, 20));
    const panel = box('.yk-reflect__panel');
    const inside = [...document.querySelectorAll('.yk-reflect__prompt, .yk-reflect__field')].filter((e) => {
      const r = e.getBoundingClientRect();
      return r.right > panel.right + 1 || r.left < panel.left - 1;
    }).length;
    const targets = [...document.querySelectorAll('.yk-reflect__prompt, .yk-mood__continue, .yk-reflect__skip')].map((e) => e.getBoundingClientRect().height);
    return {
      scrollW: document.documentElement.scrollWidth, clientW: document.documentElement.clientWidth,
      clipped, outside: inside,
      minTarget: Math.round(Math.min(...targets)),
      footerTop: Math.round(box('footer').top + scrollY),
      contentBottom: Math.round(Math.max(box('.yk-reflect__actions').bottom, box('.yk-mood__aside').bottom) + scrollY),
    };
  });
  const tag = `${w}x${h}`;
  ok(`${tag} no horizontal overflow`, m.scrollW <= m.clientW, `${m.scrollW}/${m.clientW}`);
  ok(`${tag} no clipped text`, m.clipped.length === 0, JSON.stringify(m.clipped));
  ok(`${tag} prompts and writing box stay inside the card`, m.outside === 0, String(m.outside));
  ok(`${tag} touch-friendly buttons (>=44px)`, m.minTarget >= 44, String(m.minTarget));
  ok(`${tag} footer follows content in normal flow`, m.footerTop >= m.contentBottom, `content ends ${m.contentBottom}, footer ${m.footerTop}`);
  ok(`${tag} no console errors`, errors.length === 0, errors.join(' | '));
  await page.screenshot({ path: `_mood_qa/rvp-${tag}.png`, fullPage: w < 1100 });
  await ctx.close();
}

console.log(results.join('\n'));
await browser.close();
