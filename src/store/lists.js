import { defineStore } from 'pinia'
import { listsApi } from '../services/api.js'

export const useListsStore = defineStore('lists', {
	state: () => ({
		lists: [],
		selectedId: null,
		loading: false,
		error: null,
	}),

	getters: {
		selected: (state) => state.lists.find((l) => l.id === state.selectedId) ?? null,
	},

	actions: {
		async fetchAll() {
			this.loading = true
			this.error = null
			try {
				this.lists = await listsApi.getAll()
				if (!this.selectedId && this.lists.length) {
					this.selectedId = this.lists[0].id
				}
			} catch (e) {
				this.error = e.message
			} finally {
				this.loading = false
			}
		},

		async create(name) {
			const list = await listsApi.create(name)
			this.lists.push(list)
			this.selectedId = list.id
		},

		async destroy(id) {
			await listsApi.destroy(id)
			this.lists = this.lists.filter((l) => l.id !== id)
			if (this.selectedId === id) {
				this.selectedId = this.lists[0]?.id ?? null
			}
		},

		select(id) {
			this.selectedId = id
		},
	},
})
