import nette from '@nette/eslint-plugin';
import { defineConfig } from 'eslint/config';

export default defineConfig([
	{
		ignores: [
			'vendor', 'tests', 'x',
		],
	},

	{
		files: [
			'*.js',
			'src/**/*.js',
		],

		extends: [nette.configs.recommended],
	},
]);
