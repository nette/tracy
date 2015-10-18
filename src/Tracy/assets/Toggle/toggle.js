/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

(function() {
	Tracy = window.Tracy || {};

	Tracy.Toggle = Tracy.Toggle || {};

	// enables <a class="tracy-toggle" href="#"> or <span data-tracy-ref="#"> toggling
	Tracy.Toggle.init = function() {
		document.body.addEventListener('click', function(e) {
			var link;

			if (e.shiftKey || e.altKey || e.ctrlKey || e.metaKey) {
				return;
			}

			if (link = Tracy.closest(e.target, '.tracy-toggle')) {
				var collapsed = link.classList.contains('tracy-collapsed'),
					ref = link.getAttribute('data-tracy-ref') || link.getAttribute('href', 2),
					dest = link;

				if (!ref || ref === '#') {
					ref = '+';
				} else if (ref.substr(0, 1) === '#') {
					dest = document;
				}
				ref = ref.match(/(\^\s*([^+\s]*)\s*)?(\+\s*(\S*)\s*)?(.*)/);
				dest = ref[1] ? Tracy.closest(dest.parentNode, ref[2]) : dest;
				dest = ref[3] ? Tracy.closest(dest.nextElementSibling, ref[4], 'nextElementSibling') : dest;
				dest = ref[5] ? dest.querySelector(ref[5]) : dest;

				link.classList.toggle('tracy-collapsed', !collapsed);
				dest.classList.toggle('tracy-collapsed', !collapsed);
				e.preventDefault();
			}
		});
		this.init = function() {};
	};


	// finds closing maching element
	Tracy.closest = function(el, selector, func) {
		var matches = el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector;
		while (el && selector && !(el.nodeType === 1 && matches.call(el, selector))) {
			el = el[func || 'parentNode'];
		}
		return el;
	};

})();
