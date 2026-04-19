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
</template>

<script>
import { NcAppNavigation } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { useListsStore } from './store/lists.js'
import ItemList from './components/ItemList.vue'
import ShareModal from './components/ShareModal.vue'
import ListFormModal from './components/ListFormModal.vue'
import ReorderModal from './components/ReorderModal.vue'

export default {
	name: 'App',

	components: { NcAppNavigation, ItemList, ShareModal, ListFormModal, ReorderModal },

	setup() {
		const store = useListsStore()
		store.fetchAll()
		return { store }
	},

	data() {
		return {
			openMenuId: null,
			shareTarget: null,
			formTarget: undefined, // undefined = hidden, null = create mode, object = edit mode
			reorderOpen: false,
			currentUser: window.OC?.currentUser ?? '',
		}
	},

	mounted() {
		document.addEventListener('click', this.closeMenu)
	},

	beforeUnmount() {
		document.removeEventListener('click', this.closeMenu)
	},

	methods: {
		t,

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
