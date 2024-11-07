
function init() {
	// fixes DNA-106639 "Close tabs created for opening external apps" since Opera 99
	if (navigator.userAgent.includes('OPR/')) {
		document.addEventListener(
			'click',
			(e) => {
				let el = e.target;
				while (el && el !== document) {
					if (el.tagName === 'A') {
						const href = el.getAttribute('href');
						if (href && href.startsWith('editor:')) {
							el.setAttribute('target', '_blank');
						}
						break;
					}
					el = el.parentNode;
				}
			},
			true,
		);
	}
}


let Tracy = window.Tracy = window.Tracy || {};
if (!Tracy.helpers) {
	init();
	Tracy.helpers = true;
}
