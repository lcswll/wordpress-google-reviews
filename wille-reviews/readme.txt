=== Wille Reviews – Review Widgets for Google ===
Contributors: lcswll
Tags: google reviews, reviews, testimonials, rating, widget
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your Google reviews in six layouts and five styles. Live preview, shortcode builder, block. Cached on your server, no visitor requests to Google.

== Description ==

**Your Google rating, where your visitors decide.** Wille Reviews loads your rating, the number of reviews and the latest reviews of your Google Business Profile and shows them in the design that fits your site – picked with a live preview, not by trial and error.

**Free. No subscription, no premium version, no account with us.** You connect your own Google API key; the plugin talks to Google from your server only.

= Six layouts =

* **Grid** – review cards in 1–4 columns, the classic reviews section.
* **Slider** – one swipeable row with arrow buttons, saves space.
* **List** – full-width rows, easy to read, good for long texts and sidebars.
* **Wall** – cards of different heights stacked without gaps (masonry), a testimonial wall.
* **Badge** – compact rating badge for headers, footers, checkout and landing pages.
* **Social proof** – overlapping reviewer photos, stars, rating and total count – ideal next to a call to action.

= Five styles =

**Light**, **Dark**, **Minimal**, **Speech bubble** and **Accent color** – each combinable with every layout, with your own accent color and corner radius. Styles are pure CSS custom properties, so your theme can fine-tune anything.

= Designed in a minute =

* **Design page with live preview**: click through layouts and styles, change color, columns, number of reviews, minimum stars and text length – the preview updates instantly, also against a dark page background.
* **Shortcode builder**: copy the shortcode for exactly the design you see – or save it as the default for every widget.
* **Block "Google Reviews"** with layout and style pickers in the sidebar and a real preview in the editor.
* **Floating badge** (optional): a small rating badge in a corner of every page; visitors can close it.
* **Dashboard widget** with your current rating and the latest reviews.

= Fast and privacy-friendly =

* **No requests from your visitors to Google.** Reviews are fetched on your server and cached; a background job (WP-Cron) refreshes them, so no visitor ever waits for the API.
* **Profile photos are copied to your site** once (uploads/wille-reviews/) – or switched off entirely. Visitors' browsers never load images from Google.
* **Stays online during API outages**: the last good result bridges errors – but never longer than the 30 days Google allows for caching.
* Tiny front end: one small stylesheet and one small deferred script, loaded only on pages that show a widget. Works without JavaScript, too.
* Accessible: stars announced as "4.8 out of 5 stars", keyboard-operable slider, reduced-motion support, right-to-left aware.

= For developers =

* Shortcode `[wille_reviews]` with options `layout`, `style`, `accent`, `radius`, `columns`, `limit`, `min_rating`, `sort`, `lines`, `header`, `cta`, `avatars`, `link`, `align`, `id`, `class` – all documented on the design page.
* Filters `willerev_html` (widget HTML) and `willerev_show_floating_badge`, action `willerev_refreshed`.
* WP-CLI: `wp willerev status`, `refresh`, `flush`, `selftest`.

== Installation ==

1. Install and activate the plugin.
2. In the Google Cloud Console, enable **Places API (New)** and create an API key (step-by-step guide on the settings page).
3. Go to **Google Reviews → Settings**, paste the API key and your Place ID, save and click **Test connection & load reviews**.
4. Go to **Google Reviews → Design**, pick layout and style, and either save them as the default or copy the shortcode. In the block editor, add the block **Google Reviews**.

== Frequently Asked Questions ==

= Why do I need my own Google API key? =

Google only hands out reviews through its Places API, and every request needs a key belonging to a Google Cloud project. With your own key the data flows directly from Google to your site – nobody in between, no account with us.

= What does the Google API cost? =

Google bills per request. The plugin makes one request per refresh, independent of your traffic: with the default of every 12 hours that is about 60 requests a month. Google grants a free monthly usage per product; see Google Maps Platform pricing for the current numbers.

= Why do I see at most five reviews? =

Google's Places API returns up to five reviews per place (the most relevant ones; the plugin shows them newest first). Rating and total count always reflect all your reviews.

= Can I hide negative reviews? =

You can set a minimum number of stars per widget. Rating and total count still show your real average – the plugin never changes them.

= Does the plugin add review stars to Google search results (schema)? =

No, on purpose. Google does not show rich results for reviews that a business shows about itself, and marking up third-party reviews from Google can count as spam. The plugin outputs plain HTML.

= Is it GDPR-friendly? =

Your visitors' browsers do not contact Google: the data is fetched on your server, and profile photos are stored on your site (or turned off). The reviews themselves are public content from Google that you display; mention the Google Places API in your privacy policy.

= The reviews do not update. =

Open **Google Reviews → Settings**: the status box shows the last request and its error message, if any. **Test connection & load reviews** fetches immediately. If WP-Cron is disabled on your server, make sure a real cron job calls wp-cron.php.

= Can I change the look with my own CSS? =

Yes. Every widget exposes CSS custom properties, e.g. `.willerev { --willerev-star: #f59e0b; --willerev-card-bg: #fffaf0; }`, and uses BEM-style classes such as `.willerev-card` and `.willerev__header`.

== External services ==

This plugin connects to the **Google Places API (New)**, operated by Google LLC, to load the rating, the number of reviews and the latest reviews (author name, author profile link, profile photo, star rating, text, publication time) of the Google Business Profile you configure. Nothing is sent before you enter an API key and a Place ID.

* **What is sent and when:** your API key, the Place ID and a language code are sent from your server to https://places.googleapis.com/v1/places/ when you click "Test connection & load reviews", on the first page view without cached data and then once per refresh interval (default every 12 hours, WP-Cron). No data about your visitors is sent.
* **Profile photos:** unless you switch them off, the profile photo of each shown reviewer is downloaded once from Google's image servers (googleusercontent.com) by your server and stored in your uploads folder. Visitors' browsers load the local copy.
* **Links:** buttons and names in the widgets link to your Google Maps profile, Google's review form (search.google.com/local/writereview) and the reviewers' Google profiles. These are plain links; Google is only contacted when a visitor clicks one.

Google Maps Platform Terms of Service: https://cloud.google.com/maps-platform/terms – Google Privacy Policy: https://policies.google.com/privacy

== Screenshots ==

1. Design page: pick a layout and a style, see the result instantly and copy the shortcode.
2. Slider layout in the dark style.
3. Wall (masonry) layout in the speech bubble style.
4. Badge and social proof.
5. Settings with step-by-step connection guide and status.

== Changelog ==

= 1.0.0 =
* First release: six layouts (grid, slider, list, wall, badge, social proof), five styles (light, dark, minimal, speech bubble, accent color), design page with live preview and shortcode builder, block "Google Reviews", floating badge, dashboard widget, Places API (New) with server-side cache, outage backup and local profile photos, WP-CLI commands.

== Upgrade Notice ==

= 1.0.0 =
First release.
