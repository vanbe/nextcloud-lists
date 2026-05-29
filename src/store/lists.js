import { defineStore } from 'pinia'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { listsApi } from '../services/api.js'

/** Parse `#/list/{id}` from the URL hash → numeric id or null. */
function listIdFromHash() {
	const m = (window.location.hash || '').match(/^#\/list\/(\d+)/)
	return m ? Number(m[1]) : null
}

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
				const exists = (id) => id != null && this.lists.some((l) => l.id === id)
				// Priority: URL hash (shareable link / F5) → current selection → first list
				const hashId = listIdFromHash()
				if (exists(hashId)) {
					this.selectedId = hashId
				} else if (!exists(this.selectedId)) {
					this.selectedId = this.lists.length ? this.lists[0].id : null
				}
				this.syncHash()
			} catch (e) {
				this.error = e.message
				showError(t('lists', 'Could not load lists'))
			} finally {
				this.loading = false
			}
		},

		async create(name, description = null, hasQuantities = false) {
			try {
				const list = await listsApi.create(name, description, hasQuantities)
				this.lists.push(list)
				this.selectedId = list.id
				this.syncHash()
				showSuccess(t('lists', 'List created'))
			} catch {
				showError(t('lists', 'Could not create list'))
			}
		},

		async update(id, fields) {
			try {
				// hasQuantities: send 1/0 (not boolean) so NC isset() sees it
				const payload = { ...fields }
				if ('hasQuantities' in payload) {
					payload.hasQuantities = payload.hasQuantities ? 1 : 0
				}
				const list = await listsApi.update(id, payload)
				const idx = this.lists.findIndex((l) => l.id === id)
				if (idx !== -1) this.lists[idx] = { ...this.lists[idx], ...list }
				showSuccess(t('lists', 'List updated'))
			} catch {
				showError(t('lists', 'Could not update list'))
			}
		},

		async destroy(id) {
			try {
				await listsApi.destroy(id)
				this.lists = this.lists.filter((l) => l.id !== id)
				if (this.selectedId === id) {
					this.selectedId = this.lists[0]?.id ?? null
					this.syncHash()
				}
				showSuccess(t('lists', 'List deleted'))
			} catch {
				showError(t('lists', 'Could not delete list'))
			}
		},

		select(id) {
			this.selectedId = id
			this.syncHash()
		},

		/** Reflect the current selection in the URL hash (shareable + survives F5). */
		syncHash() {
			const target = this.selectedId ? `#/list/${this.selectedId}` : ''
			if (window.location.hash !== target) {
				window.location.hash = target
			}
		},

		/** Called on `hashchange` (back/forward, pasted link) → align selection. */
		selectFromHash() {
			const hashId = listIdFromHash()
			if (hashId != null && hashId !== this.selectedId && this.lists.some((l) => l.id === hashId)) {
				this.selectedId = hashId
			}
		},

		async reorder(orderedIds) {
			// Optimistic: reorder local list immediately
			const byId = Object.fromEntries(this.lists.map((l) => [l.id, l]))
			const reordered = orderedIds.map((id) => byId[id]).filter(Boolean)
			const rest = this.lists.filter((l) => !orderedIds.includes(l.id))
			this.lists = [...reordered, ...rest]
			try {
				await listsApi.reorder(orderedIds)
			} catch {
				showError(t('lists', 'Could not save order'))
				// Revert by refetching
				await this.fetchAll()
			}
		},

		// Called after item toggle/create/delete to keep sidebar counts in sync
		async refreshCounts() {
			try {
				const fresh = await listsApi.getAll()
				fresh.forEach((updated) => {
					const idx = this.lists.findIndex((l) => l.id === updated.id)
					if (idx !== -1) this.lists[idx] = { ...this.lists[idx], activeItemCount: updated.activeItemCount }
				})
			} catch {
				// non-critical, ignore
			}
		},
	},
})
