/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

'use strict';

(function() {
	const
		COLLAPSE_COUNT = 7,
		COLLAPSE_COUNT_TOP = 14;

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
		let type = data === null ? 'null' : typeof data,
			collapseCount = collapsed === null ? COLLAPSE_COUNT : COLLAPSE_COUNT_TOP;

		if (Array.isArray(data)) {
			data = {array: data};
		}

		if (type === 'null' || type === 'string' || type === 'number' || type === 'boolean') {
			data = type === 'string' ? '"' + data + '"' : (data + '');
			return createEl(null, null, [
				createEl(
					'span',
					{'class': 'tracy-dump-' + type.replace('ean', '')},
					[data + '\n']
				)
			]);

		} else if (data.array) {
			if (data.stop) {
				return createEl(null, null, [
					createEl('span', {'class': 'tracy-dump-array'}, ['array']),
					' (' + data.length + ')',
					data.stop === 'r' ? ' [ RECURSION ]\n' : ' [ ... ]\n',
				]);
			}

			let len = data.length || data.array.length;
			return buildStruct(
				[
					createEl('span', {'class': 'tracy-dump-array'}, ['array']),
					' (' + len + ')'
				],
				' [ ... ]',
				data.array,
				collapsed === true || len >= collapseCount,

				true,
				repository,
				parentIds
			);

		} else if (data.number) {
			return createEl(null, null, [
				createEl('span', {'class': 'tracy-dump-number'}, [data.number + '\n'])
			]);

		} else if (data.key) {
			return createEl(null, null, [
				createEl('span', null, [data.type + '\n'])
			]);

		} else if (data.string) {
			return createEl(null, null, [
				createEl('span', {'class': 'tracy-dump-string'}, ['"' + data.string + '"']),
				' (' + data.length + ')\n'
			]);

		} else {
			let id = data.object || data.resource,
				object = repository[id];

			if (!object) {
				throw new UnknownEntityException;
			}
			parentIds = parentIds ? parentIds.slice() : [];
			let recursive = parentIds.indexOf(id) > -1;
			parentIds.push(id);

			return buildStruct(
				[
					createEl('span', {
						'class': data.object ? 'tracy-dump-object' : 'tracy-dump-resource',
						title: object.editor ? 'Declared in file ' + object.editor.file + ' on line ' + object.editor.line + (object.editor.url ? '\nCtrl-Click to open in editor' : '') : null,
						'data-tracy-href': object.editor ? object.editor.url : null
					}, [object.name]),
					' ',
					createEl('span', {'class': 'tracy-dump-hash'}, [data.resource ? '@' + id.substr(1) : '#' + id])
				],
				recursive ? ' { RECURSION }' : ' { ... }',
				recursive ? null : object.items,
				collapsed === true || (object.items && object.items.length >= collapseCount),
				false,
				repository,
				parentIds
			);
		}
	}


	function buildStruct(span, ellipsis, items, collapsed, array, repository, parentIds) {
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
				createItems(div, items, array, repository, parentIds);
			});
		} else {
			createItems(div, items, array, repository, parentIds);
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


	function createItems(el, items, array, repository, parentIds) {
		const classes = [
			'tracy-dump-public',
			'tracy-dump-protected',
			'tracy-dump-private',
			'tracy-dump-dynamic',
			'tracy-dump-virtual',
		];

		let key, val, vis, ref, i;

		for (i = 0; i < items.length; i++) {
			if (array) {
				[key, val, ref] = items[i];
			} else {
				[key, val, vis, ref] = items[i];
			}
			createEl(el, null, [
				createEl('span', {'class': array ? 'tracy-dump-key' : classes[vis]}, [key]),
				array ? ' => ' : ': ',
				ref ? createEl('span', {'class': 'tracy-dump-hash'}, ['&' + ref]) : null,
				ref ? ' ' : null,
				build(val, repository, null, parentIds)
			]);
		}
	}


	function UnknownEntityException() {}


	let Tracy = window.Tracy = window.Tracy || {};
	Tracy.Dumper = Dumper;
})();
