// The header hides as the visitor scrolls down and comes back when they scroll
// up, and opens and closes the menu.

export function initHeader() {
	const header = document.querySelector('[data-bf-header]');
	if (!header) {
		return;
	}

	initMenu(header);

	let last = window.scrollY;
	let y = last;
	let ticking = false;
	// Measured when it changes, not on every scroll, which would make the
	// browser stop and lay the page out mid-animation.
	let height = header.offsetHeight;
	new ResizeObserver(([entry]) => {
		height = entry.borderBoxSize?.[0]?.blockSize ?? header.offsetHeight;
	}).observe(header);

	window.addEventListener(
		'scroll',
		() => {
			// Read here, before anything has moved this frame; read later, it
			// would make the browser stop and lay the page out again.
			y = window.scrollY;
			if (ticking) {
				return;
			}
			ticking = true;
			requestAnimationFrame(() => {
				const menuOpen = document.body.classList.contains('is-menu-open');
				// A small dead zone so a trackpad's jitter does not flicker it.
				if (!menuOpen && Math.abs(y - last) > 6) {
					header.classList.toggle('is-hidden', y > last && y > height);
					last = y;
				}
				ticking = false;
			});
		},
		{ passive: true }
	);
}

function initMenu(header) {
	const toggle = header.querySelector('[data-bf-menu-toggle]');
	const menu = document.querySelector('[data-bf-menu]');
	if (!toggle || !menu) {
		return;
	}

	const setOpen = (open) => {
		toggle.setAttribute('aria-expanded', String(open));
		if (open) {
			menu.hidden = false;
			// Let the hidden → shown change land before the transition runs.
			requestAnimationFrame(() => document.body.classList.add('is-menu-open'));
			menu.querySelector('a')?.focus({ preventScroll: true });
		} else {
			document.body.classList.remove('is-menu-open');
			menu.addEventListener('transitionend', () => {
				if (!document.body.classList.contains('is-menu-open')) {
					menu.hidden = true;
				}
			}, { once: true });
		}
	};

	toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
			setOpen(false);
			toggle.focus();
		}
	});
}
