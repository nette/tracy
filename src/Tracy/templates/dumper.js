/**
 * This file is part of the Tracy (http://tracy.nette.org)
 */

(function() {
	var COLLAPSE_COUNT = 7,
		liveItems = {};

	Tracy.Dumper = Tracy.Dumper || {};

	Tracy.Dumper.init = function(liveData) {
		for (var id in liveData || {}) {
			liveItems[id] = liveData[id];
		}
		Array.prototype.forEach.call(document.querySelectorAll('.tracy-dump[data-tracy-dump]'), function(dest) {
			dest.appendChild(build(JSON.parse(dest.getAttribute('data-tracy-dump')), Tracy.hasClass(dest, 'tracy-collapsed')));
			Tracy.removeClass(dest, 'tracy-collapsed');
			dest.removeAttribute('data-tracy-dump');
		});

		if (this.inited) {
			return;
		}
		this.inited = true;

		document.body.addEventListener('click', function(e) {
			var link;

			// enables <span data-tracy-href=""> & ctrl key
			if (e.ctrlKey && (link = Tracy.closest(e.target, '[data-tracy-href]'))) {
				location.href = link.getAttribute('data-tracy-href');
				return false;
			}

			if (e.shiftKey || e.altKey || e.ctrlKey || e.metaKey) {
				return;
			}

			// enables <a class="tracy-toggle" href="#"> or <span data-ref="#"> toggling
			if (link = Tracy.closest(e.target, '.tracy-toggle')) {
				var collapsed = Tracy.hasClass(link, 'tracy-collapsed'),
					ref = link.getAttribute('data-ref') || link.getAttribute('href', 2),
					dest = ref && ref !== '#' ? document.getElementById(ref.substring(1)) : link.nextElementSibling;

				Tracy[collapsed ? 'removeClass' : 'addClass'](link, 'tracy-collapsed');
				Tracy[collapsed ? 'removeClass' : 'addClass'](dest, 'tracy-collapsed');
				e.preventDefault();
			}
		});
	};


	var build = function(data, collapsed) {
		var type = data === null ? 'null' : typeof data;

		if (type === 'null' || type === 'string' || type === 'number' || type === 'boolean') {
			data = type === 'string' ? '"' + data + '"' : (data + '').toUpperCase();
			return createEl(null, [], [
				createEl(
					'span',
					{'class': 'tracy-dump-' + type.replace('ean', '')},
					[data + '\n']
				)
			]);

		} else if (Array.isArray(data)) {
			return buildStruct([
					createEl('span', {'class': 'tracy-dump-array'}, ['array']),
					' (' + (data[0] && data.length || '') + ')'
				],
				' [ ... ]',
				data[0] === null ? null : data,
				collapsed || data.length >= COLLAPSE_COUNT
			);

		} else if (type === 'object' && data.type) {
			return createEl(null, [], [
				createEl('span', [], [data.type + '\n'])
			]);

		} else if (type === 'object') {
			var id = data.object || data.resource,
				object = liveItems[id];

			return buildStruct([
				createEl('span', {
					'class': data.object ? 'tracy-dump-object' : 'tracy-dump-resource',
					title: object.editor ? 'Declared in file ' + object.editor.file + ' on line ' + object.editor.line : null,
					'data-tracy-href': object.editor ? object.editor.url : null
				}, [object.name]),
				' ',
				createEl('span', {'class': 'tracy-dump-hash'}, ['#' + id])
			], ' { ... }', object.items, collapsed !== false || (object.items && object.items.length >= COLLAPSE_COUNT));
		}
	};


	var buildStruct = function(span, ellipsis, items, collapsed) {
		var res, toggle, div, handler;

		if (!items || !items.length) {
			span.push(!items || items.length ? ellipsis + '\n' : '\n');
			return createEl(null, [], span);
		}

		res = createEl(null, [], [
			toggle = createEl('span', {'class': collapsed ? 'tracy-toggle tracy-collapsed' : 'tracy-toggle'}, span),
			'\n',
			div = createEl('div', {'class': collapsed ? 'tracy-collapsed' : ''})
		]);

		if (collapsed) {
			toggle.addEventListener('click', handler = function() {
				toggle.removeEventListener('click', handler);
				createItems(div, items);
			});
		} else {
			createItems(div, items);
		}
		return res;
	};


	var createEl = function(el, attrs, content) {
		if (!(el instanceof Node)) {
			el = el ? document.createElement(el) : document.createDocumentFragment();
		}
		for (var id in attrs || []) {
			if (attrs[id] !== null) {
				el.setAttribute(id, attrs[id]);
			}
		}
		for (id in content || []) {
			var child = content[id];
			if (child !== null) {
				el.appendChild(child instanceof Node ? child : document.createTextNode(child));
			}
		}
		return el;
	};


	var createItems = function(el, items) {
		for (var i in items) {
			var vis = items[i][2];
			createEl(el, [], [
				createEl('span', {'class': 'tracy-dump-key'}, [items[i][0]]),
				vis ? ' ' : null,
				vis ? createEl('span', {'class': 'tracy-dump-visibility'}, [vis === 1 ? 'protected' : 'private']) : null,
				' => ',
				build(items[i][1])
			]);
		}
	};

})();
