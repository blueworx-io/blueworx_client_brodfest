// RSVP lowers over the page the guest is on, under the header, and goes back
// up when they close it or have replied (client, 6 Nov 2026). It loads the
// RSVP page's own form and sends it there, so the reply is handled exactly as
// on that page; the address never changes. The RSVP pill, the header's close
// button and Esc close it. Without JavaScript, or if the form cannot load, the
// pill opens the RSVP page as before. On that page itself, closing goes back
// to the page the guest came from.

import { initRsvp } from './rsvp.js';

const isPlainClick = (event) => event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;

const RETURN_KEY = 'brodfest-rsvp-return';

// The page the guest opened RSVP from, kept through sending the form, which
// reloads the RSVP page. None when they arrived from elsewhere.
function returnPage() {
	try {
		const from = document.referrer ? new URL(document.referrer) : null;
		if (!from || from.origin !== window.location.origin) {
			window.sessionStorage.removeItem(RETURN_KEY);
			return null;
		}
		if (from.pathname !== window.location.pathname) {
			window.sessionStorage.setItem(RETURN_KEY, from.href);
		}
		return window.sessionStorage.getItem(RETURN_KEY);
	} catch {
		return null;
	}
}

// The RSVP page: its close button, its CLOSE and the pill go back to the page
// the guest came from, or else home.
function initRsvpPage(pill) {
	const target = returnPage() || document.querySelector('[data-bf-close]')?.href;
	const back = (event) => {
		if (isPlainClick(event) && target) {
			event.preventDefault();
			window.location.href = target;
		}
	};
	pill.addEventListener('click', back);
	document.querySelector('[data-bf-close]')?.addEventListener('click', back);
	document.querySelector('.bf-rsvp__close a')?.addEventListener('click', back);
}

export function initRsvpPanel() {
	const header = document.querySelector('[data-bf-header]');
	const pill = header?.querySelector('.bf-pill');
	if (!pill) {
		return;
	}

	if (document.querySelector('[data-bf-close]')) {
		initRsvpPage(pill);
		return;
	}

	const toggle = header.querySelector('[data-bf-menu-toggle]');
	const panel = document.createElement('div');
	panel.className = 'bf-rsvp-panel';
	panel.hidden = true;
	panel.setAttribute('role', 'dialog');
	panel.setAttribute('aria-modal', 'true');
	panel.setAttribute('aria-label', 'RSVP');
	panel.dataset.bfRsvpPanel = '';
	header.after(panel);

	const isOpen = () => document.body.classList.contains('is-rsvp-open');

	// Puts the RSVP page's form into the panel, from that page's HTML.
	const show = (html) => {
		const form = new DOMParser().parseFromString(html, 'text/html').querySelector('.bf-rsvp');
		if (!form) {
			return false;
		}
		panel.replaceChildren(document.importNode(form, true));
		initRsvp(panel);
		panel.querySelector('[data-bf-rsvp]')?.addEventListener('submit', send);
		panel.querySelector('.bf-rsvp__close a')?.addEventListener('click', (event) => {
			if (isPlainClick(event)) {
				event.preventDefault();
				close();
			}
		});
		return true;
	};

	async function send(event) {
		event.preventDefault();
		const form = event.currentTarget;
		form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
		try {
			const response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
			if (!show(await response.text())) {
				throw new Error('No form in the reply');
			}
			panel.scrollTop = 0;
			panel.querySelector('[role="status"], [role="alert"]')?.setAttribute('tabindex', '-1');
			panel.querySelector('[role="status"], [role="alert"]')?.focus({ preventScroll: true });
		} catch {
			// Sent the ordinary way instead, on the RSVP page.
			form.removeEventListener('submit', send);
			form.submit();
		}
	}

	async function open() {
		if (toggle?.getAttribute('aria-expanded') === 'true') {
			toggle.click();
		}
		try {
			const response = await fetch(pill.href, { credentials: 'same-origin' });
			if (!show(await response.text())) {
				throw new Error('No form on the page');
			}
		} catch {
			window.location.href = pill.href;
			return;
		}
		panel.hidden = false;
		panel.scrollTop = 0;
		header.classList.remove('is-hidden');
		pill.classList.add('is-active');
		pill.setAttribute('aria-expanded', 'true');
		document.documentElement.classList.add('is-rsvp-locked');
		// Let the hidden → shown change land before the panel lowers.
		requestAnimationFrame(() => requestAnimationFrame(() => document.body.classList.add('is-rsvp-open')));
		panel.querySelector('input:not([type="hidden"]):not([tabindex="-1"])')?.focus({ preventScroll: true });
	}

	function close() {
		document.body.classList.remove('is-rsvp-open');
		document.documentElement.classList.remove('is-rsvp-locked');
		pill.classList.remove('is-active');
		pill.setAttribute('aria-expanded', 'false');
		pill.focus({ preventScroll: true });
		const done = () => {
			if (!isOpen()) {
				panel.hidden = true;
				panel.replaceChildren();
			}
		};
		panel.addEventListener('transitionend', done, { once: true });
		// Reduced motion has no transition to end.
		setTimeout(done, 1000);
	}

	pill.setAttribute('aria-expanded', 'false');
	pill.addEventListener('click', (event) => {
		if (!isPlainClick(event)) {
			return;
		}
		event.preventDefault();
		if (isOpen()) {
			close();
		} else {
			open();
		}
	});

	// While RSVP is open the menu button is its close button.
	document.addEventListener(
		'click',
		(event) => {
			if (isOpen() && toggle && toggle.contains(event.target)) {
				event.preventDefault();
				event.stopImmediatePropagation();
				close();
			}
		},
		true
	);

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isOpen()) {
			close();
		}
	});
}
