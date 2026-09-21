import { test, expect, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { createHmac } from 'node:crypto';

function code(secret: string) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  const bits = secret.replace(/=+$/, '').split('').map(char => alphabet.indexOf(char.toUpperCase()).toString(2).padStart(5, '0')).join('');
  const key = Buffer.from((bits.match(/.{8}/g) ?? []).map(byte => parseInt(byte, 2)));
  const counter = Buffer.alloc(8); counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
  const hash = createHmac('sha1', key).update(counter).digest();
  const offset = hash[19] & 15;
  return ((hash.readUInt32BE(offset) & 0x7fffffff) % 1000000).toString().padStart(6, '0');
}
async function login(page: Page, email: string, password: string, secret: string | null = null) {
  await page.goto('/login'); await page.getByLabel('E-mailadres', { exact: true }).fill(email);
  await page.getByLabel('Wachtwoord', { exact: true }).fill(password); await page.getByRole('button', { name: 'Inloggen' }).click();
  if (secret) {
    await page.getByLabel('Verificatiecode', { exact: true }).fill(code(secret)); await page.getByRole('button', { name: 'Inloggen', exact: true }).click();
  }
  await expect(page).toHaveURL(/dashboard/);
  if (secret) {
    await page.goto('/settings/security');
    await page.getByLabel('Wachtwoord', { exact: true }).fill(password); await page.getByLabel('Actuele verificatiecode').fill(code(secret));
    await page.getByRole('button', { name: 'Werkomgeving openen', exact: true }).click(); await expect(page).toHaveURL(/dashboard/);
  }
}
test('template, preview, review, enrollment, private sharing and feedback', async ({ page, browser }) => {
  test.setTimeout(150000);
  const fixture = JSON.parse(execFileSync('php', ['tests/fixtures/program-flow.php'], { encoding: 'utf8' }));
  const errors: string[] = []; page.on('pageerror', e => errors.push(e.message));
  await login(page, fixture.users.staff, fixture.password, fixture.secrets.staff);
  await page.goto('/werk/programmas'); await page.getByRole('link', { name: 'Gebruik dit basisprogramma' }).click();
  await expect(page.getByLabel('Titel', { exact: true })).toHaveValue('Meer overzicht in wat je ervaart');
  await page.getByRole('button', { name: 'Bekijk als deelnemer' }).click();
  await expect(page.getByRole('heading', { name: 'Jij bepaalt waar je begint' })).toBeVisible();
  await page.screenshot({ path: 'test-results/program-preview-desktop.png', fullPage: true });
  await page.getByRole('button', { name: 'Verder bewerken' }).click();
  await page.getByRole('button', { name: 'Concept opslaan', exact: true }).click();
  await expect(page).toHaveURL(/werk\/programmas\/\d+$/);
  const programId = page.url().split('/').at(-1);
  await expect(page.getByRole('button', { name: 'Ter beoordeling aanbieden' })).toBeEnabled();
  await page.getByRole('button', { name: 'Ter beoordeling aanbieden' }).click();
  await expect(page.getByText('Concept ingediend. Beoordelaars ontvangen een melding.')).toBeVisible();
  const adminContext = await browser.newContext(); const admin = await adminContext.newPage(); admin.on('pageerror', e => errors.push(e.message));
  await login(admin, fixture.users.admin, fixture.password, fixture.secrets.admin); await admin.goto('/beheer/beoordelingen');
  admin.once('dialog', d => d.accept()); await admin.getByRole('button', { name: 'Publiceer vaste versie' }).click();
  await expect(admin.getByText('Nieuwe educatieve programmaversie gepubliceerd. Dit is geen behandelgoedkeuring.')).toBeVisible();
  const memberContext = await browser.newContext(); const member = await memberContext.newPage();
  member.on('pageerror', e => errors.push(e.message));
  await login(member, fixture.users.member, fixture.password);
  await member.goto(`/programmas/${programId}`); await member.getByRole('button', { name: 'Start dit programma' }).click();
  await expect(member).toHaveURL(/mijn-programmas\/\d+$/);
  await member.getByRole('button', { name: /Wat wil je beter begrijpen/ }).click();
  await member.getByLabel('Wat wil je voor jezelf vastleggen?').fill('Verzonnen browsertest, geen echte cliëntinformatie.');
  await member.getByRole('button', { name: 'Privé opslaan', exact: true }).click();
  await expect(member.getByText('Privé opgeslagen. De maker kan dit antwoord niet lezen.')).toBeVisible();
  await page.goto('/werk/inzendingen'); await expect(page.getByRole('heading', { name: 'Nog geen gedeelde antwoorden.' })).toBeVisible();
  await member.getByRole('checkbox', { name: /Deel alleen dit antwoord/ }).check();
  await member.getByRole('button', { name: 'Opslaan en delen' }).click();
  await expect(member.getByText('Opgeslagen en alleen met de genoemde maker gedeeld.')).toBeVisible();
  await page.reload(); await page.getByRole('link', { name: /Lees gedeeld antwoord/ }).click();
  await page.getByLabel('Jouw reactie').fill('Testreactie: welke vraag wil je meenemen?');
  await page.getByRole('button', { name: 'Reactie opslaan' }).click();
  await expect(page.getByText('Reactie opgeslagen. De deelnemer krijgt een neutrale e-mailmelding.')).toBeVisible();
  await member.reload(); await member.getByRole('button', { name: /Wat wil je beter begrijpen/ }).click();
  await expect(member.getByText('Testreactie: welke vraag wil je meenemen?')).toBeVisible();
  await member.screenshot({ path: 'test-results/program-learner-desktop.png', fullPage: true });
  await member.setViewportSize({ width: 390, height: 844 });
  expect(await member.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  await member.screenshot({ path: 'test-results/program-learner-mobile.png', fullPage: true });
  member.once('dialog', d => d.accept()); await member.getByRole('button', { name: 'Delen intrekken' }).click();
  await expect(member.getByText(/Toegang voor de maker ingetrokken/)).toBeVisible();
  await page.goto('/werk/inzendingen'); await expect(page.getByRole('heading', { name: 'Nog geen gedeelde antwoorden.' })).toBeVisible();
  await member.getByRole('button', { name: 'Pauzeren', exact: true }).click();
  await expect(member.getByRole('button', { name: 'Hervatten', exact: true })).toBeVisible();
  await expect(member.getByRole('button', { name: 'Onderdeel afronden', exact: true })).toBeDisabled();
  expect(errors).toEqual([]);
  await adminContext.close(); await memberContext.close();
});
