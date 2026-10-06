=== Wille Reviews – Review Widgets for Google ===
Contributors: lcswll
Tags: google reviews, reviews, testimonials, rating, widget
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Your Google reviews on your site in a modern design that fits your theme. Six layouts, five styles, block and Elementor widget. Free.

== Description ==

**Most visitors check your Google rating before they get in touch.** Wille Reviews puts it on your own site: your average rating, the number of reviews and your latest reviews from your Google Business Profile – as a reviews section, a slider, a testimonial wall or a small badge next to your contact button.

Review widgets often look like a foreign object on a carefully designed site. Wille Reviews is built the other way round: a modern, clear design that takes over your theme's font – a score bar with your rating and number of reviews at the top, clean review cards below, your own accent colour and corner radius – and a design page where you see every change before it goes live.

**Free. No subscription, no premium version, no account with us, no widget service in between.** You connect your own Google API key; your server talks to Google directly.

= What makes it different =

* **Designed to build trust.** Rating, stars and number of reviews come first, then the reviews themselves – name, date, stars, text, each with the Google mark. Six layouts × five styles, each combinable with your accent colour and corner radius. The type scale is fixed, so the widget looks the same on a theme with an 18 px body font as on one with 14 px.
* **German included.** Ships with a complete German translation (frontend and admin); more languages come through translate.wordpress.org.
* **Pick the design by looking at it.** The design page shows every layout live with sample data or your real reviews, also on a dark page background, and writes the matching shortcode for you.
* **Your visitors never contact Google.** Reviews are fetched and cached on your server, profile photos are copied to your site. No external scripts, no cookies, no iframe.
* **Fast.** One small stylesheet and one small deferred script, loaded only on pages that show a widget. Works without JavaScript.
* **Honest numbers.** Rating and total count always show your real Google values – even when you choose to show only reviews with four stars or more.

= Six layouts =

* **Grid** – review cards in 1–4 columns, the classic reviews section.
* **Slider** – one swipeable row with arrow buttons, saves space.
* **List** – full-width rows, easy to read, good for long texts and sidebars.
* **Wall** – cards of different heights stacked without gaps (masonry), a testimonial wall.
* **Badge** – compact rating badge for headers, footers, checkout and landing pages.
* **Social proof** – reviewer photos, stars, rating and total count in one line – ideal next to a call to action.

= Five styles =

* **Light** – white cards with a fine border and soft depth, for most themes.
* **Dark** – a near-black panel with glass-like cards, for dark sections and dark themes.
* **Minimal** – no boxes at all, only typography and fine lines.
* **Quote** – the review as a quotation on a soft tint, the reviewer below it.
* **Accent** – a light tint of your brand colour as background, the colour on rating and buttons.

Styles are pure CSS custom properties, so your theme can fine-tune anything.

= Everywhere you need it =

* **Shortcode** `[wille_reviews]` – copy it from the design page with exactly the design you see, or use the saved default.
* **Block "Google Reviews"** with layout and style pickers in the sidebar and a real preview in the editor.
* **Elementor widget "Google Reviews"** with every shortcode option in the Elementor panel – drag it onto the page instead of pasting shortcodes.
* **Floating badge** (optional): a small rating badge in a corner of every page; visitors can close it.
* **Dashboard widget** with your current rating and the latest reviews.
* **"Write a review" button** that opens Google's review form for your business directly – the easiest way to collect new reviews.

= Reliable =

* A background job (WP-Cron) refreshes the reviews, so no visitor ever waits for the API.
* **Stays online during API outages**: the last good result bridges errors – but never longer than the 30 days Google allows for caching.
* Clear status on the settings page: last request, next refresh, and Google's error messages explained in plain language.
* Accessible: stars announced as "4.8 out of 5 stars", keyboard-operable slider, reduced-motion support, right-to-left aware.

= Privacy =

* No requests from your visitors' browsers to Google – not even for profile photos (or switch photos off entirely).
* No cookies, no tracking, no data about your visitors is stored or sent anywhere.
* The only external service is the Google Places API, called from your server – see "External services" below.
* Data is only removed on uninstall if you opt in.

= For developers =

* Shortcode options `layout`, `style`, `accent`, `count_color`, `radius`, `columns`, `limit`, `min_rating`, `sort`, `lines`, `header`, `cta`, `avatars`, `link`, `align`, `id`, `class` – all documented on the design page.
* Filters `willerev_html` (widget HTML) and `willerev_show_floating_badge`, action `willerev_refreshed`.
* WP-CLI: `wp willerev status`, `refresh`, `flush`, `selftest`.

= About the author =

Wille Reviews is developed and maintained by Lucas Wille, a web developer from Magdeburg, Germany, who builds websites and AI automations: https://lucaswille.de/

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

= Does it work with Elementor? =

Yes. Search for "Google Reviews" in the Elementor panel and drag the widget onto your page. All options of the shortcode are available as controls; options left on "Default" follow the design you saved on the design page.

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
3. Wall (masonry) layout in the quote style.
4. Badge and social proof.
5. Settings with step-by-step connection guide and status.

== Changelog ==

= 1.0.0 =
* First release: six layouts (grid, slider, list, wall, badge, social proof), five styles (light, dark, minimal, quote, accent), design page with live preview and shortcode builder, block and Elementor widget "Google Reviews", floating badge, dashboard widget, Places API (New) with server-side cache, outage backup and local profile photos, WP-CLI commands, German translation included.

== Upgrade Notice ==

= 1.0.0 =
First release.
