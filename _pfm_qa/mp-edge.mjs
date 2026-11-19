import { chromium } from 'playwright';
const base = 'http://127.0.0.1:8000';
const browser = await chromium.launch();
const cases = [
  ['huge 1e400', { inc: '1e400', ess: '0', lif: '0', emi: '0', inv: '0' }],
  ['huge 1e30', { inc: '1e30', ess: '5', lif: '0', emi: '0', inv: '0' }],
  ['negative', { inc: '1000', ess: '-5', lif: '0', emi: '0', inv: '0' }],
  ['overspend', { inc: '10000', ess: '9000', lif: '4000', emi: '0', inv: '0' }],
  ['zero optional', { inc: '10000', ess: '0', lif: '0', emi: '0', inv: '0' }],
];
for (const [name, v] of cases) {
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 } });
  await ctx.addCookies([{ name: 'cb-enabled', value: 'accepted', url: base }]);
  const page = await ctx.newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(e.message));
  await page.goto(base + '/tools/pfm/money-planner', { waitUntil: 'networkidle' });
  await page.evaluate(([a]) => {
    const set = (id, val) => { const el = document.getElementById(id); if (el) { el.removeAttribute('min'); el.removeAttribute('max'); el.removeAttribute('step'); el.type = 'text'; el.value = val; } };
    set('pfm-monthly-income', a.inc); set('pfm-essential', a.ess); set('pfm-lifestyle', a.lif); set('pfm-emis', a.emi); set('pfm-investments', a.inv);
    const o = document.getElementById('pfm-other'); if (o) { o.type = 'text'; o.value = '0'; }
    const f = document.getElementById('pfm-monthly-income').form; f.noValidate = true; f.submit();
  }, [v]);
  await page.waitForLoadState('networkidle');
  const status = page.url().replace(base, '');
  const text = (await page.locator('body').innerText()).replace(/\s+/g, ' ').slice(0, 260);
  console.log(`[${name}] -> ${status} | errs=${errs.length}\n   ${text}`);
  await page.screenshot({ path: `_pfm_qa/mp-edge-${name.replace(/\W+/g, '_')}.png` });
  await ctx.close();
}
await browser.close();


