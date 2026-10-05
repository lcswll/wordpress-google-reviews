#!/usr/bin/env node
/**
 * Generates the wordpress.org directory assets in .wordpress-org/ (deployed to SVN /assets, not into the plugin):
 *
 *   node scripts/wporg-assets.mjs                                # icons, banners and screenshots
 *   node scripts/wporg-assets.mjs --no-shots                     # only icons and banners
 *   node scripts/wporg-assets.mjs --base http://127.0.0.1:9400   # screenshots from an already running `npm run playground`
 *
 * Icons and banners come from scripts/assets/wporg-brand.html. Screenshots are taken from the real screens in
 * WordPress Playground, connected to the fake Google place (tests/e2e/seed.php). Uses the locally installed Edge.
 * The captions are the "== Screenshots ==" list in wille-reviews/readme.txt (same order).
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chromium } from '@playwright/test';
import { root } from './lib/php.mjs';

const args = process.argv.slice(2);
const out = path.join(root, '.wordpress-org');
const shots = !args.includes('--no-shots');
const external = args.includes('--base') ? args[args.indexOf('--base') + 1] : null;
fs.mkdirSync(out, { recursive: true });
const browser = await chromium.launch(process.env.CI ? {} : { channel: 'msedge' });

// ------------------------------------------------------------------ icons + banners.
const brand = pathToFileURL(path.join(root, 'scripts', 'assets', 'wporg-brand.html')).href;
for (const [file, width, height, asset] of [
	['icon-128x128.png', 128, 128, 'icon'],
	['icon-256x256.png', 256, 256, 'icon'],
	['banner-772x250.png', 772, 250, 'banner'],
	['banner-1544x500.png', 1544, 500, 'banner'],
]) {
	const page = await browser.newPage({ viewport: { width, height } });
	await page.goto(`${brand}?asset=${asset}`);
	await page.screenshot({ path: path.join(out, file) });
	await page.close();
	console.log(`✓ ${file}`);
}

// --------------------------------------------------------------------- screenshots.
if (shots) {
	const port = 9420;
	const base = external || `http://127.0.0.1:${port}`;
	let server = null;
	if (!external) {
		const marker = path.join(root, '.cache', 'e2e-out', 'seeded');
		fs.rmSync(marker, { force: true });
		server = spawn(process.execPath, [path.join(root, 'scripts', 'playground-server.mjs'), '--port', String(port)], { cwd: root, stdio: 'ignore' });
		const deadline = Date.now() + 5 * 60_000;
		while (!fs.existsSync(marker)) {
			if (Date.now() > deadline) throw new Error('Playground did not start.');
			await new Promise((r) => setTimeout(r, 500));
		}
	}

	try {
		const page = await browser.newPage({ viewport: { width: 1280, height: 900 }, deviceScaleFactor: 1 });
		const tidy = async () => {
			await page.addStyleTag({ content: '#wpfooter, .notice, .update-nag, #screen-meta-links, .willerev--floating { display: none !important; } html { scroll-behavior: auto !important; }' });
			await page.evaluate(() => document.activeElement?.blur());
		};
		const shot = async (n, what, locator = null) => {
			await page.waitForTimeout(500);
			if (locator) {
				await locator.evaluate((el) => el.scrollIntoView({ block: 'center' }));
				await page.waitForTimeout(300);
			}
			await page.screenshot({ path: path.join(out, `screenshot-${n}.png`) });
			console.log(`✓ screenshot-${n}.png (${what})`);
		};

		// 1. Design page: layout + style pickers, live preview, shortcode.
		await page.goto(`${base}/wp-admin/admin.php?page=wille-reviews`);
		await page.locator('.willerev-choice', { hasText: 'Slider' }).click();
		await page.locator('.willerev-choice', { hasText: 'Speech bubble' }).click();
		await tidy();
		await shot(1, 'design page');

		// 2. Slider in the dark style, 3. wall in the speech bubble style, 4. badge + social proof (front end).
		await page.goto(`${base}/styles/`);
		await tidy();
		await shot(2, 'slider dark', page.locator('#slider'));
		await shot(3, 'wall bubble', page.locator('#wall'));
		await shot(4, 'badge + social proof', page.locator('.willerev--layout-social'));

		// 5. Settings with guide and status.
		await page.goto(`${base}/wp-admin/admin.php?page=wille-reviews-settings`);
		await page.locator('.willerev-details summary').click();
		await tidy();
		await shot(5, 'settings');
	} finally {
		server?.kill();
	}
}

await browser.close();
