/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

(function(){
	document.body.appendChild(document.getElementById('tracy-bs'));

	for (var i = 0, styles = []; i < document.styleSheets.length; i++) {
		var style = document.styleSheets[i];
		if (!style.ownerNode.classList.contains('tracy-debug')) {
			style.oldDisabled = style.disabled;
			style.disabled = true;
			styles.push(style);
		}
	}

	document.getElementById('tracy-bs-toggle').addEventListener('click', function(e) {
		var collapsed = this.classList.contains('tracy-collapsed');
		for (i = 0; i < styles.length; i++) {
			styles[i].disabled = collapsed ? true : styles[i].oldDisabled;
		}
	});

	document.addEventListener('keyup', function(e) {
		if (e.keyCode === 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) { // ESC
			document.getElementById('tracy-bs-toggle').click();
		}
	});
})();
