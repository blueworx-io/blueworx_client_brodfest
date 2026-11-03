// Shows the guest's fields only once the visitor says they are bringing one.
// Without JavaScript every field shows, which still works.

export function initRsvp() {
	const form = document.querySelector('[data-bf-rsvp]');
	const guest = form?.querySelector('[data-bf-guest]');
	if (!guest) {
		return;
	}

	const sync = () => {
		const yes = form.querySelector('[data-bf-guest-toggle][value="yes"]').checked;
		guest.hidden = !yes;
		guest.querySelectorAll('input, select').forEach((field) => {
			field.disabled = !yes;
		});
	};

	form.querySelectorAll('[data-bf-guest-toggle]').forEach((radio) => radio.addEventListener('change', sync));
	sync();
}
