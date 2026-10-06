/**
 * Wille Reviews – front end: "Read more" for long reviews, slider navigation, closing the floating badge.
 * Progressive enhancement: without JavaScript every review is shown in full and the slider scrolls natively.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'willerev-floating-hidden';

	/** Clamp long texts and show the toggle only where text is actually cut off. */
	function initTexts(root) {
		root.classList.add('is-enhanced');
		var more = root.getAttribute('data-more') || 'Read more';
		var less = root.getAttribute('data-less') || 'Show less';
		var cards = root.querySelectorAll('.willerev-card');
		for (var i = 0; i < cards.length; i++) {
			var text = cards[i].querySelector('.willerev-card__text');
			var button = cards[i].querySelector('.willerev-card__more');
			if (!text || !button) {
				continue;
			}
			if (text.scrollHeight - text.clientHeight > 2) {
				button.hidden = false;
				button.textContent = more;
				button.setAttribute('data-more', more);
				button.setAttribute('data-less', less);
			}
		}
	}

	/** Prev/next state of a slider. */
	function updateNav(root) {
		var track = root.querySelector('.willerev__items');
		var prev = root.querySelector('.willerev__nav--prev');
		var next = root.querySelector('.willerev__nav--next');
		if (!track || !prev || !next) {
			return;
		}
		var max = track.scrollWidth - track.clientWidth;
		var rtl = 'rtl' === window.getComputedStyle(track).direction;
		var pos = Math.abs(track.scrollLeft);
		prev.disabled = pos <= 2;
		next.disabled = pos >= max - 2;
		root.classList.toggle('is-scrollable', max > 2);
		if (rtl) {
			root.classList.add('is-rtl');
		}
	}

	function initSlider(root) {
		var track = root.querySelector('.willerev__items');
		if (!track) {
			return;
		}
		var timer = null;
		track.addEventListener('scroll', function () {
			window.clearTimeout(timer);
			timer = window.setTimeout(function () {
				updateNav(root);
			}, 60);
		}, { passive: true });
		window.addEventListener('resize', function () {
			updateNav(root);
		});
		updateNav(root);
	}

	function init() {
		var roots = document.querySelectorAll('.willerev');
		for (var i = 0; i < roots.length; i++) {
			if (roots[i].getAttribute('data-willerev-ready')) {
				continue;
			}
			roots[i].setAttribute('data-willerev-ready', '1');
			initTexts(roots[i]);
			if (roots[i].classList.contains('willerev--layout-carousel')) {
				initSlider(roots[i]);
			}
			if (roots[i].classList.contains('willerev--floating')) {
				var hidden;
				try {
					hidden = '1' === window.sessionStorage.getItem(STORAGE_KEY);
				} catch (e) {
					hidden = false; // Storage blocked (privacy mode): always show.
				}
				if (hidden) {
					roots[i].hidden = true;
				} else {
					roots[i].classList.add('is-visible');
				}
			}
		}
	}

	document.addEventListener('click', function (event) {
		var target = event.target;
		if (!target || !target.closest) {
			return;
		}

		var more = target.closest('.willerev-card__more');
		if (more) {
			var card = more.closest('.willerev-card');
			var open = !card.classList.contains('is-open');
			card.classList.toggle('is-open', open);
			more.setAttribute('aria-expanded', open ? 'true' : 'false');
			more.textContent = more.getAttribute(open ? 'data-less' : 'data-more');
			return;
		}

		var nav = target.closest('.willerev__nav');
		if (nav) {
			var root = nav.closest('.willerev');
			var track = root.querySelector('.willerev__items');
			var first = track.querySelector('.willerev-card');
			if (!first) {
				return;
			}
			var gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
			var dir = nav.classList.contains('willerev__nav--prev') ? -1 : 1;
			if (root.classList.contains('is-rtl')) {
				dir = -dir;
			}
			var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			track.scrollBy({ left: dir * (first.offsetWidth + gap), behavior: reduce ? 'auto' : 'smooth' });
			return;
		}

		var close = target.closest('.willerev-badge__close');
		if (close) {
			var badge = close.closest('.willerev');
			badge.hidden = true;
			try {
				window.sessionStorage.setItem(STORAGE_KEY, '1');
			} catch (e) {
				// Storage blocked: the badge comes back on the next page.
			}
		}
	});

	// Re-run for widgets added later (block editor preview, AJAX-loaded content).
	window.willerevInit = init;

	/** Elementor's editor preview swaps a widget's HTML after every change – enhance the new markup. */
	function watchElementorEditor() {
		// The preview frame is loaded with ?elementor-preview=<post ID> (Elementor marks the body only later).
		if (!window.MutationObserver || !document.body || -1 === window.location.search.indexOf('elementor-preview=')) {
			return;
		}
		var queued = false;
		new window.MutationObserver(function () {
			if (queued) {
				return;
			}
			queued = true;
			window.requestAnimationFrame(function () {
				queued = false;
				init();
			});
		}).observe(document.body, { childList: true, subtree: true });
	}

	function start() {
		init();
		watchElementorEditor();
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
