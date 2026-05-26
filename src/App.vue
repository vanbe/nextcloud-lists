<template>
	<NcAppNavigation :aria-label="t('lists', 'Lists navigation')">
		<template #list>
			<li class="lists-nav__new">
				<button class="lists-nav__new-btn" @click="onNewList">
					+ {{ t('lists', 'New list') }}
				</button>
				<button
					v-if="store.lists.length > 1"
					class="lists-nav__reorder-btn"
					:title="t('lists', 'Reorder lists')"
					@click="reorderOpen = true">
					⇅
				</button>
			</li>
			<li
				v-for="list in store.lists"
				:key="list.id"
				class="lists-nav__item"
				:class="{ 'lists-nav__item--active': list.id === store.selectedId }"
				@click="store.select(list.id)">
				<span class="lists-nav__name">{{ list.name }}</span>
				<span v-if="list.activeItemCount > 0" class="lists-nav__count">{{ list.activeItemCount }}</span>
				<button
					class="lists-nav__actions"
					:title="t('lists', 'Actions')"
					@click.stop="toggleMenu(list.id)">
					⋮
				</button>
				<div v-if="openMenuId === list.id" class="lists-nav__menu">
					<button class="lists-nav__menu-item" @click.stop="openEdit(list)">
						{{ t('lists', 'Edit') }}
					</button>
					<button class="lists-nav__menu-item" @click.stop="openShare(list)">
						{{ t('lists', 'Share') }}
					</button>
					<button class="lists-nav__menu-item" @click.stop="openExport(list)">
						{{ t('lists', 'Export') }}
					</button>
					<button class="lists-nav__menu-item lists-nav__menu-item--danger" @click.stop="onDelete(list)">
						{{ t('lists', 'Delete') }}
					</button>
				</div>
			</li>
		</template>
	</NcAppNavigation>

	<main id="app-content" class="app-content no-snapper">
		<div v-if="store.loading" class="lists-loading">
			Loading…
		</div>
		<div v-else-if="store.error" class="lists-error">
			{{ store.error }}
		</div>
		<div v-else-if="!store.selected" class="lists-empty">
			<p>{{ t('lists', 'Create a list or select one in the sidebar.') }}</p>
		</div>
		<div v-else class="lists-view">
			<h2>{{ store.selected.name }}</h2>
			<p v-if="store.selected.description" class="lists-view__description">{{ store.selected.description }}</p>
			<ItemList :list="store.selected" />
		</div>
	</main>

	<ShareModal v-if="shareTarget" :list="shareTarget" @close="shareTarget = null" />
	<ListFormModal
		v-if="formTarget !== undefined"
		:list="formTarget"
		@close="formTarget = undefined"
		@submit="onFormSubmit" />
	<ReorderModal
		v-if="reorderOpen"
		:lists="store.lists"
		:current-user="currentUser"
		@save="onReorder"
		@close="reorderOpen = false" />
	<ExportModal v-if="exportTarget" :list="exportTarget" @close="exportTarget = null" />
</template>

<script>
import { NcAppNavigation } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { getCurrentUser } from '@nextcloud/auth'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { useListsStore } from './store/lists.js'
import ItemList from './components/ItemList.vue'
import ShareModal from './components/ShareModal.vue'
import ListFormModal from './components/ListFormModal.vue'
import ReorderModal from './components/ReorderModal.vue'
import ExportModal from './components/ExportModal.vue'

export default {
	name: 'App',

	components: { NcAppNavigation, ItemList, ShareModal, ListFormModal, ReorderModal, ExportModal },

	setup() {
		const store = useListsStore()
		store.fetchAll()
		return { store }
	},

	data() {
		return {
			openMenuId: null,
			shareTarget: null,
			exportTarget: null,
			formTarget: undefined, // undefined = hidden, null = create mode, object = edit mode
			reorderOpen: false,
			currentUser: getCurrentUser()?.uid ?? '',
			// Swipe-gesture state
			navOpen: false,
			swipe: { startX: 0, startY: 0, startTime: 0, tracking: false },
		}
	},

	mounted() {
		document.addEventListener('click', this.closeMenu)
		// Track NcAppNavigation open state
		this.onNavToggled = ({ open }) => { this.navOpen = !!open }
		subscribe('navigation-toggled', this.onNavToggled)
		// Touch swipe listeners (Android open/close menu)
		document.addEventListener('touchstart', this.onTouchStart, { passive: true })
		document.addEventListener('touchend', this.onTouchEnd, { passive: true })
	},

	beforeUnmount() {
		document.removeEventListener('click', this.closeMenu)
		unsubscribe('navigation-toggled', this.onNavToggled)
		document.removeEventListener('touchstart', this.onTouchStart)
		document.removeEventListener('touchend', this.onTouchEnd)
	},

	methods: {
		t,

		onTouchStart(e) {
			if (e.touches.length !== 1) {
				this.swipe.tracking = false
				return
			}
			// Skip when touching inside an input / textarea / scrollable picker
			const target = e.target
			if (target?.closest?.('input, textarea, [contenteditable="true"]')) {
				this.swipe.tracking = false
				return
			}
			const touch = e.touches[0]
			this.swipe = {
				startX: touch.clientX,
				startY: touch.clientY,
				startTime: Date.now(),
				tracking: true,
			}
		},

		onTouchEnd(e) {
			if (!this.swipe.tracking) return
			this.swipe.tracking = false
			const touch = e.changedTouches[0]
			if (!touch) return
			const dx = touch.clientX - this.swipe.startX
			const dy = touch.clientY - this.swipe.startY
			const dt = Date.now() - this.swipe.startTime

			// Filter: must be primarily horizontal, fast enough, long enough
			if (Math.abs(dx) < 60) return
			if (Math.abs(dx) <= Math.abs(dy) * 1.5) return
			if (dt > 600) return

			if (dx > 0) {
				// Swipe right — open menu (must start near the left edge to avoid conflicts)
				if (this.navOpen) return
				if (this.swipe.startX > 30) return
				emit('toggle-navigation', { open: true })
			} else {
				// Swipe left — close menu (any start position)
				if (!this.navOpen) return
				emit('toggle-navigation', { open: false })
			}
		},

		closeMenu() {
			this.openMenuId = null
		},

		toggleMenu(id) {
			this.openMenuId = this.openMenuId === id ? null : id
		},

		openShare(list) {
			this.openMenuId = null
			this.shareTarget = list
		},

		openExport(list) {
			this.openMenuId = null
			this.exportTarget = list
		},

		openEdit(list) {
			this.openMenuId = null
			this.formTarget = list
		},

		async onDelete(list) {
			this.openMenuId = null
			if (!window.confirm(t('lists', 'Delete "{name}"? This will permanently remove all its items.', { name: list.name }))) return
			await this.store.destroy(list.id)
		},

		onNewList() {
			this.formTarget = null // create mode
		},

		async onReorder(orderedIds) {
			this.reorderOpen = false
			await this.store.reorder(orderedIds)
		},

		async onFormSubmit({ name, description, hasQuantities }) {
			if (this.formTarget === null) {
				// create
				await this.store.create(name, description, hasQuantities)
			} else {
				// edit
				await this.store.update(this.formTarget.id, { name, description, hasQuantities })
			}
			this.formTarget = undefined
		},
	},
}
</script>

<style scoped>
.lists-nav__new {
	list-style: none;
	padding: 4px 12px;
	display: flex;
	align-items: center;
	gap: 4px;
}
.lists-nav__new-btn {
	flex: 1;
	text-align: left;
	background: none;
	border: none;
	cursor: pointer;
	padding: 8px;
	color: var(--color-primary);
	font-weight: bold;
}
.lists-nav__reorder-btn {
	background: none;
	border: none;
	cursor: pointer;
	color: var(--color-text-lighter);
	font-size: 1.1em;
	padding: 4px 8px;
	border-radius: var(--border-radius);
	flex-shrink: 0;
}
.lists-nav__reorder-btn:hover {
	background: var(--color-background-hover);
	color: var(--color-main-text);
}
.lists-nav__item {
	list-style: none;
	display: flex;
	align-items: center;
	padding: 4px 12px;
	cursor: pointer;
	position: relative;
}
.lists-nav__item:hover,
.lists-nav__item--active {
	background: var(--color-background-hover);
}
.lists-nav__name {
	flex: 1;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.lists-nav__count {
	font-size: 0.75em;
	background: var(--color-primary);
	color: var(--color-primary-text);
	border-radius: 10px;
	padding: 1px 7px;
	min-width: 20px;
	text-align: center;
	flex-shrink: 0;
}
.lists-nav__actions {
	background: none;
	border: none;
	cursor: pointer;
	opacity: 0;
	color: var(--color-text-lighter);
	font-size: 1.2em;
	line-height: 1;
	padding: 2px 6px;
	border-radius: var(--border-radius);
}
.lists-nav__item:hover .lists-nav__actions,
.lists-nav__item--active .lists-nav__actions {
	opacity: 1;
}
.lists-nav__actions:hover {
	background: var(--color-background-dark);
}
.lists-nav__menu {
	position: absolute;
	right: 8px;
	top: 100%;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
	z-index: 1000;
	min-width: 140px;
	padding: 4px 0;
}
.lists-nav__menu-item {
	display: block;
	width: 100%;
	text-align: left;
	background: none;
	border: none;
	cursor: pointer;
	padding: 8px 16px;
	color: var(--color-main-text);
	font-size: 0.95em;
}
.lists-nav__menu-item:hover {
	background: var(--color-background-hover);
}
.lists-nav__menu-item--danger {
	color: #c0392b;
	font-weight: 500;
}
.lists-nav__menu-item--danger:hover {
	background: #fdecea;
	color: #a93226;
}
.lists-view {
	padding: 12px 0 24px 24px;
}
.lists-view h2 {
	margin: 0 0 4px;
	padding: 44px 24px 0 0;
}
.lists-view__description {
	color: var(--color-text-lighter);
	margin: 0 0 16px;
	padding: 0 24px 0 0;
}
.lists-empty {
	padding: 92px 48px 48px;
	text-align: center;
	color: var(--color-text-lighter);
}
</style>
