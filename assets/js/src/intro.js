// The home page's first-visit intro and its video.
//
// First visit: the intro plays and ends on Enter. That click is what lets the
// browser play the video with sound, so the video starts then, once. After it
// ends, and on every later visit, a play button offers a replay instead — the
// video never starts by itself again.

const SEEN_KEY = 'brodfest-intro-seen';

function seen() {
	try {
		return window.localStorage.getItem(SEEN_KEY) === '1';
	} catch {
		return false;
	}
}

function markSeen() {
	try {
		window.localStorage.setItem(SEEN_KEY, '1');
	} catch {
		// Private browsing can refuse storage; the intro simply shows again.
	}
}

export function initIntro() {
	const hero = document.querySelector('[data-bf-hero]');
	if (!hero) {
		return;
	}

	const video = hero.querySelector('[data-bf-video]');
	const play = hero.querySelector('[data-bf-play]');

	const showPlay = () => {
		if (play) {
			play.hidden = false;
		}
	};

	const playWithSound = () => {
		if (!video) {
			return;
		}
		video.muted = false;
		video.currentTime = 0;
		if (play) {
			play.hidden = true;
		}
		video.play().catch(showPlay);
	};

	video?.addEventListener('ended', showPlay);
	play?.addEventListener('click', playWithSound);

	if (seen()) {
		showPlay();
		return;
	}

	const template = document.querySelector('template[data-bf-intro]');
	if (!template) {
		showPlay();
		return;
	}

	document.body.append(template.content.cloneNode(true));
	const intro = document.body.lastElementChild;
	const enter = intro.querySelector('[data-bf-enter]');
	document.documentElement.style.overflow = 'hidden';
	enter.focus({ preventScroll: true });
	// Focus lands on Enter by itself; its ring shows once a key is pressed.
	intro.addEventListener('keydown', () => intro.setAttribute('data-bf-keys', ''), { once: true });

	enter.addEventListener('click', () => {
		markSeen();
		playWithSound();
		intro.classList.add('is-leaving');
		document.documentElement.style.overflow = '';
		const done = () => intro.remove();
		intro.addEventListener('transitionend', done, { once: true });
		// Reduced motion has no transition to end.
		setTimeout(done, 1500);
	});
}
