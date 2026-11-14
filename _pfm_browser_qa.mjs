import { chromium } from 'playwright';
import fs from 'fs';

const base = 'http://127.0.0.1:8000';
const viewports = [
  { w: 320, h: 640 },
  { w: 375, h: 812 },
  { w: 425, h: 900 },
  { w: 768, h: 1024 },
  { w: 1024, h: 768 },
  { w: 1440, h: 900 },
];

const tools = [
  { key: 'money-planner', path: '/tools/pfm/money-planner', calc: null },
  { key: 'net-worth', path: '/tools/pfm/net-worth', calc: null },
  { key: 'emergency-fund', path: '/tools/pfm/emergency-fund', calc: null },
  { key: 'financial-goal', path: '/tools/pfm/financial-goal', calc: null },
  { key: 'debt-emi', path: '/tools/pfm/debt-emi', calc: null },
  { key: 'retirement', path: '/tools/pfm/retirement', calc: null },
  { key: 'inflation', path: '/tools/pfm/inflation', calc: {
    fields: { current_amount: '10000', inflation_percent: '0', years: '10' },
    expect: ['10,000.00', 'Future cost'],
  }},
  { key: 'savings-investment', path: '/tools/pfm/savings-investment', calc: {
    fields: { initial_amount: '10000', monthly_contribution: '1000', expected_return_percent: '0', years: '1' },
    expect: ['22,000.00'],
  }},
];

async function overflow(page) {
  return page.evaluate(() => {
    const d = document.documentElement;
    const broken = Array.from(document.images)
      .filter((img) => img.complete && img.naturalWidth === 0)
      .map((img) => img.currentSrc || img.src);
    return {
      clientWidth: d.clientWidth,
      scrollWidth: d.scrollWidth,
      overflow: d.scrollWidth > d.clientWidth + 1,
      brokenImages: broken,
      hasWizard: !!document.querySelector('[data-pfm-wizard]'),
      hasChart: !!document.querySelector('.yk-pfm__chart'),
      hasMetrics: !!document.querySelector('.yk-pfm__metrics'),
      title: document.title,
    };
  });
}

async function fillAndSubmit(page, fields) {
  // dismiss cookie if present
  await page.evaluate(() => {
    document.querySelectorAll('p').forEach((p) => {
      if ((p.textContent || '').includes('cookies')) {
        const wrap = p.closest('div');
        if (wrap) wrap.style.display = 'none';
      }
    });
  });

  // if wizard, reveal all steps
  await page.evaluate(() => {
    document.querySelectorAll('[data-pfm-step]').forEach((el) => { el.hidden = false; });
    const submit = document.querySelector('[data-pfm-submit]');
    if (submit) submit.hidden = false;
  });

  for (const [name, value] of Object.entries(fields)) {
    const locator = page.locator(`[name="${name}"]`).first();
    if (await locator.count()) {
      await locator.fill(String(value));
    }
  }

  await Promise.all([
    page.waitForURL(/\/results/),
    page.locator('form[data-pfm-form]').evaluate((form) => form.requestSubmit()),
  ]);
}

const report = { startedAt: new Date().toISOString(), pages: [], guestFlow: {}, authFlow: {}, issues: [] };

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext();
const page = await context.newPage();

// Hub + each tool form across viewports
for (const vp of viewports) {
  await page.setViewportSize({ width: vp.w, height: vp.h });

  await page.goto(`${base}/tools/pfm`, { waitUntil: 'domcontentloaded' });
  const hub = await overflow(page);
  report.pages.push({ path: '/tools/pfm', viewport: vp.w, ...hub });
  if (hub.overflow) report.issues.push(`overflow hub@${vp.w}`);

  for (const tool of tools) {
    await page.goto(`${base}${tool.path}`, { waitUntil: 'domcontentloaded' });
    const info = await overflow(page);
    report.pages.push({ path: tool.path, viewport: vp.w, ...info });
    if (info.overflow) report.issues.push(`overflow ${tool.key}@${vp.w}`);
    if (info.brokenImages.length) report.issues.push(`broken-img ${tool.key}@${vp.w}: ${info.brokenImages.join(',')}`);
  }
}

// Guest calculate inflation + savings at 375
await page.setViewportSize({ width: 375, height: 812 });
for (const tool of tools.filter((t) => t.calc)) {
  await page.goto(`${base}${tool.path}`, { waitUntil: 'domcontentloaded' });
  await fillAndSubmit(page, tool.calc.fields);
  const result = await overflow(page);
  const body = await page.locator('body').innerText();
  const expectsOk = tool.calc.expect.every((needle) => body.includes(needle));
  report.guestFlow[tool.key] = {
    url: page.url(),
    expectsOk,
    hasChart: result.hasChart,
    hasMetrics: result.hasMetrics,
    overflow: result.overflow,
  };
  if (!expectsOk) report.issues.push(`guest-result-mismatch ${tool.key}`);
  if (result.overflow) report.issues.push(`overflow results ${tool.key}@375`);

  // edit & recalculate
  await page.locator('form[action*="recalculate"]').first().evaluate((form) => form.requestSubmit());
  await page.waitForURL(new RegExp(tool.path.replace(/\//g, '\\/') + '$'));
  report.guestFlow[`${tool.key}_recalculate`] = { url: page.url(), ok: page.url().endsWith(tool.path) };

  // save redirects to login
  await page.goto(`${base}${tool.path}`, { waitUntil: 'domcontentloaded' });
  await fillAndSubmit(page, tool.calc.fields);
  await page.locator('form[action*="/save"]').first().evaluate((form) => form.requestSubmit());
  await page.waitForURL(/login/);
  report.guestFlow[`${tool.key}_save_requires_login`] = { ok: page.url().includes('/login') };
}

// Auth history + dashboard
await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
await page.fill('input[type="email"], input[name="email"]', 'qa.customer@yuktione.local');
await page.fill('input[type="password"], input[name="password"]', 'Password123!');
await page.evaluate(() => {
  document.querySelectorAll('input[type="checkbox"]').forEach((c) => {
    c.checked = true;
    c.dispatchEvent(new Event('change', { bubbles: true }));
  });
  const form = document.querySelector('form');
  if (form) form.submit();
});
await page.waitForTimeout(1500);

await page.goto(`${base}/tools/pfm/money-planner/history`, { waitUntil: 'domcontentloaded' });
const history = await overflow(page);
const historyText = await page.locator('body').innerText();
report.authFlow.history = {
  statusOk: !page.url().includes('/login'),
  overflow: history.overflow,
  hasViewResults: historyText.includes('View results'),
  hasContinue: historyText.includes('Continue'),
  hasDelete: historyText.includes('Delete'),
  hasCards: await page.locator('.yk-pfm__history-card').count(),
};

await page.goto(`${base}/user/dashboard`, { waitUntil: 'domcontentloaded' });
const dashText = await page.locator('body').innerText();
report.authFlow.dashboard = {
  statusOk: !page.url().includes('/login'),
  hasSavedTools: dashText.includes('My Saved Tools'),
};

await browser.close();

report.finishedAt = new Date().toISOString();
fs.writeFileSync('_pfm_browser_qa_report.json', JSON.stringify(report, null, 2));
console.log(JSON.stringify({
  pages: report.pages.length,
  issues: report.issues,
  guestFlow: report.guestFlow,
  authFlow: report.authFlow,
}, null, 2));
