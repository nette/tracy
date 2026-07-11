/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

const MOVE_THRESHOLD = 100;

// enables <a class="tracy-toggle" href="#"> or <span data-tracy-ref="#"> toggling
class Toggle {
	static init() {
		let start;
		document.documentElement.addEventListener('mousedown', (e) => {
			start = [e.clientX, e.clientY];
		});

		document.documentElement.addEventListener('click', (e) => {
			let el;
			if (
				!e.shiftKey && !e.ctrlKey && !e.metaKey
				&& (el = Tracy.retarget(e).closest('.tracy-toggle'))
				&& (!start || Math.pow(start[0] - e.clientX, 2) + Math.pow(start[1] - e.clientY, 2) < MOVE_THRESHOLD) // no start = keyboard activation
			) {
				Toggle.toggle(el, undefined, e);
				e.preventDefault();
				e.stopImmediatePropagation();
			}
		});

		document.documentElement.addEventListener('keydown', (e) => {
			let el;
			if (
				(e.key === 'Enter' || e.key === ' ')
				&& !e.shiftKey && !e.ctrlKey && !e.metaKey && !e.altKey
				&& (el = Tracy.retarget(e).closest('.tracy-toggle'))
			) {
				Toggle.toggle(el, undefined, e);
				e.preventDefault(); // also suppresses the synthetic click on links
				e.stopImmediatePropagation();
			}
		});

		Toggle.init = function () {};
	}


	// changes element visibility
	static toggle(el, expand, e) {
		let collapsed = el.classList.contains('tracy-collapsed'),
			dest = Toggle.findDest(el);

		if (typeof expand === 'undefined') {
			expand = collapsed;
		}

		el.dispatchEvent(new CustomEvent('tracy-beforetoggle', {
			bubbles: true,
			composed: true,
			detail: { collapsed: !expand, originalEvent: e },
		}));

		el.classList.toggle('tracy-collapsed', !expand);
		dest.classList.toggle('tracy-collapsed', !expand);
		if (dest !== el && dest.tracyUntilFound) { // keeps collapsed content reachable by find-in-page
			if (expand) {
				dest.removeAttribute('hidden');
			} else {
				dest.setAttribute('hidden', 'until-found');
			}
		}

		el.dispatchEvent(new CustomEvent('tracy-toggle', {
			bubbles: true,
			composed: true,
			detail: { relatedTarget: dest, collapsed: !expand, originalEvent: e },
		}));
	}


	// resolves the target element controlled by a toggle
	static findDest(el) {
		let ref = el.getAttribute('data-tracy-ref') || el.getAttribute('href'),
			dest = el;

		if (!ref || ref === '#') {
			ref = '+';
		} else if (ref.substr(0, 1) === '#') {
			dest = el.getRootNode();
		}
		ref = ref.match(/(\^\s*([^+\s]*)\s*)?(\+\s*(\S*)\s*)?(.*)/);
		dest = ref[1] ? dest.parentNode : dest;
		dest = ref[2] ? dest.closest(ref[2]) : dest;
		dest = ref[3] ? Toggle.nextElement(dest.nextElementSibling, ref[4]) : dest;
		dest = ref[5] ? dest.querySelector(ref[5]) : dest;
		return dest;
	}


	// makes toggles keyboard-focusable and collapsed content reachable by the
	// browser's find-in-page (Ctrl+F auto-expands the match)
	static enhance(root) {
		root.querySelectorAll('.tracy-toggle:not([tabindex])').forEach((el) => {
			el.tabIndex = 0;
		});

		root.querySelectorAll('.tracy-toggle.tracy-collapsed').forEach((el) => {
			let dest = Toggle.findDest(el);
			if (dest && dest !== el && !dest.tracyUntilFound && dest.classList.contains('tracy-collapsed')) {
				Toggle.markUntilFound(dest, el);
			}
		});
	}


	static markUntilFound(dest, toggle) {
		dest.tracyUntilFound = true;
		dest.setAttribute('hidden', 'until-found');
		dest.addEventListener('beforematch', () => Toggle.toggle(toggle, true));
	}


	// save & restore toggles
	static persist(baseEl, restore) {
		let saved = [];
		baseEl.addEventListener('tracy-toggle', (e) => {
			if (saved.indexOf(e.target) < 0) {
				saved.push(e.target);
			}
		});

		let toggles;
		try {
			toggles = JSON.parse(sessionStorage.getItem('tracy-toggles-' + baseEl.id));
		} catch {
			// ignore corrupt data
		}
		if (toggles && restore !== false) {
			toggles.forEach((item) => {
				let el = baseEl;
				for (let i in item.path) {
					if (!(el = el.children[item.path[i]])) {
						return;
					}
				}
				if (el.textContent === item.text) {
					Toggle.toggle(el, item.expand);
				}
			});
		}

		window.addEventListener('pagehide', () => {
			toggles = saved.map((el) => {
				let item = { path: [], text: el.textContent, expand: !el.classList.contains('tracy-collapsed') };
				do {
					item.path.unshift(Array.from(el.parentNode.children).indexOf(el));
					el = el.parentNode;
				} while (el && el !== baseEl);
				return item;
			});
			sessionStorage.setItem('tracy-toggles-' + baseEl.id, JSON.stringify(toggles));
		});
	}


	// finds next matching element
	static nextElement(el, selector) {
		while (el && selector && !el.matches(selector)) {
			el = el.nextElementSibling;
		}
		return el;
	}
}


let Tracy = window.Tracy = window.Tracy || {};
Tracy.Toggle = Tracy.Toggle || Toggle;
