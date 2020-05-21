/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

'use strict';

(function() {
	const
		COLLAPSE_COUNT = 7,
		COLLAPSE_COUNT_TOP = 14,
		TYPE_ARRAY = 'a',
		TYPE_OBJECT = 'o',
		TYPE_RESOURCE = 'r',
		PROP_VIRTUAL = 4;

	class Dumper
	{
		static init(context) {
			(context || document).querySelectorAll('[itemprop=tracy-snapshot], [data-tracy-snapshot]').forEach((el) => {
				let preList, snapshot = JSON.parse(el.getAttribute('data-tracy-snapshot'));

				if (el.tagName === 'META') { // <meta itemprop=tracy-snapshot>
					snapshot = JSON.parse(el.getAttribute('content'));
					preList = el.parentElement.querySelectorAll('[data-tracy-dump]');
				} else if (el.matches('[data-tracy-dump]')) { // <pre data-tracy-snapshot data-tracy-dump>
					preList = [el];
					el.removeAttribute('data-tracy-snapshot');
				} else { // <span data-tracy-dump>
					el.querySelectorAll('[data-tracy-dump]').forEach((el) => {
						el.parentNode.removeChild(el.nextSibling); // remove \n after toggler
						el.parentNode.replaceChild( // replace toggler
							build(JSON.parse(el.getAttribute('data-tracy-dump')), snapshot, el.classList.contains('tracy-collapsed')),
							el
						);
					});
					return;
				}

				preList.forEach((el) => { // <pre>
					let built = build(JSON.parse(el.getAttribute('data-tracy-dump')), snapshot, el.classList.contains('tracy-collapsed'));
					el.insertBefore(built, el.lastChild);
					el.classList.remove('tracy-collapsed');
					el.removeAttribute('data-tracy-dump');
				});
			});

			if (Dumper.inited) {
				return;
			}
			Dumper.inited = true;

			// enables <span data-tracy-href=""> & ctrl key
			document.documentElement.addEventListener('click', (e) => {
				let el;
				if (e.ctrlKey && (el = e.target.closest('[data-tracy-href]'))) {
					location.href = el.getAttribute('data-tracy-href');
					return false;
				}
			});

			document.documentElement.addEventListener('tracy-toggle', (e) => {
				if (e.target.matches('.tracy-dump *')) {
					e.detail.relatedTarget.classList.toggle('tracy-dump-flash', !e.detail.collapsed);
				}
			});

			document.documentElement.addEventListener('animationend', (e) => {
				if (e.animationName === 'tracy-dump-flash') {
					e.target.classList.toggle('tracy-dump-flash', false);
				}
			});

			document.addEventListener('mouseover', (e) => {
				let dump;
				if (e.target.matches('.tracy-dump-hash') && (dump = e.target.closest('.tracy-dump'))) {
					dump.querySelectorAll('.tracy-dump-hash').forEach((el) => {
						if (el.textContent === e.target.textContent) {
							el.classList.add('tracy-dump-highlight');
						}
					});
				}
			});

			document.addEventListener('mouseout', (e) => {
				if (e.target.matches('.tracy-dump-hash')) {
					document.querySelectorAll('.tracy-dump-hash.tracy-dump-highlight').forEach((el) => {
						el.classList.remove('tracy-dump-highlight');
					});
				}
			});

			Tracy.Toggle.init();
		}
	}


	function build(data, repository, collapsed, parentIds) {
		let id, type = data === null ? 'null' : typeof data,
			collapseCount = collapsed === null ? COLLAPSE_COUNT : COLLAPSE_COUNT_TOP;

		if (type === 'null' || type === 'number' || type === 'boolean') {
			return createEl(null, null, [
				createEl(
					'span',
					{'class': 'tracy-dump-' + type.replace('ean', '')},
					[data + '\n']
				)
			]);

		} else if (type === 'string') {
			data = {string: data};

		} else if (Array.isArray(data)) {
			data = {array: null, items: data};

		} else if (data.ref) {
			id = data.ref;
			data = repository[id];
			if (!data) {
				throw new UnknownEntityException;
			}
		}


		if (data.string !== undefined) {
			return createEl(null, null, [
				createEl('span', {'class': 'tracy-dump-string'}, ['"' + data.string + '"']),
				' (' + (data.length || data.string.length) + ')\n',
			]);

		} else if (data.number) {
			return createEl(null, null, [
				createEl('span', {'class': 'tracy-dump-number'}, [data.number + '\n'])
			]);

		} else if (data.text !== undefined) {
			return createEl(null, null, [
				createEl('span', null, [data.text + '\n'])
			]);

		} else { // object || resource || array
			parentIds = parentIds ? parentIds.slice() : [];
			let recursive = id && parentIds.indexOf(id) > -1;
			parentIds.push(id);

			return buildStruct(
				data.array !== undefined
					? [
						createEl('span', {'class': 'tracy-dump-array'}, ['array']),
						' (' + (data.length || data.items.length) + ')'
					]
					: [
						createEl('span', {
							'class': data.object ? 'tracy-dump-object' : 'tracy-dump-resource',
							title: data.editor ? 'Declared in file ' + data.editor.file + ' on line ' + data.editor.line : null,
							'data-tracy-href': data.editor ? data.editor.url : null
						}, [data.object || data.resource]),
						' ',
						createEl('span', {'class': 'tracy-dump-hash'}, [data.resource ? '@' + id.substr(1) : '#' + id])
					],
				recursive ? ' { RECURSION }' : ' { ... }',
				recursive ? null : data.items,
				collapsed === true || (data.items && data.items.length >= collapseCount),
				data.object ? TYPE_OBJECT : data.resource ? TYPE_RESOURCE : TYPE_ARRAY,
				repository,
				parentIds
			);
		}
	}


	function buildStruct(span, ellipsis, items, collapsed, type, repository, parentIds) {
		let res, toggle, div, handler;

		if (!items || !items.length) {
			span.push(!items || items.length ? ellipsis + '\n' : '\n');
			return createEl(null, null, span);
		}

		res = createEl(null, null, [
			toggle = createEl('span', {'class': collapsed ? 'tracy-toggle tracy-collapsed' : 'tracy-toggle'}, span),
			'\n',
			div = createEl('div', {'class': collapsed ? 'tracy-collapsed' : null})
		]);

		if (collapsed) {
			toggle.addEventListener('tracy-toggle', handler = function() {
				toggle.removeEventListener('tracy-toggle', handler);
				createItems(div, items, type, repository, parentIds);
			});
		} else {
			createItems(div, items, type, repository, parentIds);
		}
		return res;
	}


	function createEl(el, attrs, content) {
		if (!(el instanceof Node)) {
			el = el ? document.createElement(el) : document.createDocumentFragment();
		}
		for (let id in attrs || {}) {
			if (attrs[id] !== null) {
				el.setAttribute(id, attrs[id]);
			}
		}
		content = content || [];
		for (let id = 0; id < content.length; id++) {
			let child = content[id];
			if (child !== null) {
				el.appendChild(child instanceof Node ? child : document.createTextNode(child));
			}
		}
		return el;
	}


	function createItems(el, items, type, repository, parentIds) {
		const classes = [
			'tracy-dump-public',
			'tracy-dump-protected',
			'tracy-dump-private',
			'tracy-dump-dynamic',
			'tracy-dump-virtual',
		];

		let key, val, vis, ref, i;

		for (i = 0; i < items.length; i++) {
			if (type === TYPE_ARRAY) {
				[key, val, ref] = items[i];
			} else {
				[key, val, vis = PROP_VIRTUAL, ref] = items[i];
			}
			createEl(el, null, [
				type === TYPE_ARRAY
					? createEl('span', {'class': 'tracy-dump-key'}, [key])
					: createEl(
						'span',
						{'class': classes[type === TYPE_OBJECT ? vis : PROP_VIRTUAL]},
						[key]
					),
				' => ',
				...(ref ? [createEl('span', {'class': 'tracy-dump-hash'}, ['&' + ref]), ' '] : []),
				build(val, repository, null, parentIds)
			]);
		}
	}


	function UnknownEntityException() {}


	let Tracy = window.Tracy = window.Tracy || {};
	Tracy.Dumper = Dumper;
})();
