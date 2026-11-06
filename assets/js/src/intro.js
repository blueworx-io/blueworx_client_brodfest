// The home page's opening and its video.
//
// A first-time visitor arrives on black while the logo draws itself, then an
// Enter button appears (the head script in render.php decides, before the page
// paints). Enter is the tap that lets the browser play the video with sound,
// so the video starts then. On later visits it plays by itself, with sound
// where the browser allows it (client, 6 Nov 2026); where it does not, muted
// until the visitor's first tap, click or key, unless they chose silence with
// the sound button. Either way it plays in its place, with the page and header as
// normal (Luke, 6 Nov 2026). Anyone who asks for less motion gets neither: the
// page, with a play button.

const SEEN_KEY = 'brodfest-entered';

function markSeen() {
	try {
		window.localStorage.setItem(SEEN_KEY, '1');
	} catch {
		// Private browsing can refuse storage; the opening simply shows again.
	}
}

function initVideo(hero) {
	const video = hero.querySelector('[data-bf-video]');
	const play = hero.querySelector('[data-bf-play]');
	const sound = hero.querySelector('[data-bf-sound]');

	if (!video) {
		return null;
	}

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
	});

	video.addEventListener('ended', showPlay);

	// Set once the visitor uses the sound button: their choice then stands.
	let chosen = false;
	sound?.addEventListener('click', () => {
		chosen = true;
		setSound(video.muted);
	});

	// The first tap, click or key lets the browser play sound.
	const soundOnFirstTouch = () => {
		const wake = (event) => {
			if (event.target.closest?.('[data-bf-sound]')) {
				return;
			}
			document.removeEventListener('pointerdown', wake, true);
			document.removeEventListener('keydown', wake, true);
			if (!chosen && !video.paused) {
				setSound(true);
			}
		};
		document.addEventListener('pointerdown', wake, true);
		document.addEventListener('keydown', wake, true);
	};

	// Only after a tap or click: browsers refuse sound before one.
	const playWithSound = () => {
		setSound(true);
		video.currentTime = 0;
		video.play().catch(showPlay);
	};

	play?.addEventListener('click', playWithSound);

	return {
		playWithSound,
		// Starts the video by itself, with sound if the browser allows it, or
		// else muted until the visitor's first tap, click or key.
		autoplay() {
			if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				showPlay();
				return;
			}
			setSound(true);
			video.play().catch(() => {
				setSound(false);
				video.play().then(soundOnFirstTouch, showPlay);
			});
		},
	};
}

export function initIntro() {
	const hero = document.querySelector('[data-bf-hero]');
	const opening = document.querySelector('[data-bf-opening]');
	const video = hero ? initVideo(hero) : null;

	if (!opening || !document.documentElement.classList.contains('bf-is-opening')) {
		opening?.remove();
		video?.autoplay();
		return;
	}

	const enter = opening.querySelector('[data-bf-enter]');
	enter.focus({ preventScroll: true });

	enter.addEventListener(
		'click',
		() => {
			markSeen();
			video?.playWithSound();
			opening.classList.add('is-leaving');
			const done = () => {
				document.documentElement.classList.remove('bf-is-opening');
				opening.remove();
			};
			opening.addEventListener('animationend', (event) => {
				if (event.target === opening) {
					done();
				}
			});
			// In case the browser runs no animations at all.
			setTimeout(done, 1000);
		},
		{ once: true },
	);
}
