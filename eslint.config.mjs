import globals from 'globals';
import pluginJs from '@eslint/js';

export default [
	pluginJs.configs.recommended,
	{
		languageOptions: {
			ecmaVersion: 'latest',
			globals: globals.browser,
		},
		rules: {
			indent: ['error', 'tab'],
			quotes: ['error', 'single'],
			semi: ['error', 'always'],
			'prefer-arrow-callback': ['error'],
			'arrow-parens': ['error'],
			'arrow-spacing': ['error'],
			'no-var': ['error'],
		},
	},
];
