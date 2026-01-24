import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

test('verify dashboards and modals', async ({ page }) => {
  const htmlContent = fs.readFileSync('index.html', 'utf8');
  await page.setContent(htmlContent);

  // 1. Check breakdown modal readability (visibility of text-muted)
  await page.evaluate(() => {
    // Mock user and open breakdown modal
    (window as any).curU = { id: '1', name: 'Test User' };
    (window as any).mBreakdown.show();
  });
  await page.waitForTimeout(500);
  await page.screenshot({ path: 'verification/breakdown_modal.png' });

  // 2. Check manual entry date format
  await page.evaluate(() => {
    (window as any).openManual();
  });
  await page.waitForTimeout(500);
  const mDateValue = await page.$eval('#mDate', (el: HTMLInputElement) => el.value);
  console.log('Manual Date Value:', mDateValue);
  await page.screenshot({ path: 'verification/manual_entry.png' });

  // 3. Check admin dash cards
  await page.evaluate(() => {
    (window as any).document.getElementById('viewAdmin').classList.remove('hidden');
    (window as any).document.getElementById('dash').classList.remove('hidden');
  });
  await page.screenshot({ path: 'verification/admin_dash_updated.png' });
});
