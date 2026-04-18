const { merge } = require('webpack-merge')
const baseConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

module.exports = merge(baseConfig, {
	entry: {
		main: path.join(__dirname, 'src', 'main.js'),
	},
	resolve: {
		alias: {
			// @nextcloud/vue v8 has legacy Vue.extend() calls — shim provides it
			vue$: path.join(__dirname, 'src', 'vue-shim.js'),
		},
	},
})
