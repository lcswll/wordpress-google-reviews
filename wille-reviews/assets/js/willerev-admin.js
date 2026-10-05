/**
 * Wille Reviews – design page: live preview and shortcode builder.
 * Every layout is rendered once by PHP; this script only swaps classes and custom properties.
 */
(function (config) {
	'use strict';

	var form = document.getElementById('willerev-designer');
	if (!form || !config) {
		return;
	}
	var preview = form.querySelector('[data-willerev-preview]');
	var CARD_LAYOUTS = ['grid', 'carousel', 'list', 'masonry'];

	function field(name) {
		return form.querySelectorAll('[name="willerev_settings[' + name + ']"]');
	}

	function value(name) {
		var els = field(name);
		for (var i = 0; i < els.length; i++) {
			var el = els[i];
			if ('radio' === el.type) {
				if (el.checked) {
					return el.value;
				}
			} else if ('checkbox' === el.type) {
				return el.checked ? 1 : 0;
			} else {
				return el.value;
			}
		}
		return '';
	}

	function state() {
		return {
			layout: value('layout'),
			style: value('style'),
			accent: value('accent'),
			radius: parseInt(value('radius'), 10) || 0,
			columns: parseInt(value('columns'), 10) || 1,
			limit: parseInt(value('limit'), 10) || 1,
			min_rating: parseInt(value('min_rating'), 10) || 0,
			lines: parseInt(value('lines'), 10) || 0,
			show_header: value('show_header'),
			show_avatars: value('show_avatars'),
			show_cta: value('show_cta'),
		};
	}

	/** Same rule as WILLEREV_Render::ink_on(): white or dark text on the accent color. */
	function ink(hex) {
		var h = String(hex).replace('#', '');
		if (3 === h.length) {
			h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
		}
		if (!/^[0-9a-f]{6}$/i.test(h)) {
			return '#ffffff';
		}
		var ch = function (i) {
			var v = parseInt(h.substr(i, 2), 16) / 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
		};
		var l = 0.2126 * ch(0) + 0.7152 * ch(2) + 0.0722 * ch(4);
		return 1.05 / (l + 0.05) >= (l + 0.05) / 0.0656 ? '#ffffff' : '#1f2328';
	}

	function swapClass(el, prefix, next) {
		var remove = [];
		for (var i = 0; i < el.classList.length; i++) {
			if (0 === el.classList[i].indexOf(prefix)) {
				remove.push(el.classList[i]);
			}
		}
		for (var j = 0; j < remove.length; j++) {
			el.classList.remove(remove[j]);
		}
		el.classList.add(prefix + next);
	}

	function updateRoot(root, s) {
		swapClass(root, 'willerev--style-', s.style);
		swapClass(root, 'willerev--cols-', s.columns);
		root.classList.toggle('willerev--no-header', !s.show_header);
		root.classList.toggle('willerev--no-avatars', !s.show_avatars);
		root.classList.toggle('willerev--no-cta', !s.show_cta);
		root.style.setProperty('--willerev-accent', s.accent);
		root.style.setProperty('--willerev-accent-ink', ink(s.accent));
		root.style.setProperty('--willerev-radius', s.radius + 'px');
		root.style.setProperty('--willerev-cols', String(s.columns));
		root.style.setProperty('--willerev-lines', String(s.lines > 0 ? s.lines : 999));

		// Social proof faces follow the photo toggle.
		var faces = root.querySelector('.willerev-social__faces');
		if (faces) {
			faces.hidden = !s.show_avatars;
		}

		var cards = Array.prototype.slice.call(root.querySelectorAll('.willerev-card'));
		cards.sort(function (a, b) {
			return parseInt(a.getAttribute('data-rank'), 10) - parseInt(b.getAttribute('data-rank'), 10);
		});
		var shown = 0;
		cards.forEach(function (card) {
			var ok = parseInt(card.getAttribute('data-rating'), 10) >= s.min_rating && shown < s.limit;
			if (ok) {
				shown++;
			}
			card.hidden = !ok;
		});
	}

	/** "Read more" only where text is cut off at the current line count (needs the item to be visible). */
	function measure(root) {
		var cards = root.querySelectorAll('.willerev-card');
		for (var i = 0; i < cards.length; i++) {
			var text = cards[i].querySelector('.willerev-card__text');
			var button = cards[i].querySelector('.willerev-card__more');
			if (!text || !button || cards[i].classList.contains('is-open')) {
				continue;
			}
			var cut = text.scrollHeight - text.clientHeight > 2;
			button.hidden = !cut;
			if (cut && !button.getAttribute('data-more')) {
				button.setAttribute('data-more', root.getAttribute('data-more') || button.textContent);
				button.setAttribute('data-less', root.getAttribute('data-less') || button.textContent);
			}
		}
	}

	function shortcode(s) {
		var d = config.defaults;
		var parts = ['layout="' + s.layout + '"', 'style="' + s.style + '"'];
		var cards = CARD_LAYOUTS.indexOf(s.layout) !== -1;
		if (s.accent.toLowerCase() !== String(d.accent).toLowerCase()) {
			parts.push('accent="' + s.accent + '"');
		}
		if (s.radius !== d.radius) {
			parts.push('radius="' + s.radius + '"');
		}
		if (cards) {
			if ('list' !== s.layout && s.columns !== d.columns) {
				parts.push('columns="' + s.columns + '"');
			}
			if (s.limit !== d.limit) {
				parts.push('limit="' + s.limit + '"');
			}
			if (s.min_rating !== d.min_rating) {
				parts.push('min_rating="' + s.min_rating + '"');
			}
			if (s.lines !== d.lines) {
				parts.push('lines="' + s.lines + '"');
			}
			if (s.show_header !== d.show_header) {
				parts.push('header="' + (s.show_header ? 'yes' : 'no') + '"');
			}
			if (s.show_cta !== d.show_cta) {
				parts.push('cta="' + (s.show_cta ? 'yes' : 'no') + '"');
			}
		}
		if (s.show_avatars !== d.show_avatars) {
			parts.push('avatars="' + (s.show_avatars ? 'yes' : 'no') + '"');
		}
		return '[wille_reviews ' + parts.join(' ') + ']';
	}

	function apply() {
		var s = state();
		var cards = CARD_LAYOUTS.indexOf(s.layout) !== -1;

		var items = preview.querySelectorAll('.willerev-preview__item');
		for (var i = 0; i < items.length; i++) {
			items[i].hidden = items[i].getAttribute('data-layout') !== s.layout;
		}
		var roots = preview.querySelectorAll('.willerev');
		for (var r = 0; r < roots.length; r++) {
			updateRoot(roots[r], s);
		}
		var visible = preview.querySelector('.willerev-preview__item:not([hidden]) .willerev');
		if (visible) {
			measure(visible);
		}

		var dependent = form.querySelectorAll('[data-willerev-for="cards"]');
		for (var k = 0; k < dependent.length; k++) {
			var hide = !cards || ('list' === s.layout && dependent[k].querySelector('[data-willerev="columns"]'));
			dependent[k].hidden = Boolean(hide);
		}

		var outputs = form.querySelectorAll('[data-willerev-output]');
		for (var o = 0; o < outputs.length; o++) {
			outputs[o].textContent = String(s[outputs[o].getAttribute('data-willerev-output')]);
		}
		form.querySelector('[data-willerev-shortcode]').textContent = shortcode(s);

		// The slider updates its arrows on resize (front-end script).
		window.dispatchEvent(new Event('resize'));
	}

	form.addEventListener('input', apply);
	form.addEventListener('change', apply);

	var copy = form.querySelector('[data-willerev-copy]');
	if (copy) {
		copy.addEventListener('click', function () {
			var text = form.querySelector('[data-willerev-shortcode]').textContent;
			var done = function () {
				copy.textContent = config.i18n.copied;
				window.setTimeout(function () {
					copy.textContent = config.i18n.copy;
				}, 1600);
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(done, function () {});
			} else {
				var range = document.createRange();
				range.selectNodeContents(form.querySelector('[data-willerev-shortcode]'));
				window.getSelection().removeAllRanges();
				window.getSelection().addRange(range);
			}
		});
	}

	var bgButtons = form.querySelectorAll('[data-willerev-bg]');
	for (var b = 0; b < bgButtons.length; b++) {
		bgButtons[b].addEventListener('click', function (event) {
			var dark = 'dark' === event.currentTarget.getAttribute('data-willerev-bg');
			preview.classList.toggle('is-dark', dark);
			for (var j = 0; j < bgButtons.length; j++) {
				var current = bgButtons[j] === event.currentTarget;
				bgButtons[j].classList.toggle('is-current', current);
				bgButtons[j].setAttribute('aria-pressed', current ? 'true' : 'false');
			}
		});
	}

	// Links inside the preview go nowhere.
	preview.addEventListener('click', function (event) {
		if (event.target.closest && event.target.closest('a')) {
			event.preventDefault();
		}
	});

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', apply);
	} else {
		apply();
	}
})(window.willerevAdmin);
