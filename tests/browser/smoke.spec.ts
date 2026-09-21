import { test, expect } from '@playwright/test';

test('homepage, empty library and mobile layout', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  await page.goto('/');
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Jouw eigen richting');
  await page.screenshot({ path: 'test-results/home-desktop.png', fullPage: true });
  await page.getByRole('link', { name: 'Ontdek programma’s', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Hier groeit het programma-aanbod.' })).toBeVisible();
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');
  await expect(page.getByRole('link', { name: 'Begin hier' })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  await page.screenshot({ path: 'test-results/home-mobile.png', fullPage: true });
  expect(errors).toEqual([]);
});

test('registration works in the browser and requires email verification', async ({ page }) => {
  await page.goto('/register');
  await page.getByLabel('Naam of schermnaam').fill('Browser test');
  await page.getByLabel('E-mailadres', { exact: true }).fill(`browser-${Date.now()}@example.test`);
  await page.getByLabel('Wachtwoord · minimaal 12 tekens').fill('TestOnlyLongPassword123!');
  await page.getByLabel('Herhaal je wachtwoord').fill('TestOnlyLongPassword123!');
  await page.getByRole('button', { name: 'Account maken' }).click();
  await expect(page.getByRole('heading', { name: 'Nog één stap.' })).toBeVisible();
  await expect(page).toHaveURL(/email\/verify/);
});
