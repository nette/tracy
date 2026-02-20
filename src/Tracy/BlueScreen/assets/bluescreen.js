/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

class BlueScreen {
	static init(ajax) {
		BlueScreen.globalInit();

		let blueScreen = document.getElementById('tracy-bs');

		// Shadow DOM for CSS isolation
		let host = document.createElement('tracy-bs');
		let shadow = host.attachShadow({ mode: 'open' });
		BlueScreen.shadow = shadow;

		// Clone CSS into shadow root
		document.querySelectorAll('style.tracy-debug').forEach((s) => {
			shadow.appendChild(s.cloneNode(true));
		});

		shadow.appendChild(blueScreen);
		document.body.appendChild(host);

		document.documentElement.classList.add('tracy-bs-visible');
		if (navigator.platform.indexOf('Mac') > -1) {
			blueScreen.classList.add('tracy-mac');
		}

		blueScreen.addEventListener('tracy-toggle', (e) => {
			let target = e.composedPath()[0] || e.target;
			if (target.matches('#tracy-bs-toggle')) { // blue screen toggle
				document.documentElement.classList.toggle('tracy-bs-visible', !e.detail.collapsed);

			} else if (!target.matches('.tracy-dump *') && e.detail.originalEvent) { // panel toggle
				e.detail.relatedTarget.classList.toggle('tracy-panel-fadein', !e.detail.collapsed);
			}
		});

		if (!ajax) {
			let id = location.href + shadow.querySelector('.tracy-section--error').textContent;
			Tracy.Toggle.persist(blueScreen, sessionStorage.getItem('tracy-toggles-bskey') === id);
			sessionStorage.setItem('tracy-toggles-bskey', id);
		}

		Tracy.Dumper.init(shadow);
		(new ResizeObserver(() => stickyFooter(shadow))).observe(blueScreen);

		if (document.documentElement.classList.contains('tracy-bs-visible')) {
			blueScreen.scrollIntoView();
		}
	}


	static globalInit() {
		// enables toggling via ESC
		document.addEventListener('keyup', (e) => {
			if (e.keyCode === 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) { // ESC
				let toggle = BlueScreen.shadow && BlueScreen.shadow.querySelector('#tracy-bs-toggle');
				if (toggle) {
					Tracy.Toggle.toggle(toggle);
				}
			}
		});

		Tracy.TableSort.init();
		Tracy.Tabs.init();

		window.addEventListener('scroll', () => stickyFooter(BlueScreen.shadow));

		BlueScreen.globalInit = function () {};
	}


	static loadAjax(content) {
		let host = document.querySelector('tracy-bs');
		if (host) {
			host.remove();
		}
		document.body.insertAdjacentHTML('beforeend', content);
		BlueScreen.init(true);
	}
}

function stickyFooter(root) {
	let footer = root && root.querySelector('#tracy-bs footer');
	if (!footer) {
		return;
	}
	footer.classList.toggle('tracy-footer--sticky', false); // to measure footer.offsetTop
	footer.classList.toggle('tracy-footer--sticky', footer.offsetHeight + footer.offsetTop - window.innerHeight - document.documentElement.scrollTop < 0);
}

let Tracy = window.Tracy = window.Tracy || {};
Tracy.BlueScreen = Tracy.BlueScreen || BlueScreen;
