import { chromium } from 'playwright';

const base = 'http://127.0.0.1:8000';
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
const page = await ctx.newPage();
await page.goto(base + '/tools/psychology/mood', { waitUntil: 'networkidle' });
await page.locator('label.is-mood-good').click();
await Promise.all([page.waitForURL(/mood\/emotions/), page.locator('[data-mood-continue]').click()]);
for (const k of ['calm', 'grateful', 'tired']) await page.locator(`label.is-emotion-${k}`).click();
await Promise.all([page.waitForURL(/mood\/reflection/), page.locator('.yk-mood__continue').click()]);
await page.waitForLoadState('networkidle');
await page.evaluate(() => document.fonts.ready);
await page.screenshot({ path: '_mood_qa/rm-top.png' });
await page.evaluate(() => document.querySelector('.yk-reflect__actions').scrollIntoView({ block: 'start' }));
await page.waitForTimeout(300);
await page.screenshot({ path: '_mood_qa/rm-bottom.png' });
await browser.close();
