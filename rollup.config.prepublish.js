// https://stackoverflow.com/a/57718411

import babel from '@rollup/plugin-babel';
import json from '@rollup/plugin-json';

/**
 * @type {import('rollup').RollupOptions}
 */
export default {
	input  : 'es6/index.js',
	output : [
		{
			file     : 'lib/browser.js',
			format   : 'es',
			sourcemap: true,
		},
		{
			file     : 'lib/main.cjs',
			format   : 'cjs',
			sourcemap: true,
		},
	],
	plugins: [
		babel({
			babelHelpers: 'bundled',
			configFile  : './babel.config.json',
		}),
		json(),
	],
};
