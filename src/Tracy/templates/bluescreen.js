/**
 * This file is part of the Tracy (http://tracy.nette.org)
 */

(function(){
	document.body.appendChild(document.getElementById('tracyBluescreen'));

	for (var i = 0, styles = []; i < document.styleSheets.length; i++) {
		var style = document.styleSheets[i];
		if (!Tracy.hasClass(style.ownerNode, 'tracy-debug')) {
			style.oldDisabled = style.disabled;
			style.disabled = true;
			styles.push(style);
		}
	}

	document.getElementById('tracyBluescreenIcon').addEventListener('click', function(e) {
		var collapsed = Tracy.hasClass(this, 'tracy-collapsed');
		for (i = 0; i < styles.length; i++) {
			styles[i].disabled = collapsed ? true : styles[i].oldDisabled;
		}
	});

	document.addEventListener('keyup', function(e) {
		if (e.keyCode === 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) { // ESC
			document.getElementById('tracyBluescreenIcon').click();
		}
	});
})();
