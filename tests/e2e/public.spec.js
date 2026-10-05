// Front end as a logged-out visitor: every layout renders, nothing is loaded from Google, slider, "Read more",
// floating badge, links and the block.
import { expect, test } from '@playwright/test';

test.describe.configure({ mode: 'serial' });

/**
 * A logged-out visitor. Playground's --login signs in every cookie-less request automatically; the marker cookie
 * tells it this client has had its auto-login already, so the visit stays anonymous.
 */
async function visitor(browser, baseURL) {
	const context = await browser.newContext({ baseURL });
	const { hostname } = new URL(baseURL);
	await context.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', domain: hostname, path: '/' }]);
	const page = await context.newPage();
	const errors = [];
	const external = [];
	page.on('console', (msg) => {
		if (msg.type() === 'error') errors.push(msg.text());
	});
	page.on('pageerror', (err) => errors.push(err.message));
	page.on('request', (req) => {
		const url = new URL(req.url());
		if (url.protocol.startsWith('http') && !['127.0.0.1', 'localhost'].includes(url.hostname) && !/(^|\.)(w\.org|wordpress\.org)$/.test(url.hostname)) {
			external.push(req.url());
		}
	});
	return { page, context, errors, external };
}

test('every layout renders for visitors without loading anything from Google', async ({ browser, baseURL }) => {
	const v = await visitor(browser, baseURL);
	await v.page.goto('/styles/');

	await expect(v.page.locator('#wpadminbar')).toHaveCount(0); // really logged out
	await expect(v.page.locator('#grid.willerev--layout-grid.willerev--style-light')).toBeVisible();
	await expect(v.page.locator('#slider.willerev--layout-carousel.willerev--style-dark')).toBeVisible();
	await expect(v.page.locator('#list.willerev--layout-list.willerev--style-minimal')).toBeVisible();
	await expect(v.page.locator('#wall.willerev--layout-masonry.willerev--style-bubble')).toBeVisible();
	await expect(v.page.locator('.willerev--layout-badge.willerev--style-accent .willerev-badge')).toBeVisible();
	await expect(v.page.locator('.willerev--layout-social .willerev-social')).toBeVisible();

	// List: header off and only 4+ stars.
	await expect(v.page.locator('#list .willerev__header')).toHaveCount(0);
	await expect(v.page.locator('#list')).not.toContainText('Lukas Hoffmann');

	// Stars are announced; the visual rating is not read twice.
	await expect(v.page.locator('#grid .willerev__score .willerev-stars')).toHaveAttribute('aria-label', '4.7 out of 5 stars');

	// Badge colors from the shortcode, readable text color computed for it.
	const badge = v.page.locator('.willerev--layout-badge:not(.willerev--floating)');
	expect(await badge.evaluate((el) => el.style.getPropertyValue('--willerev-accent'))).toBe('#0f766e');
	expect(await badge.evaluate((el) => el.style.getPropertyValue('--willerev-accent-ink'))).toBe('#ffffff');

	// Every star row is filled exactly to its rating (the 2-star review shows two stars, not five).
	const fills = await v.page.locator('.willerev-stars').evaluateAll((rows) => rows.map((row) => ({
		label: row.getAttribute('aria-label'),
		ratio: row.querySelector('.willerev-stars__full').getBoundingClientRect().width / row.getBoundingClientRect().width,
	})));
	expect(fills.length).toBeGreaterThan(10);
	for (const { label, ratio } of fills) {
		expect(ratio, label).toBeCloseTo(parseFloat(label) / 5, 1);
	}

	// Profile photos come from the uploads folder.
	const photos = await v.page.locator('img.willerev-avatar').evaluateAll((imgs) => imgs.map((img) => img.currentSrc || img.src));
	expect(photos.length).toBeGreaterThan(0);
	for (const src of photos) expect(src).toContain('/wp-content/uploads/wille-reviews/');

	expect(v.errors).toEqual([]);
	expect(v.external).toEqual([]);
	await v.context.close();
});

test('slider arrows scroll and disable at the ends', async ({ browser, baseURL }) => {
	const v = await visitor(browser, baseURL);
	await v.page.goto('/styles/');
	const slider = v.page.locator('#slider');
	const track = slider.locator('.willerev__items');
	const prev = slider.locator('.willerev__nav--prev');
	const next = slider.locator('.willerev__nav--next');

	await expect(prev).toBeDisabled();
	await next.click();
	await expect.poll(() => track.evaluate((el) => el.scrollLeft)).toBeGreaterThan(0);
	await expect(prev).toBeEnabled();
	for (let i = 0; i < 6 && (await next.isEnabled()); i++) {
		await next.click();
		await v.page.waitForTimeout(400);
	}
	await expect(next).toBeDisabled();
	await v.context.close();
});

test('long reviews are shortened with an accessible "Read more"', async ({ browser, baseURL }) => {
	const v = await visitor(browser, baseURL);
	await v.page.goto('/styles/');
	const card = v.page.locator('#grid .willerev-card', { hasText: 'Mira Schulz' });
	const more = card.getByRole('button', { name: 'Read more' });
	await expect(more).toBeVisible();
	await expect(more).toHaveAttribute('aria-expanded', 'false');
	const clamped = await card.locator('.willerev-card__text').evaluate((el) => el.clientHeight);
	await more.click();
	await expect(card.getByRole('button', { name: 'Show less' })).toHaveAttribute('aria-expanded', 'true');
	expect(await card.locator('.willerev-card__text').evaluate((el) => el.clientHeight)).toBeGreaterThan(clamped);

	// Short reviews get no button.
	await expect(v.page.locator('#grid .willerev-card', { hasText: 'Jonas Weber' }).locator('.willerev-card__more')).toBeHidden();
	await v.context.close();
});

test('the first section on a page gets the #google-reviews anchor the badge links to', async ({ browser, baseURL }) => {
	const v = await visitor(browser, baseURL);
	await v.page.goto('/google-reviews/');
	await expect(v.page.locator('section#google-reviews')).toHaveCount(1);
	await expect(v.page.locator('#google-reviews')).toContainText('Write a review');
	await expect(v.page.locator('#google-reviews a', { hasText: 'Write a review' })).toHaveAttribute('href', /writereview\?placeid=ChIJtest/);

	// Floating badge (on in the seed) links to the reviews page and can be closed for the session.
	const floating = v.page.locator('.willerev--floating');
	await expect(floating).toBeVisible();
	await expect(floating.locator('a.willerev-badge')).toHaveAttribute('href', /\/google-reviews\/#google-reviews$/);
	await floating.hover();
	await floating.getByRole('button', { name: 'Hide rating' }).click();
	await expect(floating).toBeHidden();
	await v.page.reload();
	await expect(v.page.locator('.willerev--floating')).toBeHidden();
	await v.context.close();
});

test('the block renders on the front end with its own design', async ({ browser, baseURL }) => {
	const v = await visitor(browser, baseURL);
	await v.page.goto('/block-page/');
	const block = v.page.locator('.willerev--layout-carousel.willerev--style-accent');
	await expect(block).toBeVisible();
	expect(await block.evaluate((el) => el.style.getPropertyValue('--willerev-accent'))).toBe('#7c3aed');
	await expect(block.locator('.willerev-card')).toHaveCount(4);
	expect(v.errors).toEqual([]);
	await v.context.close();
});

test('the widget adapts to a phone screen', async ({ browser, baseURL }) => {
	const v = await visitor(browser, baseURL);
	await v.page.setViewportSize({ width: 390, height: 844 });
	await v.page.goto('/styles/');
	const columns = await v.page.locator('#grid .willerev__items').evaluate((el) => getComputedStyle(el).gridTemplateColumns.split(' ').length);
	expect(columns).toBe(1);
	const overflow = await v.page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
	expect(overflow).toBeLessThanOrEqual(1);
	await v.context.close();
});
