/**
 * Dumper
 *
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

(function(){

	var $ = Nette.Query.factory;

	var Dumper = Nette.Dumper = {};

	// enables <a class="nette-toggle" href="#"> or <span data-ref="#"> toggling
	Dumper.init = function() {
		$(document.body).bind('click', function(e) {
			for (var link = e.target; link && (!link.tagName || link.className.indexOf('nette-toggle') < 0); link = link.parentNode) {}
			if (!link) {
				return;
			}
			var collapsed = $(link).hasClass('nette-toggle-collapsed'),
				ref = link.getAttribute('data-ref') || link.getAttribute('href', 2),
				dest = ref && ref !== '#' ? $(ref) : $(link).next(''),
				panel = $(link).closest('.nette-panel'),
				oldPosition = panel.position();

			link.className = 'nette-toggle' + (collapsed ? '' : '-collapsed');
			dest[collapsed ? 'show' : 'hide']();
			e.preventDefault();

			var newPosition = panel.position();
			panel.position({
				right: newPosition.right - newPosition.width + oldPosition.width,
				bottom: newPosition.bottom - newPosition.height + oldPosition.height
			});
		});
	};

})();
