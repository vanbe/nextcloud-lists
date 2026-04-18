<template>
	<div class="item-list">
		<ItemInput :list-id="listId" @add="onAdd" @select-suggestion="onSelectSuggestion" />

		<div v-if="store.loading" class="item-list__loading">
			{{ t('lists', 'Loading…') }}
		</div>
		<div v-else-if="store.error" class="item-list__error">
			{{ store.error }}
		</div>
		<ul v-else ref="listEl" class="item-list__items">
			<li
				v-for="item in store.unchecked"
				:key="item.id"
				:data-item-id="item.id"
				class="item-list__item">
				<input
					type="checkbox"
					:checked="item.checked"
					class="item-list__checkbox"
					@change="store.toggle(listId, item.id)"
				/>
				<span class="item-list__title">{{ item.title }}</span>
				<button
					class="item-list__delete"
					:title="t('lists', 'Delete')"
					@click="store.destroy(listId, item.id)">
					✕
				</button>
			</li>

			<li v-if="store.checked.length" class="item-list__separator">
				{{ t('lists', 'Checked') }}
			</li>

			<li
				v-for="item in store.checked"
				:key="item.id"
				:data-item-id="item.id"
				class="item-list__item item-list__item--checked">
				<input
					type="checkbox"
					:checked="item.checked"
					class="item-list__checkbox"
					@change="store.toggle(listId, item.id)"
				/>
				<span class="item-list__title item-list__title--checked">{{ item.title }}</span>
				<button
					class="item-list__delete"
					:title="t('lists', 'Delete')"
					@click="store.destroy(listId, item.id)">
					✕
				</button>
			</li>
		</ul>
	</div>
</template>

<script>
import { nextTick } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { useItemsStore } from '../store/items.js'
import ItemInput from './ItemInput.vue'

export default {
	name: 'ItemList',

	components: { ItemInput },

	props: {
		listId: { type: Number, required: true },
	},

	setup(props) {
		const store = useItemsStore()
		store.fetchAll(props.listId)
		return { store }
	},

	watch: {
		listId(id) {
			this.store.reset()
			this.store.fetchAll(id)
		},
	},

	methods: {
		t,

		async onAdd(title) {
			await this.store.create(this.listId, title)
		},

		async onSelectSuggestion(item) {
			// If checked → uncheck it first
			if (item.checked) {
				await this.store.toggle(this.listId, item.id)
			}
			// After DOM update, scroll to the item and give it focus
			await nextTick()
			this.scrollToItem(item.id)
		},

		scrollToItem(id) {
			const el = this.$refs.listEl?.querySelector(`[data-item-id="${id}"]`)
			if (el) {
				el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
				el.classList.add('item-list__item--highlight')
				setTimeout(() => el.classList.remove('item-list__item--highlight'), 1200)
			}
		},
	},
}
</script>

<style scoped>
.item-list {
	padding: 24px;
}
.item-list__items {
	list-style: none;
	margin: 8px 0 0;
	padding: 0;
}
.item-list__item {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 4px;
	border-bottom: 1px solid var(--color-border-dark);
	transition: background 0.3s;
}
.item-list__item--highlight {
	background: var(--color-primary-light, #e8f4ff);
}
.item-list__item:hover .item-list__delete {
	opacity: 1;
}
.item-list__checkbox {
	cursor: pointer;
	width: 18px;
	height: 18px;
	flex-shrink: 0;
}
.item-list__title {
	flex: 1;
}
.item-list__title--checked {
	text-decoration: line-through;
	color: var(--color-text-lighter);
}
.item-list__delete {
	background: none;
	border: none;
	cursor: pointer;
	opacity: 0;
	color: var(--color-text-lighter);
	padding: 4px;
}
.item-list__separator {
	list-style: none;
	padding: 12px 4px 4px;
	font-size: 0.85em;
	color: var(--color-text-lighter);
	font-weight: bold;
	text-transform: uppercase;
	letter-spacing: 0.05em;
}
.item-list__loading,
.item-list__error {
	padding: 16px 0;
	color: var(--color-text-lighter);
}
.item-list__error {
	color: var(--color-error);
}
</style>
