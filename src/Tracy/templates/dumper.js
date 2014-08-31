/**
 * Dumper
 *
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

(function(){

	Tracy.Dumper = Tracy.Dumper || {};

	Tracy.Dumper.init = function() {
		if (this.inited) {
			return;
		}
		this.inited = true;

		document.body.addEventListener('click', function(e) {
			var link;

			// enables <span data-tracy-href=""> & ctrl key
			if (e.ctrlKey && (link = Tracy.closest(e.target, '[data-tracy-href]'))) {
				location.href = link.getAttribute('data-tracy-href');
				return false;
			}

			if (e.shiftKey || e.altKey || e.ctrlKey || e.metaKey) {
				return;
			}

			// enables <a class="tracy-toggle" href="#"> or <span data-ref="#"> toggling
			if (link = Tracy.closest(e.target, '.tracy-toggle')) {
				var collapsed = Tracy.hasClass(link, 'tracy-collapsed'),
					ref = link.getAttribute('data-ref') || link.getAttribute('href', 2),
					dest = ref && ref !== '#' ? document.getElementById(ref.substring(1)) : link.nextElementSibling;

				Tracy[collapsed ? 'removeClass' : 'addClass'](link, 'tracy-collapsed');
				Tracy[collapsed ? 'removeClass' : 'addClass'](dest, 'tracy-collapsed');
				e.preventDefault();
			}
		});
	};

})();
