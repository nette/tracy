/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

class BlueScreen {
	static init(ajax) {
		BlueScreen.globalInit();

		let blueScreen = document.querySelector('.tracy-bs');

		// Shadow DOM for CSS isolation
		let host = document.createElement('tracy-bs');
		let shadow = host.attachShadow({ mode: 'open' });
		BlueScreen.shadow = shadow;
		BlueScreen.host = host;

		if (ajax) { // injected into a host page
			Tracy.adoptStyleSheets(shadow, ['shared', 'bluescreen']);
			// all bluescreen rules are scoped to .tracy-bs which lives in the shadow root,
			// document-level adoption only activates the html.tracy-bs-visible rules
			Tracy.adoptStyleSheets(document, ['bluescreen']);
		} else { // standalone error page, styles are in document.head so the page works without JavaScript
			document.querySelectorAll('style.tracy-debug').forEach((s) => {
				shadow.appendChild(s.cloneNode(true));
			});
		}

		shadow.appendChild(blueScreen);
		document.body.appendChild(host);

		document.documentElement.classList.add('tracy-bs-visible');
		if (navigator.userAgent.includes('Mac')) {
			blueScreen.classList.add('tracy-mac');
		}

		blueScreen.addEventListener('tracy-toggle', (e) => {
			let target = Tracy.retarget(e);
			if (target.matches('.tracy-bs-toggle')) { // blue screen toggle
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
			if (e.key === 'Escape' && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) {
				let toggle = BlueScreen.shadow && BlueScreen.shadow.querySelector('.tracy-bs-toggle');
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
	let footer = root && root.querySelector('footer');
	if (!footer) {
		return;
	}
	footer.classList.toggle('tracy-footer--sticky', false); // to measure footer.offsetTop
	footer.classList.toggle('tracy-footer--sticky', footer.offsetHeight + footer.offsetTop - window.innerHeight - document.documentElement.scrollTop < 0);
}

let Tracy = window.Tracy = window.Tracy || {};
Tracy.BlueScreen = Tracy.BlueScreen || BlueScreen;
