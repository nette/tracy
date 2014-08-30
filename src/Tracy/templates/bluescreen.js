/**
 * Bluescreen.
 *
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

(function(){
	var bs = document.getElementById('tracyBluescreen');
	document.body.appendChild(bs);
	document.onkeyup = function(e) {
		e = e || window.event;
		if (e.keyCode == 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) {
			document.getElementById('tracyBluescreenIcon').click();
		}
	};

	for (var i = 0, styles = []; i < document.styleSheets.length; i++) {
		var style = document.styleSheets[i];
		if ((style.owningElement || style.ownerNode).className !== 'tracy-debug') {
			style.oldDisabled = style.disabled;
			style.disabled = true;
			styles.push(style);
		}
	}

	bs.onclick = function(e) {
		e = e || window.event;

		if (e.ctrlKey) {
			for (var link = e.target; link && (!link.getAttribute || !link.getAttribute('data-tracy-href')); link = link.parentNode) {}
			if (link) {
				location.href = link.getAttribute('data-tracy-href');
				return false;
			}
		}

		if (e.shiftKey || e.altKey || e.ctrlKey || e.metaKey) {
			return;
		}

		for (var link = e.target || e.srcElement; link && (!link.tagName || !link.className.match(/(^|\s)tracy-toggle(\s|$)/)); link = link.parentNode) {}
		if (!link) {
			return true;
		}

		var collapsed = link.className.match(/(^|\s)tracy-collapsed(\s|$)/),
			ref = link.getAttribute('data-ref') || link.getAttribute('href', 2),
			dest;

		if (ref && ref !== '#') {
			dest = document.getElementById(ref.substring(1));
		} else {
			for (dest = link.nextSibling; dest && dest.nodeType !== 1; dest = dest.nextSibling) {}
		}

		link.className = (collapsed ? link.className.replace(/(^|\s)tracy-collapsed(\s|$)/, ' ') : link.className + ' tracy-collapsed').replace(/^\s+|\s+$/g,'');
		dest.className = (collapsed ? dest.className.replace(/(^|\s)tracy-collapsed(\s|$)/, ' ') : dest.className + ' tracy-collapsed').replace(/^\s+|\s+$/g,'');

		if (link.id === 'tracyBluescreenIcon') {
			for (i = 0; i < styles.length; i++) {
				styles[i].disabled = collapsed ? true : styles[i].oldDisabled;
			}
		}
		e.preventDefault ? e.preventDefault() : e.returnValue = false;
		e.stopPropagation ? e.stopPropagation() : e.cancelBubble = true;
	};
})();
