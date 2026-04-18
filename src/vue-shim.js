// Shim: @nextcloud/vue v8 has residual Vue 2 API calls (Vue.extend, set, del).
// Re-export Vue 3 + compat stubs so the library doesn't crash.
export * from 'vue/dist/vue.esm-bundler.js'
import { defineComponent } from 'vue/dist/vue.esm-bundler.js'

// Vue 2 reactive helpers — in Vue 3, plain assignment suffices
export const set = (target, key, value) => {
	target[key] = value
	return value
}
export const del = (target, key) => {
	delete target[key]
}

// Vue 2 Vue.extend() compat
export default { extend: defineComponent, set, del }
