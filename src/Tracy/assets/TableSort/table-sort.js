/**
 * This file is part of the Tracy (https://tracy.nette.org)
 */

'use strict';

(function() {

	class TableSort
	{
		static init(context) {
			context.addEventListener('click', (e) => {
				if (!e.target.matches('tr:first-child *')) {
					return;
				}
				let tcell = e.target.closest('td,th');
				let tbody = tcell.closest('table').tBodies[0];
				let preserveFirst = !tcell.closest('thead') && !tcell.parentNode.querySelectorAll('td').length;
				let asc = !(tbody.tracyAsc === tcell.cellIndex);
				tbody.tracyAsc = asc ? tcell.cellIndex : null;
				let getText = (cell) => { return cell ? cell.innerText : ''; };

				Array.from(tbody.querySelectorAll('tr'))
					.slice(preserveFirst ? 1 : 0)
					.sort((a, b) => {
						return function(v1, v2) {
							return v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2);
						}(getText((asc ? a : b).children[tcell.cellIndex]), getText((asc ? b : a).children[tcell.cellIndex]));
					})
					.forEach((tr) => { tbody.appendChild(tr); });
			});
		}
	}


	Tracy = window.Tracy || {};
	Tracy.TableSort = Tracy.TableSort || TableSort;
})();
