import { defineStore } from 'pinia'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { categoriesApi } from '../services/api.js'

export const useCategoriesStore = defineStore('categories', {
	state: () => ({
		categories: [],
		listId: null,
	}),

	actions: {
		async fetchAll(listId) {
			if (this.listId === listId) return
			this.listId = listId
			this.categories = []
			try {
				this.categories = await categoriesApi.getAll(listId)
			} catch {
				showError(t('lists', 'Could not load categories'))
			}
		},

		async refresh(listId) {
			try {
				this.categories = await categoriesApi.getAll(listId)
				this.listId = listId
			} catch {
				showError(t('lists', 'Could not refresh categories'))
			}
		},

		reset() {
			this.categories = []
			this.listId = null
		},

		async create(listId, name, icon = '') {
			try {
				const cat = await categoriesApi.create(listId, name, icon)
				this.categories.push(cat)
				showSuccess(t('lists', 'Category created'))
				return cat
			} catch {
				showError(t('lists', 'Could not create category'))
				return null
			}
		},

		async update(listId, id, fields) {
			try {
				const updated = await categoriesApi.update(listId, id, fields)
				const idx = this.categories.findIndex((c) => c.id === id)
				if (idx !== -1) this.categories[idx] = updated
			} catch {
				showError(t('lists', 'Could not update category'))
			}
		},

		async reorder(listId, orderedIds) {
			// Optimistic: re-arrange locally so the UI mirrors the drop immediately
			const byId = Object.fromEntries(this.categories.map((c) => [c.id, c]))
			const next = orderedIds.map((id) => byId[id]).filter(Boolean)
			const previous = this.categories
			this.categories = next
			try {
				await categoriesApi.reorder(listId, orderedIds)
			} catch {
				this.categories = previous
				showError(t('lists', 'Could not save order'))
			}
		},

		async destroy(listId, id) {
			try {
				await categoriesApi.destroy(listId, id)
				this.categories = this.categories.filter((c) => c.id !== id)
				showSuccess(t('lists', 'Category deleted'))
			} catch {
				showError(t('lists', 'Could not delete category'))
			}
		},
	},
})
