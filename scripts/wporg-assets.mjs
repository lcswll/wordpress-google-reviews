#!/usr/bin/env node
/**
 * Generates the wordpress.org directory assets in .wordpress-org/ (deployed to SVN /assets, not into the plugin):
 *
 *   node scripts/wporg-assets.mjs                                # icons, banners and screenshots
 *   node scripts/wporg-assets.mjs --no-shots                     # only icons and banners
 *   node scripts/wporg-assets.mjs --base http://127.0.0.1:9400   # screenshots from an already running `npm run playground`
 *
 * Icons (animated GIF) and banners come from scripts/assets/wporg-brand.html. Screenshots are taken from the real screens in
 * WordPress Playground, connected to the fake Google place (tests/e2e/seed.php). Uses the locally installed Edge.
 * The captions are the "== Screenshots ==" list in wille-reviews/readme.txt (same order).
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chromium } from '@playwright/test';
import gifenc from 'gifenc';
import { root } from './lib/php.mjs';

const { GIFEncoder, applyPalette, quantize } = gifenc;
const args = process.argv.slice(2);
const out = path.join(root, '.wordpress-org');
const shots = !args.includes('--no-shots');
const external = args.includes('--base') ? args[args.indexOf('--base') + 1] : null;
fs.mkdirSync(out, { recursive: true });
const browser = await chromium.launch(process.env.CI ? {} : { channel: 'msedge' });

// ------------------------------------------------------------------ icons + banners.
// The icon is an animated GIF (wordpress.org accepts icon-128x128.gif / icon-256x256.gif, max. 1 MB): every frame is
// rendered in the browser, read back as pixels and encoded with one shared palette, so colours do not flicker.
const brand = pathToFileURL(path.join(root, 'scripts', 'assets', 'wporg-brand.html')).href;
for (const size of [128, 256]) {
	const page = await browser.newPage({ viewport: { width: size, height: size } });
	await page.goto(`${brand}?asset=icon&frame=0`);
	const delays = await page.evaluate(() => window.iconFrames);
	const frames = [];
	for (let i = 0; i < delays.length; i++) {
		await page.goto(`${brand}?asset=icon&frame=${i}`);
		frames.push(dither(await pixels(page, size), size));
	}
	await page.close();

	const all = new Uint8Array(frames.length * size * size * 4);
	frames.forEach((f, i) => all.set(f, i * f.length));
	const palette = quantize(all, 256);
	const gif = GIFEncoder();
	frames.forEach((f, i) => gif.writeFrame(applyPalette(f, palette), size, size, { palette: i ? undefined : palette, delay: delays[i], repeat: 0 }));
	gif.finish();
	const file = `icon-${size}x${size}.gif`;
	fs.writeFileSync(path.join(out, file), gif.bytes());
	// A PNG next to the GIF would compete with it on wordpress.org.
	fs.rmSync(path.join(out, `icon-${size}x${size}.png`), { force: true });
	console.log(`✓ ${file} (${frames.length} frames, ${Math.round(gif.bytes().length / 1024)} KB)`);
}

for (const [file, width, height] of [
	['banner-772x250.png', 772, 250],
	['banner-1544x500.png', 1544, 500],
]) {
	const page = await browser.newPage({ viewport: { width, height } });
	await page.goto(`${brand}?asset=banner`);
	await page.screenshot({ path: path.join(out, file) });
	await page.close();
	console.log(`✓ ${file}`);
}

/**
 * Ordered (Bayer 4×4) dithering against banding of the background gradient in 256 colours. The pattern is
 * fixed per pixel position, so it does not flicker between frames.
 */
function dither(rgba, size) {
	const bayer = [0, 8, 2, 10, 12, 4, 14, 6, 3, 11, 1, 9, 15, 7, 13, 5];
	for (let y = 0; y < size; y++) {
		for (let x = 0; x < size; x++) {
			const offset = (bayer[(y % 4) * 4 + (x % 4)] / 16 - 0.5) * 6;
			const i = (y * size + x) * 4;
			for (let c = 0; c < 3; c++) rgba[i + c] = Math.max(0, Math.min(255, Math.round(rgba[i + c] + offset)));
		}
	}
	return rgba;
}

/** RGBA pixels of the rendered page (screenshot decoded by the browser itself). */
async function pixels(page, size) {
	const png = (await page.screenshot()).toString('base64');
	const base64 = await page.evaluate(
		async ({ png, size }) => {
			const img = new Image();
			img.src = `data:image/png;base64,${png}`;
			await img.decode();
			const canvas = new OffscreenCanvas(size, size);
			const ctx = canvas.getContext('2d');
			ctx.drawImage(img, 0, 0);
			const data = ctx.getImageData(0, 0, size, size).data;
			let bin = '';
			for (let i = 0; i < data.length; i += 0x8000) bin += String.fromCharCode.apply(null, data.subarray(i, i + 0x8000));
			return btoa(bin);
		},
		{ png, size }
	);
	return new Uint8Array(Buffer.from(base64, 'base64'));
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
		await page.locator('.willerev-choice', { hasText: 'Quote' }).click();
		await tidy();
		await shot(1, 'design page');

		// 2. Slider in the dark style, 3. wall in the quote style, 4. badge + social proof (front end).
		await page.goto(`${base}/styles/`);
		await tidy();
		await shot(2, 'slider dark', page.locator('#slider'));
		await shot(3, 'wall quote', page.locator('#wall'));
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
