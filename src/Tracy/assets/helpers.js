
function init() {
	// fixes DNA-106639 "Close tabs created for opening external apps" since Opera 99
	if (navigator.userAgent.includes('OPR/')) {
		document.addEventListener(
			'click',
			(e) => {
				e.target.closest('a[href^="editor:"]')?.setAttribute('target', '_blank');
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
