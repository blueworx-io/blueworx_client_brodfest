// Add to Calendar opens its choice of calendars, rather than downloading the
// file, which most desktop browsers only save. Without JavaScript the button
// is the file itself.

export function initCalendar() {
	const box = document.querySelector('[data-bf-calendar]');
	const button = box?.querySelector('[data-bf-calendar-toggle]');
	const menu = box?.querySelector('[data-bf-calendar-menu]');
	if (!button || !menu) {
		return;
	}

	const set = (open) => {
		menu.hidden = !open;
		button.setAttribute('aria-expanded', open ? 'true' : 'false');
	};

	button.addEventListener('click', (event) => {
		event.preventDefault();
		set(menu.hidden);
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && !menu.hidden) {
			set(false);
			button.focus();
		}
	});

	document.addEventListener('pointerdown', (event) => {
		if (!menu.hidden && !box.contains(event.target)) {
			set(false);
		}
	});

	// Choosing a calendar closes the choices.
	menu.addEventListener('click', () => set(false));
}
