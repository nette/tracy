
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

// registry of CSS sources filled by the loader (DeferredContent, Dumper::renderAssets)
Tracy.css = Tracy.css || {};

// memoized constructed stylesheets, one instance per document
Tracy.styleSheet = (name) => {
	let sheets = Tracy.styleSheets = Tracy.styleSheets || {};
	if (!sheets[name]) {
		sheets[name] = (new CSSStyleSheet);
		sheets[name].replaceSync(Tracy.css[name] || '');
	}
	return sheets[name];
};

// adopts named stylesheets into a Document or ShadowRoot, without duplicates
Tracy.adoptStyleSheets = (root, names) => {
	let sheets = names.map(Tracy.styleSheet).filter((sheet) => !root.adoptedStyleSheets.includes(sheet));
	root.adoptedStyleSheets = [...root.adoptedStyleSheets, ...sheets];
};

if (!Tracy.helpers) {
	init();
	Tracy.helpers = true;
}
