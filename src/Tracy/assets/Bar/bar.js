/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

(function(){
	Tracy = window.Tracy || {};

	var Panel = Tracy.DebugPanel = function(id) {
		this.id = 'tracy-debug-panel-' + id;
		this.elem = document.getElementById(this.id);
		this.elem.Tracy = this.elem.Tracy || {};
	};

	Panel.PEEK = 'tracy-mode-peek';
	Panel.FLOAT = 'tracy-mode-float';
	Panel.WINDOW = 'tracy-mode-window';
	Panel.FOCUSED = 'tracy-focused';
	Panel.zIndex = 20000;

	Panel.prototype.init = function() {
		var _this = this, elem = this.elem;

		elem.Tracy.onMove = function(coords) {
			_this.moveConstrains(this, coords);
		};

		draggable(elem, {
			rightEdge: true,
			bottomEdge: true,
			handle: elem.querySelector('h1'),
			stop: function() {
				_this.toFloat();
			}
		});

		elem.addEventListener('mouseover', function(e) {
			if (isTargetChanged(e.relatedTarget, this)) {
				_this.focus();
			}
		});

		elem.addEventListener('mouseout', function(e) {
			if (isTargetChanged(e.relatedTarget, this)) {
				_this.blur();
			}
		});

		elem.addEventListener('click', function() {
			_this.oldPosition = getPosition(elem);
		});

		document.documentElement.addEventListener('click', function() {
			if (_this.oldPosition) {
				var pos = getPosition(elem);
				setPosition(elem, {
					right: pos.right - pos.width + _this.oldPosition.width,
					bottom: pos.bottom - pos.height + _this.oldPosition.height
				});
			}
			_this.oldPosition = null;
		});

		[].forEach.call(elem.querySelectorAll('.tracy-icons a'), function(a) {
			a.addEventListener('click', function(e) {
				if (this.rel === 'close') {
					_this.toPeek();
				} else {
					_this.toWindow();
				}
				e.preventDefault();
			});
		});

		this.restorePosition();
	};

	Panel.prototype.is = function(mode) {
		return this.elem.classList.contains(mode);
	};

	Panel.prototype.focus = function(callback) {
		var elem = this.elem;
		if (this.is(Panel.WINDOW)) {
			elem.Tracy.window.focus();
		} else {
			clearTimeout(elem.Tracy.displayTimeout);
			elem.Tracy.displayTimeout = setTimeout(function() {
				elem.classList.add(Panel.FOCUSED);
				elem.style.display = 'block';
				elem.style.zIndex = Panel.zIndex++;
				if (callback) {
					callback();
				}
			}, 50);
		}
	};

	Panel.prototype.blur = function() {
		var elem = this.elem;
		elem.classList.remove(Panel.FOCUSED);
		if (this.is(Panel.PEEK)) {
			clearTimeout(elem.Tracy.displayTimeout);
			elem.Tracy.displayTimeout = setTimeout(function() {
				elem.style.display = 'none';
			}, 50);
		}
	};

	Panel.prototype.toFloat = function() {
		this.elem.classList.remove(Panel.WINDOW);
		this.elem.classList.remove(Panel.PEEK);
		this.elem.classList.add(Panel.FLOAT);
		this.elem.style.display = 'block';
		this.reposition();
	};

	Panel.prototype.toPeek = function() {
		this.elem.classList.remove(Panel.WINDOW);
		this.elem.classList.remove(Panel.FLOAT);
		this.elem.classList.add(Panel.PEEK);
		this.elem.style.display = 'none';
		localStorage.removeItem(this.id); // delete position
	};

	Panel.prototype.toWindow = function() {
		var offset = getOffset(this.elem);
		offset.left += typeof window.screenLeft === 'number' ? window.screenLeft : (window.screenX + 10);
		offset.top += typeof window.screenTop === 'number' ? window.screenTop : (window.screenY + 50);

		var win = window.open('', this.id.replace(/-/g, '_'), 'left=' + offset.left + ',top=' + offset.top
			+ ',width=' + this.elem.offsetWidth + ',height=' + (this.elem.offsetHeight + 15) + ',resizable=yes,scrollbars=yes');
		if (!win) {
			return;
		}

		var doc = win.document;
		doc.write('<!DOCTYPE html><meta charset="utf-8"><style>'
			+ document.getElementById('tracy-debug-style').innerHTML
			+ '<\/style><script>'
			+ document.getElementById('tracy-debug-script').innerHTML
			+ '<\/script><body id="tracy-debug">'
		);
		doc.body.innerHTML = '<div class="tracy-panel tracy-mode-window" id="' + this.id + '">' + this.elem.innerHTML + '<\/div>';
		win.Tracy.Dumper.init();
		if (this.elem.querySelector('h1')) {
			doc.title = this.elem.querySelector('h1').innerHTML;
		}

		for (var i = 0, scripts = doc.body.getElementsByTagName('script'); i < scripts.length; i++) {
			(win.execScript || function(data) {
				win['eval'].call(win, data);
			})(scripts[i].innerHTML);
		}

		var _this = this;
		win.addEventListener('beforeunload', function() {
			_this.toPeek();
			win.close(); // forces closing, can be invoked by F5
		});

		doc.addEventListener('keyup', function(e) {
			if (e.keyCode === 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) {
				win.close();
			}
		});

		localStorage.setItem(this.id, JSON.stringify({window: true}));
		this.elem.style.display = 'none';
		this.elem.classList.remove(Panel.FLOAT);
		this.elem.classList.remove(Panel.PEEK);
		this.elem.classList.add(Panel.WINDOW);
		this.elem.Tracy.window = win;
	};

	Panel.prototype.reposition = function() {
		if (!this.is(Panel.WINDOW)) {
			var pos = getPosition(this.elem);
			if (pos.width) { // is visible?
				setPosition(this.elem, {right: pos.right, bottom: pos.bottom});
				localStorage.setItem(this.id, JSON.stringify({right: pos.right, bottom: pos.bottom}));
			}
		}
	};

	Panel.prototype.moveConstrains = function(el, coords) { // forces constrained inside window
		var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth,
			height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		coords.right = Math.min(Math.max(coords.right, -0.2 * el.offsetWidth), width - 0.8 * el.offsetWidth);
		coords.bottom = Math.min(Math.max(coords.bottom, -0.2 * el.offsetHeight), height - el.offsetHeight);
	};

	Panel.prototype.restorePosition = function() {
		var pos = JSON.parse(localStorage.getItem(this.id));
		if (!pos) {
			this.elem.classList.add(Panel.PEEK);
		} else if (pos.window) {
			this.toWindow();
		} else if (this.elem.querySelector('*')) {
			setPosition(this.elem, pos);
			this.toFloat();
		}
	};


	var Bar = Tracy.DebugBar = function() {
	};

	Bar.prototype.id = 'tracy-debug-bar';

	Bar.prototype.init = function() {
		var elem = document.getElementById(this.id), _this = this;

		elem.Tracy = {};
		elem.Tracy.onMove = function(coords) {
			_this.moveConstrains(this, coords);
		};

		draggable(elem, {
			rightEdge: true,
			bottomEdge: true,
			draggedClass: 'tracy-dragged',
			stop: function() {
				_this.savePosition();
			}
		});

		[].forEach.call(elem.querySelectorAll('a'), function(a) {
			a.addEventListener('click', function(e) {
				if (this.rel === 'close') {
					_this.close();

				} else if (this.rel) {
					var panel = Debug.getPanel(this.rel);
					if (e.shiftKey) {
						panel.toFloat();
						panel.toWindow();

					} else if (panel.is(Panel.FLOAT)) {
						panel.toPeek();

					} else {
						panel.toFloat();
						setPosition(panel.elem, {
							right: getPosition(panel.elem).right + Math.round(Math.random() * 100) + 20,
							bottom: getPosition(panel.elem).bottom + Math.round(Math.random() * 100) + 20
						});
						panel.reposition();
					}
				}
				e.preventDefault();
			});

			a.addEventListener('mouseover', function(e) {
				if (isTargetChanged(e.relatedTarget, this) && this.rel && this.rel !== 'close' && !elem.classList.contains('tracy-dragged')) {
					var panel = Debug.getPanel(this.rel), link = this;
					panel.focus(function() {
						if (panel.is(Panel.PEEK)) {
							var pos = getPosition(panel.elem);
							setPosition(panel.elem, {
								right: pos.right - getOffset(link).left + pos.width - getPosition(link).width - 4 + getOffset(panel.elem).left,
								bottom: pos.bottom - getOffset(elem).top + pos.height + 4 + getOffset(panel.elem).top
							});
						}
					});
				}
			});

			a.addEventListener('mouseout', function(e) {
				if (isTargetChanged(e.relatedTarget, this) && this.rel && this.rel !== 'close' && !elem.classList.contains('tracy-dragged')) {
					Debug.getPanel(this.rel).blur();
				}
			});
		});

		this.restorePosition();
	};

	Bar.prototype.close = function() {
		document.getElementById('tracy-debug').style.display = 'none';
	};

	Bar.prototype.moveConstrains = function(el, coords) { // forces constrained inside window
		var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth,
			height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		coords.right = Math.min(Math.max(coords.right, 0), width - el.offsetWidth);
		coords.bottom = Math.min(Math.max(coords.bottom, 0), height - el.offsetHeight);
	};

	Bar.prototype.savePosition = function() {
		var pos = getPosition(document.getElementById(this.id));
		localStorage.setItem(this.id, JSON.stringify({right: pos.right, bottom: pos.bottom}));
	};

	Bar.prototype.restorePosition = function() {
		var pos = JSON.parse(localStorage.getItem(this.id));
		if (pos) {
			setPosition(document.getElementById(this.id), pos);
		}
	};


	var Debug = Tracy.Debug = {};

	Debug.init = function() {
		Debug.initResize();
		(new Bar).init();
		[].forEach.call(document.querySelectorAll('.tracy-panel'), function(panel) {
			Debug.getPanel(panel.id).init();
		});
	};

	Debug.getPanel = function(id) {
		return new Panel(id.replace('tracy-debug-panel-', ''));
	};

	Debug.initResize = function() {
		window.addEventListener('resize', function() {
			var bar = document.getElementById(Bar.prototype.id);
			setPosition(bar, {right: getPosition(bar).right, bottom: getPosition(bar).bottom});
			[].forEach.call(document.querySelectorAll('.tracy-panel'), function(panel) {
				Debug.getPanel(panel.id).reposition();
			});
		});
	};


	// emulate mouseenter & mouseleave
	function isTargetChanged(target, dest) {
		while (target) {
			if (target === dest) {
				return;
			}
			target = target.parentNode;
		}
		return true;
	}


	var dragging;

	function draggable(elem, options) {
		var dE = document.documentElement, started, pos, deltaX, deltaY;
		options = options || {};

		var onmousemove = function(e) {
			if (e.buttons === 0) {
				return onmouseup(e);
			}
			if (!started) {
				if (options.draggedClass) {
					elem.classList.add(options.draggedClass);
				}
				if (options.start) {
					options.start(e, elem);
				}
				started = true;
			}

			var pos = {};
			pos[options.rightEdge ? 'right' : 'left'] = options.rightEdge ? deltaX - e.clientX : e.clientX + deltaX;
			pos[options.bottomEdge ? 'bottom' : 'top'] = options.bottomEdge ? deltaY - e.clientY : e.clientY + deltaY;
			setPosition(elem, pos);
			return false;
		};

		var onmouseup = function(e) {
			if (started) {
				if (options.draggedClass) {
					elem.classList.remove(options.draggedClass);
				}
				if (options.stop) {
					options.stop(e, elem);
				}
			}
			dragging = null;
			dE.removeEventListener('mousemove', onmousemove);
			dE.removeEventListener('mouseup', onmouseup);
			return false;
		};

		(options.handle || elem).addEventListener('mousedown', function(e) {
			e.preventDefault();
			e.stopPropagation();

			if (dragging) { // missed mouseup out of window?
				return onmouseup(e);
			}

			pos = getPosition(elem);
			deltaX = options.rightEdge ? pos.right + e.clientX : pos.left - e.clientX;
			deltaY = options.bottomEdge ? pos.bottom + e.clientY : pos.top - e.clientY;
			dragging = true;
			started = false;
			dE.addEventListener('mousemove', onmousemove);
			dE.addEventListener('mouseup', onmouseup);
		});

		(options.handle || elem).addEventListener('click', function(e) {
			if (started) {
				e.stopImmediatePropagation();
			}
		});
	}

	// returns total offset for element
	function getOffset(elem) {
		var res = {left: elem.offsetLeft, top: elem.offsetTop};
		while (elem = elem.offsetParent) { // eslint-disable-line
			res.left += elem.offsetLeft; res.top += elem.offsetTop;
		}
		return res;
	}

	// move to new position
	function setPosition(elem, coords) {
		if (elem.Tracy && elem.Tracy.onMove) {
			elem.Tracy.onMove.call(elem, coords);
		}
		for (var item in coords) {
			elem.style[item] = coords[item] + 'px';
		}
	}

	// returns current position
	function getPosition(elem) {
		return {
			left: elem.offsetLeft,
			top: elem.offsetTop,
			right: elem.style.right ? parseInt(elem.style.right, 10) : 0,
			bottom: elem.style.bottom ? parseInt(elem.style.bottom, 10) : 0,
			width: elem.offsetWidth,
			height: elem.offsetHeight
		};
	}

})();
