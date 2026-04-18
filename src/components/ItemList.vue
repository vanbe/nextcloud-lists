<template>
	<div class="item-list">
		<form class="item-list__add" @submit.prevent="onAdd">
			<input
				v-model="newTitle"
				class="item-list__input"
				type="text"
				:placeholder="t('lists', 'Add an item…')"
			/>
			<button class="item-list__add-btn" type="submit" :disabled="!newTitle.trim()">
				{{ t('lists', 'Add') }}
			</button>
		</form>

		<div v-if="store.loading" class="item-list__loading">
			{{ t('lists', 'Loading…') }}
		</div>
		<div v-else-if="store.error" class="item-list__error">
			{{ store.error }}
		</div>
		<ul v-else class="item-list__items">
			<li
				v-for="item in store.unchecked"
				:key="item.id"
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
import { translate as t } from '@nextcloud/l10n'
import { useItemsStore } from '../store/items.js'

export default {
	name: 'ItemList',

	props: {
		listId: {
			type: Number,
			required: true,
		},
	},

	setup(props) {
		const store = useItemsStore()
		store.fetchAll(props.listId)
		return { store }
	},

	data() {
		return { newTitle: '' }
	},

	watch: {
		listId(id) {
			this.store.reset()
			this.store.fetchAll(id)
		},
	},

	methods: {
		t,

		async onAdd() {
			const title = this.newTitle.trim()
			if (!title) return
			this.newTitle = ''
			await this.store.create(this.listId, title)
		},
	},
}
</script>

<style scoped>
.item-list {
	padding: 24px;
}
.item-list__add {
	display: flex;
	gap: 8px;
	margin-bottom: 16px;
}
.item-list__input {
	flex: 1;
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
}
.item-list__add-btn {
	padding: 8px 16px;
	background: var(--color-primary);
	color: var(--color-primary-text);
	border: none;
	border-radius: var(--border-radius);
	cursor: pointer;
}
.item-list__add-btn:disabled {
	opacity: 0.5;
	cursor: default;
}
.item-list__items {
	list-style: none;
	margin: 0;
	padding: 0;
}
.item-list__item {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 4px;
	border-bottom: 1px solid var(--color-border-dark);
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
