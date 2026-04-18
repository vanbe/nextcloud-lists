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

		reset() {
			this.categories = []
			this.listId = null
		},

		async create(listId, name) {
			try {
				const cat = await categoriesApi.create(listId, name)
				this.categories.push(cat)
				showSuccess(t('lists', 'Category created'))
				return cat
			} catch {
				showError(t('lists', 'Could not create category'))
				return null
			}
		},

		async rename(listId, id, name) {
			try {
				const updated = await categoriesApi.update(listId, id, name)
				const idx = this.categories.findIndex((c) => c.id === id)
				if (idx !== -1) this.categories[idx] = updated
			} catch {
				showError(t('lists', 'Could not rename category'))
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
