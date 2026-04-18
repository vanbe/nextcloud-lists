<template>
	<NcAppNavigation :aria-label="t('lists', 'Lists navigation')">
		<template #list>
			<li class="lists-nav__new">
				<button class="lists-nav__new-btn" @click="onNewList">
					+ {{ t('lists', 'New list') }}
				</button>
			</li>
			<li
				v-for="list in store.lists"
				:key="list.id"
				class="lists-nav__item"
				:class="{ 'lists-nav__item--active': list.id === store.selectedId }"
				@click="store.select(list.id)">
				<span class="lists-nav__name">{{ list.name }}</span>
				<button
					class="lists-nav__delete"
					:title="t('lists', 'Delete')"
					@click.stop="store.destroy(list.id)">
					✕
				</button>
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
			<ItemList :list-id="store.selected.id" />
			<ShareSection :list-id="store.selected.id" />
		</div>
	</main>
</template>

<script>
import { NcAppNavigation } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { useListsStore } from './store/lists.js'
import ItemList from './components/ItemList.vue'
import ShareSection from './components/ShareSection.vue'

export default {
	name: 'App',

	components: { NcAppNavigation, ItemList, ShareSection },

	setup() {
		const store = useListsStore()
		store.fetchAll()
		return { store }
	},

	methods: {
		t,

		async onNewList() {
			const name = window.prompt(t('lists', 'List name'))
			if (name?.trim()) {
				await this.store.create(name.trim())
			}
		},
	},
}
</script>

<style scoped>
.lists-nav__new {
	list-style: none;
	padding: 4px 12px;
}
.lists-nav__new-btn {
	width: 100%;
	text-align: left;
	background: none;
	border: none;
	cursor: pointer;
	padding: 8px;
	color: var(--color-primary);
	font-weight: bold;
}
.lists-nav__item {
	list-style: none;
	display: flex;
	align-items: center;
	padding: 4px 12px;
	cursor: pointer;
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
.lists-nav__delete {
	background: none;
	border: none;
	cursor: pointer;
	opacity: 0;
	color: var(--color-text-lighter);
}
.lists-nav__item:hover .lists-nav__delete {
	opacity: 1;
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
