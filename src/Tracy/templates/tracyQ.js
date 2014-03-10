/**
 * tracyQ
 *
 * This file is part of the Tracy.
 * Copyright (c) 2004, 2014 David Grudl (http://davidgrudl.com)
 */

var Tracy = Tracy || {};

(function(){

	var Query = Tracy.Query = function(selector) {
		if (typeof selector === 'string') {
			selector = document.querySelectorAll(selector);

		} else if (!selector || selector.nodeType || selector.length === undefined || selector === window) {
			selector = [selector];
		}

		for (var i = 0, len = selector.length; i < len; i++) {
			if (selector[i]) { this[this.length++] = selector[i]; }
		}
	};

	Query.factory = function(selector) {
		return new Query(selector);
	};

	Query.prototype.length = 0;

	Query.prototype.find = function(selector) {
		return new Query(this[0] && selector ? this[0].querySelectorAll(selector) : []);
	};

	Query.prototype.dom = function() {
		return this[0];
	};

	Query.prototype.each = function(callback) {
		for (var i = 0; i < this.length; i++) {
			if (callback.apply(this[i]) === false) { break; }
		}
		return this;
	};

	// event attach
	Query.prototype.bind = function(event, handler) {
		if (event === 'mouseenter' || event === 'mouseleave') { // simulate mouseenter & mouseleave using mouseover & mouseout
			var old = handler;
			event = event === 'mouseenter' ? 'mouseover' : 'mouseout';
			handler = function(e) {
				for (var target = e.relatedTarget; target; target = target.parentNode) {
					if (target === this) { return; } // target must not be inside this
				}
				old.call(this, e);
			};
		}
		return this.each(function() {
			this.addEventListener(event, handler);
		});
	};

	// adds class to element
	Query.prototype.addClass = function(className) {
		return this.each(function() {
			this.className = (this.className.replace(/^|\s+|$/g, ' ').replace(' '+className+' ', ' ') + ' ' + className).trim();
		});
	};

	// removes class from element
	Query.prototype.removeClass = function(className) {
		return this.each(function() {
			this.className = this.className.replace(/^|\s+|$/g, ' ').replace(' '+className+' ', ' ').trim();
		});
	};

	// tests whether element has given class
	Query.prototype.hasClass = function(className) {
		return this[0] && this[0].className && this[0].className.replace(/^|\s+|$/g, ' ').indexOf(' '+className+' ') > -1;
	};

	Query.prototype.show = function() {
		Query.displays = Query.displays || {};
		return this.each(function() {
			var tag = this.tagName;
			if (!Query.displays[tag]) {
				Query.displays[tag] = (new Query(document.body.appendChild(document.createElement(tag)))).css('display');
			}
			this.style.display = Query.displays[tag];
		});
	};

	Query.prototype.hide = function() {
		return this.each(function() {
			this.style.display = 'none';
		});
	};

	Query.prototype.css = function(property) {
		if (this[0]) {
			return document.defaultView.getComputedStyle(this[0], null).getPropertyValue(property);
		}
	};

	Query.prototype.data = function() {
		if (this[0]) {
			return this[0].tracy ? this[0].tracy : this[0].tracy = {};
		}
	};

	Query.prototype._trav = function(el, selector, fce) {
		var matches = el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector;
		while (el && selector && !(el.nodeType === 1 && matches.call(el, selector))) {
			el = el[fce];
		}
		return new Query(el || []);
	};

	Query.prototype.closest = function(selector) {
		return this._trav(this[0], selector, 'parentNode');
	};

	Query.prototype.prev = function(selector) {
		return this._trav(this[0] && this[0].previousElementSibling, selector, 'previousElementSibling');
	};

	Query.prototype.next = function(selector) {
		return this._trav(this[0] && this[0].nextElementSibling, selector, 'nextElementSibling');
	};

	// returns total offset for element
	Query.prototype.offset = function(coords) {
		if (coords) {
			return this.each(function() {
				var elem = this, ofs = {left: -coords.left || 0, top: -coords.top || 0};
				while (elem = elem.offsetParent) {
					ofs.left += elem.offsetLeft; ofs.top += elem.offsetTop;
				}
				this.style.left = -ofs.left + 'px';
				this.style.top = -ofs.top + 'px';
			});
		} else if (this[0]) {
			var elem = this[0], res = {left: elem.offsetLeft, top: elem.offsetTop};
			while (elem = elem.offsetParent) {
				res.left += elem.offsetLeft; res.top += elem.offsetTop;
			}
			return res;
		}
	};

	// returns current position or move to new position
	Query.prototype.position = function(coords) {
		if (coords) {
			return this.each(function() {
				if (this.tracy && this.tracy.onmove) {
					this.tracy.onmove.call(this, coords);
				}
				for (var item in coords) {
					this.style[item] = coords[item] + 'px';
				}
			});
		} else if (this[0]) {
			return {
				left: this[0].offsetLeft, top: this[0].offsetTop,
				right: this[0].style.right ? parseInt(this[0].style.right, 10) : 0, bottom: this[0].style.bottom ? parseInt(this[0].style.bottom, 10) : 0,
				width: this[0].offsetWidth, height: this[0].offsetHeight
			};
		}
	};

	// makes element draggable
	Query.prototype.draggable = function(options) {
		var elem = this[0], dE = document.documentElement, started;
		options = options || {};

		(options.handle ? new Query(options.handle) : this).bind('mousedown', function(e) {
			var $el = new Query(options.handle ? elem : this);
			e.preventDefault();
			e.stopPropagation();

			if (Query.dragging) { // missed mouseup out of window?
				return dE.onmouseup(e);
			}

			var pos = $el.position(),
				deltaX = options.rightEdge ? pos.right + e.clientX : pos.left - e.clientX,
				deltaY = options.bottomEdge ? pos.bottom + e.clientY : pos.top - e.clientY;

			Query.dragging = true;
			started = false;

			dE.onmousemove = function(e) {
				e = e || window.event;
				if (!started) {
					if (options.draggedClass) {
						$el.addClass(options.draggedClass);
					}
					if (options.start) {
						options.start(e, $el);
					}
					started = true;
				}

				var pos = {};
				pos[options.rightEdge ? 'right' : 'left'] = options.rightEdge ? deltaX - e.clientX : e.clientX + deltaX;
				pos[options.bottomEdge ? 'bottom' : 'top'] = options.bottomEdge ? deltaY - e.clientY : e.clientY + deltaY;
				$el.position(pos);
				return false;
			};

			dE.onmouseup = function(e) {
				if (started) {
					if (options.draggedClass) {
						$el.removeClass(options.draggedClass);
					}
					if (options.stop) {
						options.stop(e || window.event, $el);
					}
				}
				Query.dragging = dE.onmousemove = dE.onmouseup = null;
				return false;
			};

		}).bind('click', function(e) {
			if (started) {
				e.stopImmediatePropagation();
			}
		});

		return this;
	};

})();
