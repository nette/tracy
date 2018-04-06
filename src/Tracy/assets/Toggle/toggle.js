/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

(function() {

	// enables <a class="tracy-toggle" href="#"> or <span data-tracy-ref="#"> toggling
	class Toggle
	{
		static init() {
			document.documentElement.addEventListener('click', function(e) {
				var el = e.target.closest('.tracy-toggle');
				if (el && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) {
					Toggle.toggle(el);
				}
			});
			Toggle.init = function() {};
		}


		// changes element visibility
		static toggle(el, show) {
			var collapsed = el.classList.contains('tracy-collapsed'),
				ref = el.getAttribute('data-tracy-ref') || el.getAttribute('href', 2),
				dest = el;

			if (typeof show === 'undefined') {
				show = collapsed;
			} else if (!show === collapsed) {
				return;
			}

			if (!ref || ref === '#') {
				ref = '+';
			} else if (ref.substr(0, 1) === '#') {
				dest = document;
			}
			ref = ref.match(/(\^\s*([^+\s]*)\s*)?(\+\s*(\S*)\s*)?(.*)/);
			dest = ref[1] ? dest.parentNode : dest;
			dest = ref[2] ? dest.closest(ref[2]) : dest;
			dest = ref[3] ? Toggle.nextElement(dest.nextElementSibling, ref[4]) : dest;
			dest = ref[5] ? dest.querySelector(ref[5]) : dest;

			el.classList.toggle('tracy-collapsed', !show);
			dest.classList.toggle('tracy-collapsed', !show);

			if (typeof window.Event === 'function') {
				var toggleEvent = new Event('tracy-toggle', {bubbles: true});
			} else {
				toggleEvent = document.createEvent('Event');
				toggleEvent.initEvent('tracy-toggle', true, false);
			}
			el.dispatchEvent(toggleEvent);
		}


		// save & restore toggles
		static persist(baseEl, restore) {
			var saved = [];
			baseEl.addEventListener('tracy-toggle', function(e) {
				if (saved.indexOf(e.target) < 0) {
					saved.push(e.target);
				}
			});

			var toggles = JSON.parse(sessionStorage.getItem('tracy-toggles-' + baseEl.id));
			if (toggles && restore !== false) {
				toggles.forEach(function(item) {
					var el = baseEl;
					for (var i in item.path) {
						if (!(el = el.children[item.path[i]])) {
							return;
						}
					}
					if (el.textContent === item.text) {
						Toggle.toggle(el, item.show);
					}
				});
			}

			window.addEventListener('unload', function() {
				toggles = [].map.call(saved, function(el) {
					var item = {path: [], text: el.textContent, show: !el.classList.contains('tracy-collapsed')};
					do {
						item.path.unshift([].indexOf.call(el.parentNode.children, el));
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


	Tracy = window.Tracy || {};
	Tracy.Toggle = Tracy.Toggle || Toggle;
})();
