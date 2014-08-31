/**
 * This file is part of the Tracy (http://tracy.nette.org)
 */

(function(){

	Tracy = window.Tracy || {};

	// adds class to element
	Tracy.addClass = function(el, className) {
		el.className = (el.className.replace(/^|\s+|$/g, ' ').replace(' ' + className + ' ', ' ') + ' ' + className).trim();
	};

	// removes class from element
	Tracy.removeClass = function(el, className) {
		el.className = el.className.replace(/^|\s+|$/g, ' ').replace(' ' + className + ' ', ' ').trim();
	};

	// tests whether element has given class
	Tracy.hasClass = function(el, className) {
		return typeof el.className === 'string' && el.className.replace(/^|\s+|$/g, ' ').indexOf(' ' + className + ' ') > -1;
	};

	// finds closing maching element
	Tracy.closest = function(el, selector, fce) {
		var matches = el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector;
		while (el && selector && !(el.nodeType === 1 && matches.call(el, selector))) {
			el = el[fce || 'parentNode'];
		}
		return el;
	};

})();
