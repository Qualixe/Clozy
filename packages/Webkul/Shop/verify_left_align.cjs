const { chromium } = require('playwright-core');

(async () => {
    const browser = await chromium.launch({ args: ['--no-sandbox'] });
    const page = await (await browser.newContext({ viewport: { width: 700, height: 900 } })).newPage();

    await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
    await page.waitForSelector('text=Unleash Your Boldness', { timeout: 15000 });

    const section = page.locator('.bold-collections').filter({ hasText: 'Unleash Your Boldness' });
    await section.scrollIntoViewIfNeeded();
    await page.waitForTimeout(300);

    const style = await page.locator('.inline-col-content-wrapper').last().evaluate(el => {
        const s = window.getComputedStyle(el);
        return { justifyContent: s.justifyContent, textAlign: s.textAlign };
    });
    console.log('mobile (700px) content wrapper style:', JSON.stringify(style));

    await page.screenshot({ path: __dirname + '/left-align-check.png' });

    await browser.close();
})();
