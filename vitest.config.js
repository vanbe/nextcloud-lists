import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
	plugins: [
		vue({
			template: {
				compilerOptions: {
					compatConfig: { MODE: 2 },
				},
			},
		}),
	],
	test: {
		environment: 'jsdom',
		globals: true,
		setupFiles: ['./tests/js/setup.js'],
	},
	resolve: {
		alias: {
			'@nextcloud/l10n': resolve('./tests/js/mocks/nextcloud-l10n.js'),
			'@nextcloud/axios': resolve('./tests/js/mocks/nextcloud-axios.js'),
		},
	},
})
