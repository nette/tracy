
function init() {
	// fixes DNA-106639 "Close tabs created for opening external apps" since Opera 99
	if (navigator.userAgent.includes('OPR/')) {
		document.addEventListener(
			'click',
			(e) => {
				Tracy.retarget(e).closest('a[href^="editor:"]')?.setAttribute('target', '_blank');
			},
			true,
		);
	}
}


let Tracy = window.Tracy = window.Tracy || {};

// returns the real event target even when the event crossed a shadow DOM boundary
Tracy.retarget = (e) => e.composedPath()[0] ?? e.target;

if (!Tracy.helpers) {
	init();
	Tracy.helpers = true;
}
