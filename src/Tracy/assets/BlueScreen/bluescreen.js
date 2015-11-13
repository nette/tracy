/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

(function(){
	var blueScreen = document.getElementById('tracy-bs');
	document.body.appendChild(blueScreen);

	for (var i = 0, styles = []; i < document.styleSheets.length; i++) {
		var style = document.styleSheets[i];
		if (!style.ownerNode.classList.contains('tracy-debug')) {
			style.oldDisabled = style.disabled;
			style.disabled = true;
			styles.push(style);
		}
	}

	document.getElementById('tracy-bs-toggle').addEventListener('tracy-toggle', function(e) {
		var collapsed = this.classList.contains('tracy-collapsed');
		for (i = 0; i < styles.length; i++) {
			styles[i].disabled = collapsed ? styles[i].oldDisabled : true;
		}
	});

	document.addEventListener('keyup', function(e) {
		if (e.keyCode === 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) { // ESC
			Tracy.Toggle.toggle(document.getElementById('tracy-bs-toggle'));
		}
	});

	var id = location.href + document.getElementById('tracy-bs-error').textContent;
	Tracy.Toggle.persist(blueScreen, localStorage.getItem('tracy-toggles-bskey') === id);
	localStorage.setItem('tracy-toggles-bskey', id);
})();
