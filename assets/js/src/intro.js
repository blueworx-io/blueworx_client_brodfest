// The home page's opening and its video.
//
// A first-time visitor arrives on black while the logo draws itself, and it
// fades to the page (the head script in render.php decides, before the page
// paints). The video then plays by itself, muted, filling the window over the
// menu, until it ends or the visitor scrolls, taps or presses Esc; it plays on
// in its place. Anyone who asks for less motion gets neither: the page, with a
// play button.

const SEEN_KEY = 'brodfest-intro-seen';

function markSeen() {
	try {
		window.localStorage.setItem(SEEN_KEY, '1');
	} catch {
		// Private browsing can refuse storage; the opening simply shows again.
	}
}

// Keys that only move focus leave the full-window video be.
const FOCUS_KEYS = ['Tab', 'Shift'];

function initVideo(hero) {
	const video = hero.querySelector('[data-bf-video]');
	const play = hero.querySelector('[data-bf-play]');
	const sound = hero.querySelector('[data-bf-sound]');

	if (!video) {
		return () => {};
	}

	const leaveCinema = () => {
		document.body.classList.remove('is-cinema');
		window.removeEventListener('wheel', leaveCinema);
		window.removeEventListener('touchmove', leaveCinema);
		window.removeEventListener('scroll', leaveCinema);
		document.removeEventListener('pointerdown', onPointer);
		document.removeEventListener('keydown', onKey);
	};

	function onPointer(event) {
		if (!event.target.closest('[data-bf-sound]')) {
			leaveCinema();
		}
	}

	function onKey(event) {
		const onSound = event.target.closest?.('[data-bf-sound]') && ['Enter', ' '].includes(event.key);
		if (!FOCUS_KEYS.includes(event.key) && !onSound) {
			leaveCinema();
		}
	}

	const enterCinema = () => {
		document.body.classList.add('is-cinema');
		window.addEventListener('wheel', leaveCinema, { passive: true });
		window.addEventListener('touchmove', leaveCinema, { passive: true });
		window.addEventListener('scroll', leaveCinema, { passive: true });
		document.addEventListener('pointerdown', onPointer);
		document.addEventListener('keydown', onKey);
	};

	const setSound = (on) => {
		video.muted = !on;
		sound?.setAttribute('aria-pressed', on ? 'true' : 'false');
	};

	const showPlay = () => {
		if (play) {
			play.hidden = false;
		}
		if (sound) {
			sound.hidden = true;
		}
	};

	video.addEventListener('play', () => {
		if (play) {
			play.hidden = true;
		}
		if (sound) {
			sound.hidden = false;
		}
		enterCinema();
	});

	video.addEventListener('ended', () => {
		leaveCinema();
		showPlay();
	});

	sound?.addEventListener('click', () => setSound(video.muted));

	play?.addEventListener('click', () => {
		setSound(true);
		video.currentTime = 0;
		video.play().catch(showPlay);
	});

	// Starts the video by itself, muted, as browsers allow.
	return () => {
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			showPlay();
			return;
		}
		setSound(false);
		video.play().catch(showPlay);
	};
}

export function initIntro() {
	const hero = document.querySelector('[data-bf-hero]');
	const opening = document.querySelector('[data-bf-opening]');
	const start = hero ? initVideo(hero) : () => {};

	if (!opening || !document.documentElement.classList.contains('bf-is-opening')) {
		opening?.remove();
		start();
		return;
	}

	markSeen();
	let started = false;
	const begin = () => {
		if (!started) {
			started = true;
			start();
		}
	};
	const done = () => {
		begin();
		document.documentElement.classList.remove('bf-is-opening');
		opening.remove();
	};

	// The video starts as the opening begins to fade, so the fade reveals it.
	opening.addEventListener('animationstart', (event) => {
		if (event.target === opening) {
			begin();
		}
	});
	opening.addEventListener('animationend', (event) => {
		if (event.target === opening) {
			done();
		}
	});
	// In case the browser runs no animations at all.
	setTimeout(done, 3000);
}
