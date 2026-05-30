<template>
	<NcAppNavigation :aria-label="t('lists', 'Lists navigation')">
		<template #list>
			<li class="lists-nav__new">
				<NcButton
					class="lists-nav__new-btn"
					variant="primary"
					wide
					@click="onNewList">
					+ {{ t('lists', 'New list') }}
				</NcButton>
				<NcButton
					v-if="store.lists.length > 1"
					variant="tertiary"
					:aria-label="t('lists', 'Reorder lists')"
					:title="t('lists', 'Reorder lists')"
					@click="reorderOpen = true">
					<template #icon>
						<SwapVertical :size="20" />
					</template>
				</NcButton>
			</li>
			<li
				v-for="list in store.lists"
				:key="list.id"
				class="lists-nav__item"
				:class="{ 'lists-nav__item--active': list.id === store.selectedId }"
				@click="store.select(list.id)">
				<span class="lists-nav__name">{{ list.name }}</span>
				<span v-if="list.activeItemCount > 0" class="lists-nav__count">{{ list.activeItemCount }}</span>
				<!-- Manual menu rather than NcActions: the latter's render filters slot
				     children with `componentOptions.Ctor.extendOptions.name` (Vue 2 VNode
				     API), which is undefined on @vue/compat MODE:2 VNodes → render returns
				     nothing, no trigger button. Manual <button>+<div> is bulletproof. -->
				<button
					class="lists-nav__actions"
					:title="t('lists', 'Actions')"
					:aria-label="t('lists', 'Actions')"
					@click.stop="toggleMenu(list.id)">
					⋮
				</button>
				<div v-if="openMenuId === list.id" class="lists-nav__menu" @click.stop>
					<button class="lists-nav__menu-item" @click="openEdit(list)">
						<Pencil :size="16" /> {{ t('lists', 'Edit') }}
					</button>
					<button class="lists-nav__menu-item" @click="openShare(list)">
						<ShareVariant :size="16" /> {{ t('lists', 'Share') }}
					</button>
					<button class="lists-nav__menu-item" @click="openExport(list)">
						<Download :size="16" /> {{ t('lists', 'Export') }}
					</button>
					<button class="lists-nav__menu-item lists-nav__menu-item--danger" @click="onDelete(list)">
						<Delete :size="16" /> {{ t('lists', 'Delete') }}
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
			<p v-if="countsReady && (activeCount || checkedCount)" class="lists-view__counts">
				<span class="lists-view__count-active">{{ activeCount }} {{ t('lists', 'active') }}</span>
				<span v-if="checkedCount" class="lists-view__count-checked">· {{ checkedCount }} {{ t('lists', 'checked') }}</span>
			</p>
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
		@save="onReorder"
		@close="reorderOpen = false" />
	<ExportModal v-if="exportTarget" :list="exportTarget" @close="exportTarget = null" />
</template>

<script>
import { defineAsyncComponent } from 'vue'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcButton from '@nextcloud/vue/components/NcButton'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import ShareVariant from 'vue-material-design-icons/ShareVariant.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import SwapVertical from 'vue-material-design-icons/SwapVertical.vue'
import { translate as t } from '@nextcloud/l10n'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { useListsStore } from './store/lists.js'
import { useItemsStore } from './store/items.js'
import ItemList from './components/ItemList.vue'

// Modals are only shown on user action → load them on demand (keeps the
// initial bundle small; markdown-it, the emoji picker, etc. stay out of it).
const ShareModal = defineAsyncComponent(() => import('./components/ShareModal.vue'))
const ListFormModal = defineAsyncComponent(() => import('./components/ListFormModal.vue'))
const ReorderModal = defineAsyncComponent(() => import('./components/ReorderModal.vue'))
const ExportModal = defineAsyncComponent(() => import('./components/ExportModal.vue'))

export default {
	name: 'App',

	components: {
		NcAppNavigation,
		NcButton,
		Pencil,
		ShareVariant,
		Download,
		Delete,
		SwapVertical,
		ItemList,
		ShareModal,
		ListFormModal,
		ReorderModal,
		ExportModal,
	},

	setup() {
		const store = useListsStore()
		const itemsStore = useItemsStore()
		store.fetchAll()
		return { store, itemsStore }
	},

	computed: {
		// Only show counts once the items store holds the currently-selected list
		countsReady() {
			return this.itemsStore.listId === this.store.selectedId && !this.itemsStore.loading
		},
		activeCount() {
			return this.itemsStore.unchecked.length
		},
		checkedCount() {
			return this.itemsStore.checked.length
		},
	},

	data() {
		return {
			shareTarget: null,
			exportTarget: null,
			formTarget: undefined, // undefined = hidden, null = create mode, object = edit mode
			reorderOpen: false,
			openMenuId: null, // id of the list whose ⋮ menu is open (null = all closed)
			navOpen: false,
			swipe: { startX: 0, startY: 0, startTime: 0, tracking: false },
		}
	},

	mounted() {
		this.onNavToggled = ({ open }) => { this.navOpen = !!open }
		subscribe('navigation-toggled', this.onNavToggled)
		document.addEventListener('touchstart', this.onTouchStart, { passive: true })
		document.addEventListener('touchend', this.onTouchEnd, { passive: true })
		document.addEventListener('click', this.closeMenu)
		this.onHashChange = () => this.store.selectFromHash()
		window.addEventListener('hashchange', this.onHashChange)
	},

	beforeUnmount() {
		unsubscribe('navigation-toggled', this.onNavToggled)
		document.removeEventListener('touchstart', this.onTouchStart)
		document.removeEventListener('touchend', this.onTouchEnd)
		document.removeEventListener('click', this.closeMenu)
		window.removeEventListener('hashchange', this.onHashChange)
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

		toggleMenu(id) {
			this.openMenuId = this.openMenuId === id ? null : id
		},

		closeMenu() {
			this.openMenuId = null
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
			// Auto-save on drop: persist the new order but keep the modal open
			// so the user can keep reordering. Modal closes on Done / X / Esc.
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

<!-- Non-scoped: NcAppNavigation ships no background, and NC core CSS targets the old
     #app-navigation id (not the component's #app-navigation-vue) → menu is transparent. -->
<style>
#app-navigation-vue,
.app-navigation {
	background: var(--color-main-background);
}
</style>

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
	flex-shrink: 0;
	background: transparent;
	border: none;
	cursor: pointer;
	color: var(--color-text-lighter);
	padding: 4px 8px;
	border-radius: var(--border-radius);
	font-size: 1.2em;
	line-height: 1;
	opacity: 0;
	transition: opacity 0.12s, background 0.12s;
}
.lists-nav__item:hover .lists-nav__actions,
.lists-nav__item--active .lists-nav__actions,
.lists-nav__actions:focus-visible {
	opacity: 1;
}
.lists-nav__actions:hover {
	background: var(--color-background-dark);
	color: var(--color-main-text);
}
.lists-nav__menu {
	position: absolute;
	right: 12px;
	top: 100%;
	z-index: 1000;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
	min-width: 160px;
	padding: 4px;
	display: flex;
	flex-direction: column;
	gap: 2px;
}
.lists-nav__menu-item {
	background: transparent;
	border: none;
	cursor: pointer;
	color: var(--color-main-text);
	text-align: left;
	padding: 8px 10px;
	border-radius: var(--border-radius);
	font-size: 0.95em;
	display: flex;
	align-items: center;
	gap: 8px;
}
.lists-nav__menu-item:hover {
	background: var(--color-background-hover);
}
.lists-nav__menu-item--danger:hover {
	background: var(--color-error);
	color: var(--color-primary-text);
}
/* Mobile: always show the ⋮ button (no hover) */
@media (hover: none) {
	.lists-nav__actions {
		opacity: 1;
	}
}
.lists-view {
	padding: 12px 0 24px 24px;
}
.lists-view h2 {
	margin: 0 0 4px;
	padding: 44px 24px 0 0;
}
.lists-view__counts {
	margin: 0 0 8px;
	padding: 0 24px 0 0;
	font-size: 0.85em;
	color: var(--color-text-lighter);
	display: flex;
	gap: 6px;
}
.lists-view__count-active {
	font-weight: 600;
	color: var(--color-main-text);
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

/* ── Mobile: reclaim horizontal space ── */
@media (max-width: 768px) {
	.lists-view {
		padding: 4px 0 16px 8px;
	}
	.lists-view h2 {
		padding: 44px 8px 0 0;
		font-size: 1.15em;
	}
	.lists-view__counts {
		padding: 0 8px 0 0;
	}
	.lists-view__description {
		padding: 0 8px 0 0;
		font-size: 0.9em;
	}
	.lists-empty {
		padding: 80px 16px 32px;
	}
}
</style>
