/**
 * This file is part of the Tracy (http://tracy.nette.org)
 */

(function(){

	Tracy = window.Tracy || {};

	// finds closing maching element
	Tracy.closest = function(el, selector, fce) {
		var matches = el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector;
		while (el && selector && !(el.nodeType === 1 && matches.call(el, selector))) {
			el = el[fce || 'parentNode'];
		}
		return el;
	};

})();
