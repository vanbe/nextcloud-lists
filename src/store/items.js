import { defineStore } from 'pinia'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { itemsApi } from '../services/api.js'
import { useListsStore } from './lists.js'

const CHECKED_SORT_KEY = 'lists.checkedSortMode'
const VALID_SORT_MODES = ['alpha', 'recent', 'category']

function loadCheckedSortMode() {
	const stored = localStorage.getItem(CHECKED_SORT_KEY)
	return VALID_SORT_MODES.includes(stored) ? stored : 'recent'
}

export const useItemsStore = defineStore('items', {
	state: () => ({
		items: [],
		listId: null,
		loading: false,
		error: null,
		checkedSortMode: loadCheckedSortMode(), // 'alpha' | 'recent'
	}),

	getters: {
		unchecked: (state) => state.items.filter((i) => !i.checked).sort((a, b) => a.position - b.position),
		checked: (state) => {
			const checked = state.items.filter((i) => i.checked)
			if (state.checkedSortMode === 'recent') {
				return checked.sort((a, b) => (b.checkedAt ?? 0) - (a.checkedAt ?? 0))
			}
			// 'alpha' and 'category' both start with title sort; category is re-sorted in the component (needs catStore)
			return checked.sort((a, b) => (a.title ?? '').localeCompare(b.title ?? '', undefined, { sensitivity: 'base', numeric: true }))
		},
	},

	actions: {
		async fetchAll(listId) {
			if (this.listId === listId) return
			this.listId = listId
			this.items = []
			this.loading = true
			this.error = null
			try {
				this.items = await itemsApi.getAll(listId)
			} catch (e) {
				this.error = e.message
				showError(t('lists', 'Could not load items'))
			} finally {
				this.loading = false
			}
		},

		async refresh(listId) {
			this.error = null
			try {
				this.items = await itemsApi.getAll(listId)
				this.listId = listId
			} catch (e) {
				this.error = e.message
				showError(t('lists', 'Could not refresh items'))
			}
		},

		reset() {
			this.items = []
			this.listId = null
			this.loading = false
			this.error = null
		},

		toggleCheckedSort() {
			const cycle = { alpha: 'recent', recent: 'category', category: 'alpha' }
			this.checkedSortMode = cycle[this.checkedSortMode] ?? 'alpha'
			localStorage.setItem(CHECKED_SORT_KEY, this.checkedSortMode)
		},

		async create(listId, title, categoryId = null, quantity = null) {
			try {
				const item = await itemsApi.create(listId, title, categoryId, quantity)
				this.items.push(item)
				useListsStore().refreshCounts()
			} catch {
				showError(t('lists', 'Could not add item'))
			}
		},

		async updateQuantity(listId, id, quantity) {
			try {
				// Send 0 as "null" sentinel (NC drops JSON null via isset)
				const item = await itemsApi.update(listId, id, { quantity: quantity ?? 0 })
				const idx = this.items.findIndex((i) => i.id === id)
				if (idx !== -1) this.items[idx] = item
			} catch {
				showError(t('lists', 'Could not update item'))
			}
		},

		async rename(listId, id, title) {
			try {
				const item = await itemsApi.update(listId, id, { title })
				const idx = this.items.findIndex((i) => i.id === id)
				if (idx !== -1) this.items[idx] = item
			} catch {
				showError(t('lists', 'Could not rename item'))
			}
		},

		async setCategory(listId, id, categoryId) {
			try {
				// NC's IRequest uses isset() so JSON null is invisible — send 0 as "unassign" sentinel
				const item = await itemsApi.update(listId, id, { categoryId: categoryId ?? 0 })
				const idx = this.items.findIndex((i) => i.id === id)
				if (idx !== -1) this.items[idx] = item
			} catch {
				showError(t('lists', 'Could not update item'))
			}
		},

		async toggle(listId, id) {
			try {
				const item = await itemsApi.toggle(listId, id)
				const idx = this.items.findIndex((i) => i.id === id)
				if (idx !== -1) this.items[idx] = item
				useListsStore().refreshCounts()
			} catch {
				showError(t('lists', 'Could not update item'))
			}
		},

		async destroy(listId, id) {
			try {
				await itemsApi.destroy(listId, id)
				this.items = this.items.filter((i) => i.id !== id)
				useListsStore().refreshCounts()
			} catch {
				showError(t('lists', 'Could not delete item'))
			}
		},
	},
})
