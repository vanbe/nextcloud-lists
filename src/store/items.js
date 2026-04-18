import { defineStore } from 'pinia'
import { itemsApi } from '../services/api.js'

export const useItemsStore = defineStore('items', {
	state: () => ({
		items: [],
		listId: null,
		loading: false,
		error: null,
	}),

	getters: {
		unchecked: (state) => state.items.filter((i) => !i.checked).sort((a, b) => a.position - b.position),
		checked: (state) => state.items.filter((i) => i.checked).sort((a, b) => (b.checkedAt ?? 0) - (a.checkedAt ?? 0)),
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
			} finally {
				this.loading = false
			}
		},

		reset() {
			this.items = []
			this.listId = null
			this.loading = false
			this.error = null
		},

		async create(listId, title) {
			const item = await itemsApi.create(listId, title)
			this.items.push(item)
		},

		async toggle(listId, id) {
			const item = await itemsApi.toggle(listId, id)
			const idx = this.items.findIndex((i) => i.id === id)
			if (idx !== -1) this.items[idx] = item
		},

		async destroy(listId, id) {
			await itemsApi.destroy(listId, id)
			this.items = this.items.filter((i) => i.id !== id)
		},
	},
})
