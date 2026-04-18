const { merge } = require('webpack-merge')
const baseConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

module.exports = merge(baseConfig, {
	entry: {
		main: path.join(__dirname, 'src', 'main.js'),
	},
})
