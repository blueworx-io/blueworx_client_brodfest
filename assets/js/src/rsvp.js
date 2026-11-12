// Shows the partner question and accessibility tick only to someone who says
// they are coming (Mark, 12 Nov 2026), and the partner's fields only once they
// say they are bringing one. Whatever shows must be filled in; whatever is
// hidden is switched off, so it is neither checked nor sent. Without
// JavaScript every field shows, which still works: the server checks the same
// things. Runs on the RSVP page, and on the form each time the RSVP panel
// loads it.

export function initRsvp(root = document) {
	const form = root.querySelector('[data-bf-rsvp]');
	const attending = form?.querySelector('[data-bf-attending]');
	const guest = form?.querySelector('[data-bf-guest]');
	if (!attending || !guest) {
		return;
	}

	const checked = (selector) => form.querySelector(selector)?.checked ?? false;

	const sync = () => {
		const coming = checked('[data-bf-attending-toggle][value="yes"]');
		const bringing = coming && checked('[data-bf-guest-toggle][value="yes"]');

		attending.hidden = !coming;
		attending.querySelectorAll('input, select').forEach((field) => {
			field.disabled = !coming;
		});
		form.querySelectorAll('[data-bf-guest-toggle]').forEach((radio) => {
			radio.required = coming;
		});

		guest.hidden = !bringing;
		guest.querySelectorAll('input, select').forEach((field) => {
			field.disabled = !bringing;
			field.required = bringing;
		});
	};

	form.querySelectorAll('[data-bf-attending-toggle], [data-bf-guest-toggle]').forEach((radio) => radio.addEventListener('change', sync));
	sync();
}
