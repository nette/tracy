/**
 * This file is part of the Tracy (http://tracy.nette.org)
 */

(function() {
	var COLLAPSE_COUNT = 7,
		COLLAPSE_COUNT_TOP = 14;

	Tracy = window.Tracy || {};

	Tracy.Dumper = Tracy.Dumper || {};

	Tracy.Dumper.init = function(repository) {
		if (repository) {
			[].forEach.call(document.querySelectorAll('.tracy-dump[data-tracy-dump]'), function(el) {
				try {
					el.appendChild(build(JSON.parse(el.getAttribute('data-tracy-dump')), repository, el.classList.contains('tracy-collapsed')));
					el.classList.remove('tracy-collapsed');
					el.removeAttribute('data-tracy-dump');
				} catch (e) {
					if (!(e instanceof UnknownEntityException)) {
						throw e;
					}
				}
			});
		}

		if (this.inited) {
			return;
		}
		this.inited = true;

		document.body.addEventListener('click', function(e) {
			var link;

			// enables <span data-tracy-href=""> & ctrl key
			if (e.ctrlKey && (link = closest(e.target, '[data-tracy-href]'))) {
				location.href = link.getAttribute('data-tracy-href');
				return false;
			}

			if (e.shiftKey || e.altKey || e.ctrlKey || e.metaKey) {
				return;
			}

			// enables <a class="tracy-toggle" href="#"> or <span data-tracy-ref="#"> toggling
			if (link = closest(e.target, '.tracy-toggle')) {
				var collapsed = link.classList.contains('tracy-collapsed'),
					ref = link.getAttribute('data-tracy-ref') || link.getAttribute('href', 2),
					dest = link;

				if (!ref || ref === '#') {
					ref = '+';
				} else if (ref.substr(0, 1) === '#') {
					dest = document;
				}
				ref = ref.match(/(\^\s*([^+\s]*)\s*)?(\+\s*(\S*)\s*)?(.*)/);
				dest = ref[1] ? closest(dest.parentNode, ref[2]) : dest;
				dest = ref[3] ? closest(dest.nextElementSibling, ref[4], 'nextElementSibling') : dest;
				dest = ref[5] ? dest.querySelector(ref[5]) : dest;

				link.classList.toggle('tracy-collapsed', !collapsed);
				dest.classList.toggle('tracy-collapsed', !collapsed);
				e.preventDefault();
			}
		});
	};


	var build = function(data, repository, collapsed, parentIds) {
		var type = data === null ? 'null' : typeof data,
			collapseCount = typeof collapsed === 'undefined' ? COLLAPSE_COUNT : COLLAPSE_COUNT_TOP;

		if (type === 'null' || type === 'string' || type === 'number' || type === 'boolean') {
			data = type === 'string' ? '"' + data + '"' : (data + '').toUpperCase();
			return createEl(null, null, [
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
				collapsed === true || data.length >= collapseCount,
				repository,
				parentIds
			);

		} else if (type === 'object' && data.type) {
			return createEl(null, null, [
				createEl('span', null, [data.type + '\n'])
			]);

		} else if (type === 'object') {
			var id = data.object || data.resource,
				object = repository[id];

			if (!object) {
				throw new UnknownEntityException;
			}
			parentIds = parentIds || [];
			recursive = parentIds.indexOf(id) > -1;
			parentIds.push(id);

			return buildStruct([
					createEl('span', {
						'class': data.object ? 'tracy-dump-object' : 'tracy-dump-resource',
						title: object.editor ? 'Declared in file ' + object.editor.file + ' on line ' + object.editor.line : null,
						'data-tracy-href': object.editor ? object.editor.url : null
					}, [object.name]),
					' ',
					createEl('span', {'class': 'tracy-dump-hash'}, ['#' + id])
				],
				' { ... }',
				object.items,
				collapsed === true || recursive || (object.items && object.items.length >= collapseCount),
				repository,
				parentIds
			);
		}
	};


	var buildStruct = function(span, ellipsis, items, collapsed, repository, parentIds) {
		var res, toggle, div, handler;

		if (!items || !items.length) {
			span.push(!items || items.length ? ellipsis + '\n' : '\n');
			return createEl(null, null, span);
		}

		res = createEl(null, null, [
			toggle = createEl('span', {'class': collapsed ? 'tracy-toggle tracy-collapsed' : 'tracy-toggle'}, span),
			'\n',
			div = createEl('div', {'class': collapsed ? 'tracy-collapsed' : ''})
		]);

		if (collapsed) {
			toggle.addEventListener('click', handler = function() {
				toggle.removeEventListener('click', handler);
				createItems(div, items, repository, parentIds);
			});
		} else {
			createItems(div, items, repository, parentIds);
		}
		return res;
	};


	var createEl = function(el, attrs, content) {
		if (!(el instanceof Node)) {
			el = el ? document.createElement(el) : document.createDocumentFragment();
		}
		for (var id in attrs || {}) {
			if (attrs[id] !== null) {
				el.setAttribute(id, attrs[id]);
			}
		}
		content = content || [];
		for (id = 0; id < content.length; id++) {
			var child = content[id];
			if (child !== null) {
				el.appendChild(child instanceof Node ? child : document.createTextNode(child));
			}
		}
		return el;
	};


	var createItems = function(el, items, repository, parentIds) {
		for (var i = 0; i < items.length; i++) {
			var vis = items[i][2];
			createEl(el, null, [
				createEl('span', {'class': 'tracy-dump-key'}, [items[i][0]]),
				vis ? ' ' : null,
				vis ? createEl('span', {'class': 'tracy-dump-visibility'}, [vis === 1 ? 'protected' : 'private']) : null,
				' => ',
				build(items[i][1], repository, null, parentIds)
			]);
		}
	};

	var UnknownEntityException = function() {};


	// finds closing maching element
	var closest = function(el, selector, func) {
		var matches = el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector;
		while (el && selector && !(el.nodeType === 1 && matches.call(el, selector))) {
			el = el[func || 'parentNode'];
		}
		return el;
	};

})();
