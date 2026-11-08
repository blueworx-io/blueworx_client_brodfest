// The home page's opening and its video.
//
// A first-time visitor arrives on black while the logo draws itself, then an
// Enter button appears (the head script in render.php decides, before the page
// paints). Enter is the tap that lets the browser play the video with sound,
// so the video starts then. On later visits it plays by itself, with sound,
// where the browser allows it; where it does not, it waits for the play
// button rather than playing muted: the sound only ever defaults to on
// (client, 8 Nov 2026). Either way it plays in its place, with the page and
// header as normal (Luke, 6 Nov 2026). Anyone who asks for less motion gets
// neither: the page, with a play button.

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

	sound?.addEventListener('click', () => setSound(video.muted));

	// Only after a tap or click: browsers refuse sound before one.
	const playWithSound = () => {
		setSound(true);
		video.currentTime = 0;
		video.play().catch(showPlay);
	};

	play?.addEventListener('click', playWithSound);

	return {
		playWithSound,
		// Starts the video by itself, with sound, if the browser allows it; or
		// else offers the play button, never a muted video.
		autoplay() {
			if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				showPlay();
				return;
			}
			setSound(true);
			video.play().catch(showPlay);
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
