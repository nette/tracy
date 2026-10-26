
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

// passes Tracy's own markup and scripts to sinks that a page enforcing Trusted Types
// (require-trusted-types-for 'script') refuses plain strings at
Tracy.trusted = Tracy.trusted || (() => {
	let policy;
	try {
		policy = window.trustedTypes?.createPolicy('tracy', {
			createHTML: (s) => s,
			createScript: (s) => s,
			createScriptURL: (s) => s,
		});
	} catch {
		// the page's trusted-types directive doesn't allow the name
	}
	return {
		html: (s) => policy ? policy.createHTML(s) : s,
		script: (s) => policy ? policy.createScript(s) : s,
		scriptURL: (s) => policy ? policy.createScriptURL(s) : s,
	};
})();

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
