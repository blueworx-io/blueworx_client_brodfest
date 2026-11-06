// The site's motion (Luke, 6 Nov 2026): slow and soft, each section easing in
// once as it scrolls into view, nothing looping or moving while it is read.
// The head script in render.php marks the page (html.bf-motion) only when the
// visitor has not asked for less motion, and hides the content until this has
// set every section's starting point, so nothing jumps; it shows the content
// anyway after 3 seconds if this never runs. Everything ends exactly where the
// page lays it out, and the inline styles are cleared once done.
//
// Smoothness: everything moves by sliding, fading or scaling, which the
// graphics card does without the page being laid out again; nothing animates
// letter spacing or a cut-out shape. Text is split into lines and letters
// once its fonts are in, before it is seen, never while the page scrolls.

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

// Soft from the first frame: expo's opening burst reads as a jump.
const EASE = 'power3.out';
const START = 'top 90%';

const TITLES = '.bf-main .bf-title, .bf-main .bf-area__name';
const COPY = '.bf-main .bf-text .bf-body, .bf-main .bf-area__intro, .bf-main .bf-rsvp__message';

// Runs once the home page's opening has gone, so its motion is seen.
function afterOpening(run) {
	const root = document.documentElement;
	if (!root.classList.contains('bf-is-opening')) {
		run();
		return;
	}
	const watch = new MutationObserver(() => {
		if (!root.classList.contains('bf-is-opening')) {
			watch.disconnect();
			run();
		}
	});
	watch.observe(root, { attributes: true, attributeFilter: ['class'] });
}

// Something growing or shrinking is told so ahead of time, or the browser
// redraws it from scratch at every size on the way; cleared once it is done.
const prepareScale = (targets) => gsap.set(targets, { willChange: 'transform' });

// A button's own CSS transition would smooth each step GSAP takes all over
// again, so it trailed behind; it is held off while GSAP moves the button.
const hold = (targets) => gsap.set(targets, { transition: 'none' });

// GSAP reads where each element sits once, here while the page is set up,
// rather than the moment its reveal begins mid-scroll, where reading the page
// makes the browser stop and lay it out.
const measure = (targets) => gsap.utils.toArray(targets).forEach((el) => gsap.getProperty(el, 'x'));

// Hands the targets back to the page's own styles, again without reading it.
const release = (targets, props) => () => gsap.utils.toArray(targets).forEach((el) => props.forEach((prop) => el.style.removeProperty(prop)));

// Where every starting point settles: shown, in place, at full size. Given
// outright, so GSAP never has to read the page to find out.
const SETTLED = { opacity: 1, x: 0, y: 0, scale: 1 };
const settled = (from) => Object.fromEntries(Object.keys(from).map((key) => [key, SETTLED[key]]));

// Eases targets in from `from` when `trigger` scrolls into view.
function reveal(targets, from, trigger, extra = {}) {
	if ('scale' in from) {
		prepareScale(targets);
	}
	measure(targets);
	return gsap.fromTo(targets, from, {
		...settled(from),
		duration: 1.2,
		ease: EASE,
		scrollTrigger: { trigger, start: START, once: true },
		onComplete: release(targets, ['transform', 'opacity', 'will-change']),
		...extra,
	});
}

// Titles: the letters start a little spread from the centre and drift closed
// into place as they fade up, so the wide spacing seems to draw together. The
// drift is gentle from its first frame and fades in over its first half; a
// fast start read as the letters jumping from wide to narrow.
function titles() {
	gsap.utils.toArray(TITLES).forEach((el) => {
		const split = SplitText.create(el, { type: 'words,chars', wordsClass: 'bf-word', charsClass: 'bf-char' });
		const box = el.getBoundingClientRect();
		const centre = box.left + box.width / 2;
		// Measured all at once, before anything moves.
		const offsets = split.chars.map((char) => {
			const r = char.getBoundingClientRect();
			return (r.left + r.width / 2 - centre) * 0.25;
		});
		measure(split.chars);
		gsap.set(el, { clearProps: 'opacity' });
		// Redrawing one line of letters costs less than a layer for each.
		gsap.set(split.chars, { opacity: 0, x: (i) => offsets[i], force3D: false });
		// Left split once settled: put back together, its letters would close up
		// a hair further as the font's pairing returns, a visible last nudge.
		gsap
			.timeline({ scrollTrigger: { trigger: el, start: START, once: true }, onComplete: release(split.chars, ['transform', 'opacity']) })
			.to(split.chars, { x: 0, duration: 1.8, ease: 'power2.out', force3D: false }, 0)
			.to(split.chars, { opacity: 1, duration: 0.9, ease: 'power1.inOut' }, 0);
	});
	gsap.utils.toArray('.bf-area__distance').forEach((el) => reveal(el, { opacity: 0, y: 12 }, el, { delay: 0.3 }));
}

// Copy: rises in line by line, then is put back as it was. Split ahead of
// time, and again should the window change size before it is seen.
function copy() {
	gsap.utils.toArray(COPY).forEach((el) => {
		gsap.set(el, { clearProps: 'opacity' });
		SplitText.create(el, {
			type: 'lines',
			mask: 'lines',
			linesClass: 'bf-line',
			autoSplit: true,
			onSplit: (split) => {
				measure(split.lines);
				// In pixels, worked out now: a share of each line's own height
				// would have to be looked up as each line starts to move.
				const drop = split.lines[0]?.offsetHeight ?? 0;
				return gsap.fromTo(
					split.lines,
					{ y: drop, opacity: 0 },
					{
						y: 0,
						opacity: 1,
						duration: 1.2,
						ease: EASE,
						stagger: 0.08,
						scrollTrigger: { trigger: el, start: START, once: true },
						onComplete: () => split.revert(),
					}
				);
			},
		});
	});
}

// The header's contents settle in as each page arrives.
function header() {
	const parts = gsap.utils.toArray('.bf-header__toggle, .bf-header__logo, .bf-header .bf-pill');
	hold(parts);
	gsap.from(parts, { opacity: 0, y: -16, duration: 1, ease: EASE, stagger: 0.08, clearProps: 'opacity,transform,transition' });
}

// The menu's links rise in, one after another, each time it opens.
function menu() {
	const links = gsap.utils.toArray('.bf-menu__items a');
	if (links.length === 0) {
		return;
	}
	new MutationObserver(() => {
		if (document.body.classList.contains('is-menu-open')) {
			gsap.fromTo(links, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.9, ease: EASE, stagger: 0.07, delay: 0.1, clearProps: 'all' });
		}
	}).observe(document.body, { attributes: true, attributeFilter: ['class'] });
}

// Home: the video settles from a slight zoom, the date and lettering are
// written in left to right, and the button and line under it follow.
function home() {
	const video = document.querySelector('[data-bf-video]');
	video?.addEventListener(
		'playing',
		() => {
			prepareScale(video);
			gsap.fromTo(video, { scale: 1.08 }, { scale: 1, duration: 2.6, ease: 'power2.out', clearProps: 'transform,willChange' });
		},
		{ once: true }
	);

	const parts = gsap.utils.toArray('.bf-home__date, .bf-home__lettering');
	const rest = gsap.utils.toArray('.bf-home .bf-calendar, .bf-home__note');
	if (parts.length === 0) {
		return;
	}
	gsap.set(parts, { clipPath: 'inset(0 100% 0 0)' });
	gsap.set(rest, { opacity: 0, y: 24 });
	afterOpening(() => {
		const play = gsap.timeline({ scrollTrigger: { trigger: '.bf-home', start: START, once: true } });
		play.to(parts, { clipPath: 'inset(0 0% 0 0)', duration: 1.4, ease: 'power3.inOut', stagger: 0.25, clearProps: 'clipPath' });
		play.to(rest, { opacity: 1, y: 0, duration: 1.1, ease: EASE, stagger: 0.12, clearProps: 'opacity,transform' }, '-=0.6');
	});
}

// The map's glow fades up and the island settles.
function map() {
	gsap.utils.toArray('.bf-map--image').forEach((el) => reveal(el, { opacity: 0, scale: 0.96 }, el, { duration: 1.6 }));
}

// Area photos: a curtain as they arrive, its edge sweeping down the photo;
// on desktop the photo then drifts a little slower than the page, for depth.
// The curtain is the photo's frame sliding down into its slot while the photo
// inside is held still, rather than a cut-out shape redrawn every frame.
function banners() {
	const desktop = window.matchMedia('(min-width: 1024px)').matches;
	gsap.utils.toArray('.bf-area .bf-banner').forEach((banner) => {
		const frame = banner.querySelector('picture');
		const img = banner.querySelector('img');
		if (!frame || !img) {
			return;
		}
		const curtain = { duration: 1.4, ease: 'power3.inOut' };
		// The slot's own colour would show where the photo has not yet reached.
		gsap.set(banner, { backgroundColor: 'transparent' });
		measure([frame, img]);
		const height = frame.offsetHeight;
		gsap
			.timeline({ scrollTrigger: { trigger: banner, start: START, once: true }, onComplete: release([banner, frame], ['background-color', 'transform']) })
			.fromTo(frame, { y: -height }, { y: 0, ...curtain }, 0)
			.fromTo(img, { y: height }, { y: 0, ...curtain }, 0);
		if (desktop) {
			// Enlarged just enough that the drift never shows an edge. The drift
			// trails the scroll by a moment, so it never stutters against it.
			gsap.set(img, { scale: 1.16 });
			gsap.fromTo(img, { yPercent: -6 }, { yPercent: 6, ease: 'none', scrollTrigger: { trigger: banner, start: 'top bottom', end: 'bottom top', scrub: 0.6 } });
		} else {
			reveal(img, { scale: 1.12 }, banner, { duration: 1.8 });
		}
	});
}

// Hotel and recommendation cards: each row's cards rise in one after another.
// Their photos are left still: a row of photos zooming at once was the one
// thing on the site heavy enough to stutter.
function cards() {
	gsap.utils.toArray('[data-bf-track]').forEach((track) => {
		reveal(gsap.utils.toArray(track.children), { opacity: 0, y: 60 }, track, { stagger: 0.12 });
	});
}

// RSVP: the fields rise in one after another, on the page and in the panel.
function rsvp(root = document) {
	const fields = root.querySelectorAll('.bf-rsvp .bf-title, .bf-form > :not(input[type="hidden"]):not(.bf-form__trap), .bf-rsvp__message, .bf-rsvp__close');
	if (fields.length > 0) {
		hold(fields);
		gsap.from(fields, { opacity: 0, y: 24, duration: 0.9, ease: EASE, stagger: 0.05, delay: 0.15, clearProps: 'opacity,transform,transition' });
	}
}

// The footer's mountain builds up from its base.
function footer() {
	gsap.utils.toArray('.bf-footer__mountain').forEach((el) => {
		gsap.fromTo(
			el,
			{ clipPath: 'inset(100% 0 0 0)' },
			{ clipPath: 'inset(0% 0 0 0)', duration: 1.4, ease: 'power3.out', clearProps: 'clipPath', scrollTrigger: { trigger: el, start: 'top 98%', once: true } }
		);
	});
}

export function initAnimations() {
	const root = document.documentElement;
	if (!root.classList.contains('bf-motion')) {
		return;
	}
	gsap.registerPlugin(ScrollTrigger, SplitText);
	// A phone's address bar showing and hiding is not a resize worth
	// re-measuring the page for mid-scroll.
	ScrollTrigger.config({ ignoreMobileResize: true });

	header();
	menu();
	home();
	map();
	banners();
	cards();
	footer();
	if (!document.querySelector('[data-bf-rsvp-panel]')) {
		rsvp();
	}
	document.addEventListener('bf:rsvp-shown', (event) => rsvp(event.target));

	// Text is split once its fonts are in, so its lines and letters fall where
	// the page will set them; it is held invisible until then. Laying the page
	// out first is what asks for the fonts.
	gsap.set(gsap.utils.toArray(`${TITLES}, ${COPY}`), { opacity: 0 });
	void document.body.offsetHeight;
	(document.fonts ? document.fonts.ready : Promise.resolve()).then(() => {
		titles();
		copy();
		// Every starting point is set: let the page fade in.
		root.classList.add('bf-motion-ready');
	});
}
