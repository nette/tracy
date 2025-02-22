import globals from 'globals';
import pluginJs from '@eslint/js';
import stylistic from '@stylistic/eslint-plugin';

export default [
	{
		ignores: ['*/', '!src/'],
	},

	pluginJs.configs.recommended,

	stylistic.configs.customize({
		indent: 'tab',
		braceStyle: '1tbs',
		arrowParens: true,
		semi: true,
		jsx: false,
	}),

	{
		languageOptions: {
			ecmaVersion: 'latest',
			globals: globals.browser,
		},
		plugins: {
			'@stylistic': stylistic,
		},
		rules: {
			'@stylistic/no-multiple-empty-lines': ['error', { max: 2, maxEOF: 0 }],
			'@stylistic/new-parens': ['error', 'never'],
			'@stylistic/padded-blocks': 'off',
			'func-style': ['error', 'declaration', { allowArrowFunctions: true }],
			'prefer-arrow-callback': 'error',
			'arrow-body-style': 'error',
			'eqeqeq': ['error', 'always', { null: 'ignore' }],
			'no-var': 'error',
			'prefer-const': 'off',
		},
	},
];
