// Use @vue/compat (Vue 3 + Vue 2 compat layer) so that @nextcloud/vue v8
// components compiled with Vue 2 render functions work in our webpack bundle.
export * from '@vue/compat'
export { default } from '@vue/compat'

// Vue 2 reactive helpers missing from @vue/compat — plain assignment is enough in Vue 3
export const set = (target, key, value) => {
	target[key] = value
	return value
}
export const del = (target, key) => {
	delete target[key]
}
