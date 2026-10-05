// Admin screens in a real browser: design page (live preview, shortcode builder, saving the default), settings
// (status, connection test), dashboard widget and the block in the editor.
import { expect, test } from '@playwright/test';

const DESIGN = '/wp-admin/admin.php?page=wille-reviews';
const SETTINGS = '/wp-admin/admin.php?page=wille-reviews-settings';

/**
 * Collects console errors, uncaught exceptions and requests to other hosts (the plugin promises that nothing is
 * loaded from Google or anywhere else).
 */
function watch(page) {
	const errors = [];
	const external = [];
	page.on('console', (msg) => {
		if (msg.type() === 'error') errors.push(msg.text());
	});
	page.on('pageerror', (err) => errors.push(err.message));
	page.on('request', (req) => {
		const url = new URL(req.url());
		if (!url.protocol.startsWith('http')) return;
		// WordPress core itself may fetch Gravatar/emoji/s.w.org – not the plugin's business.
		if (!['127.0.0.1', 'localhost'].includes(url.hostname) && !/(^|\.)(gravatar\.com|w\.org|wordpress\.org)$/.test(url.hostname)) {
			external.push(req.url());
		}
	});
	return { errors, external };
}

test.describe.configure({ mode: 'serial' });

test('design page previews the connected place', async ({ page }) => {
	const seen = watch(page);
	await page.goto(DESIGN);

	await expect(page.getByRole('heading', { name: 'Design & shortcode' })).toBeVisible();
	const preview = page.locator('[data-willerev-preview]');
	const grid = preview.locator('[data-layout="grid"]');
	await expect(grid).toBeVisible();
	await expect(grid).toContainText('Anna Becker');
	await expect(grid).toContainText('Based on 312 reviews');
	await expect(preview).not.toContainText('Sample data');
	// Mirrored photos, not Google's servers.
	const src = await grid.locator('img.willerev-avatar').first().getAttribute('src');
	expect(src).toContain('/wp-content/uploads/wille-reviews/');

	expect(seen.errors).toEqual([]);
	expect(seen.external).toEqual([]);
});

test('layout, style and options update the preview and the shortcode instantly', async ({ page }) => {
	await page.goto(DESIGN);
	const preview = page.locator('[data-willerev-preview]');
	const code = page.locator('[data-willerev-shortcode]');
	await expect(code).toHaveText('[wille_reviews layout="grid" style="light"]');

	await page.locator('.willerev-choice', { hasText: 'Slider' }).click();
	await page.locator('.willerev-choice', { hasText: 'Dark' }).click();
	const slider = preview.locator('[data-layout="carousel"] .willerev');
	await expect(slider).toBeVisible();
	await expect(preview.locator('[data-layout="grid"]')).toBeHidden();
	await expect(slider).toHaveClass(/willerev--style-dark/);
	await expect(code).toHaveText('[wille_reviews layout="carousel" style="dark"]');

	// Slider arrows work in the preview.
	const track = slider.locator('.willerev__items');
	await slider.locator('.willerev__nav--next').click();
	await expect.poll(() => track.evaluate((el) => el.scrollLeft)).toBeGreaterThan(0);

	// Minimum stars hides the 2-star review; the number of reviews limits the cards.
	await page.locator('[data-willerev="min_rating"]').selectOption('4');
	await expect(slider.locator('.willerev-card', { hasText: 'Lukas Hoffmann' })).toBeHidden();
	await page.locator('[data-willerev="limit"]').fill('2');
	await expect(slider.locator('.willerev-card:visible')).toHaveCount(2);

	await page.locator('[data-willerev="accent"]').fill('#ff5722');
	await page.locator('[data-willerev="show_header"]').uncheck();
	await expect(slider.locator('.willerev__header')).toBeHidden();
	await expect(code).toHaveText('[wille_reviews layout="carousel" style="dark" accent="#ff5722" limit="2" min_rating="4" header="no"]');
	expect(await slider.evaluate((el) => el.style.getPropertyValue('--willerev-accent'))).toBe('#ff5722');

	// Badge layout: card options are not offered.
	await page.locator('.willerev-choice', { hasText: 'Badge' }).click();
	await expect(page.locator('[data-willerev="limit"]')).toBeHidden();
	await expect(preview.locator('[data-layout="badge"] .willerev-badge')).toBeVisible();

	// Dark page background toggle.
	await page.getByRole('button', { name: 'Dark page' }).click();
	await expect(preview).toHaveClass(/is-dark/);
});

test('saving the design changes the default of every widget', async ({ page }) => {
	await page.goto(DESIGN);
	await page.locator('.willerev-choice', { hasText: 'Wall' }).click();
	await page.locator('.willerev-choice', { hasText: 'Quote' }).click();
	await Promise.all([page.waitForURL(/settings-updated=true/), page.getByRole('button', { name: 'Save as default design' }).click()]);
	await expect(page.getByText('Design saved as default.')).toBeVisible();
	await expect(page.locator('input[value="masonry"]')).toBeChecked();

	await page.goto('/google-reviews/');
	const section = page.locator('#google-reviews');
	await expect(section).toHaveClass(/willerev--layout-masonry/);
	await expect(section).toHaveClass(/willerev--style-quote/);

	// The connection survived the design save.
	await page.goto(SETTINGS);
	await expect(page.locator('#willerev-api-key')).toHaveValue('TEST-KEY');

	// Back to grid/light for the other tests.
	await page.goto(DESIGN);
	await page.locator('.willerev-choice', { hasText: 'Grid' }).click();
	await page.locator('.willerev-choice', { hasText: 'Light' }).click();
	await Promise.all([page.waitForURL(/settings-updated=true/), page.getByRole('button', { name: 'Save as default design' }).click()]);
});

test('settings show the status and test the connection', async ({ page }) => {
	const seen = watch(page);
	await page.goto(SETTINGS);
	await expect(page.getByText('Last request successful')).toBeVisible();
	await expect(page.locator('.willerev-status-place')).toContainText('Café Sonnenschein');
	await expect(page.getByText(/Next automatic refresh/)).toBeVisible();

	await Promise.all([page.waitForURL(/willerev-refreshed=ok/), page.getByRole('button', { name: 'Test connection & load reviews' }).click()]);
	await expect(page.getByText('Connection works – the reviews were loaded from Google.')).toBeVisible();

	// Saving the connection page keeps the design (and the other way round).
	await page.locator('#willerev-cache-hours').fill('24');
	await Promise.all([page.waitForURL(/settings-updated=true/), page.getByRole('button', { name: 'Save settings' }).click()]);
	await expect(page.locator('#willerev-cache-hours')).toHaveValue('24');
	await expect(page.locator('#willerev-place-id')).toHaveValue('ChIJtest');

	// A wrong key: error with Google's message, the site keeps showing the last good reviews.
	await page.locator('#willerev-api-key').fill('WRONG-KEY');
	await Promise.all([page.waitForURL(/settings-updated=true/), page.getByRole('button', { name: 'Save settings' }).click()]);
	await Promise.all([page.waitForURL(/willerev-refreshed=error/), page.getByRole('button', { name: 'Test connection & load reviews' }).click()]);
	await expect(page.getByText('Last request failed')).toBeVisible();
	await expect(page.locator('.willerev-status-message')).toContainText('PERMISSION_DENIED');
	await page.goto('/google-reviews/');
	await expect(page.locator('#google-reviews')).toContainText('Anna Becker');

	await page.goto(SETTINGS);
	await page.locator('#willerev-api-key').fill('TEST-KEY');
	await Promise.all([page.waitForURL(/settings-updated=true/), page.getByRole('button', { name: 'Save settings' }).click()]);
	await Promise.all([page.waitForURL(/willerev-refreshed=ok/), page.getByRole('button', { name: 'Test connection & load reviews' }).click()]);

	expect(seen.errors).toEqual([]);
	expect(seen.external).toEqual([]);
});

test('dashboard widget shows rating and latest reviews', async ({ page }) => {
	await page.goto('/wp-admin/');
	const widget = page.locator('#willerev_dashboard');
	await expect(widget).toContainText('4.7');
	await expect(widget).toContainText('312 reviews on Google');
	await expect(widget).toContainText('Anna Becker');
});

test('block "Google Reviews" previews in the editor', async ({ page }) => {
	const seen = watch(page);
	await page.goto('/wp-admin/post-new.php?post_type=page');
	await page.waitForFunction(() => window.wp?.data?.select('core/block-editor') && window.wp.blocks.getBlockType('wille-reviews/reviews'));
	await page.evaluate(() => {
		window.wp.data.dispatch('core/edit-post')?.toggleFeature?.('welcomeGuide');
		const guide = window.wp.data.select('core/preferences')?.get('core/edit-post', 'welcomeGuide');
		if (guide) window.wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false);
		window.wp.data.dispatch('core/block-editor').insertBlocks(window.wp.blocks.createBlock('wille-reviews/reviews', { layout: 'social', style: 'accent' }));
	});
	const iframe = page.locator('iframe[name="editor-canvas"]');
	const canvas = (await iframe.count()) ? page.frameLocator('iframe[name="editor-canvas"]') : page;
	await expect(canvas.locator('.willerev--layout-social.willerev--style-accent')).toBeVisible({ timeout: 30_000 });
	await expect(canvas.locator('.willerev-social')).toContainText('312');
	expect(seen.errors.filter((e) => !/favicon|preload/i.test(e))).toEqual([]);
});
