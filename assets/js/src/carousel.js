// Keeps each mobile card carousel's dots in step with the card in view.
// The swiping itself is plain CSS scroll-snap.

export function initCarousels() {
	document.querySelectorAll('[data-bf-carousel]').forEach((carousel) => {
		const track = carousel.querySelector('[data-bf-track]');
		const dots = [...carousel.querySelectorAll('[data-bf-dots] > *')];
		if (!track || dots.length === 0) {
			return;
		}

		const cards = [...track.children];
		const observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						const index = cards.indexOf(entry.target);
						dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
					}
				});
			},
			{ root: track, threshold: 0.6 }
		);

		cards.forEach((card) => observer.observe(card));
	});
}
