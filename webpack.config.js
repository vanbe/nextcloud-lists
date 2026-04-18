const { merge } = require('webpack-merge')
const baseConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

module.exports = merge(baseConfig, {
	entry: {
		main: path.join(__dirname, 'src', 'main.js'),
	},
	resolve: {
		alias: {
			// Route all 'vue' imports through our shim (@vue/compat)
			// so @nextcloud/vue v8 Vue-2-compiled components work in webpack
			vue$: path.join(__dirname, 'src', 'vue-shim.js'),
		},
	},
})
