<template>
	<div class="item-list">
		<ItemInput
			:list-id="list.id"
			:has-quantities="list.hasQuantities"
			:categories="catStore.categories"
			:default-category-id="addCategoryId"
			@add="onAdd"
			@select-suggestion="onSelectSuggestion" />

		<div v-if="store.loading" class="item-list__loading">
			{{ t('lists', 'Loading…') }}
		</div>

		<template v-else>
			<!-- Category management bar -->
			<div class="cat-bar">
				<button
					v-for="cat in catStore.categories"
					:key="cat.id"
					class="cat-bar__chip"
					:class="{ 'cat-bar__chip--active': addCategoryId === cat.id }"
					@click="toggleAddCategory(cat.id)">
					<span
						v-if="editingCatId !== cat.id"
						class="cat-bar__chip-name"
						@dblclick.stop="startRename(cat)">
						{{ cat.name }}
					</span>
					<input
						v-else
						:ref="`catInput_${cat.id}`"
						v-model="editingCatName"
						class="cat-bar__chip-input"
						type="text"
						maxlength="255"
						@keydown.enter.prevent="confirmRename(cat)"
						@keydown.esc.prevent="cancelRename"
						@blur="confirmRename(cat)" />
					<button
						class="cat-bar__chip-del"
						:title="t('lists', 'Delete category')"
						@click.stop="onDeleteCategory(cat)">
						✕
					</button>
				</button>
				<button class="cat-bar__add" @click="onAddCategory">
					+ {{ t('lists', 'Category') }}
				</button>
			</div>

			<!-- Unchecked items grouped by category -->
			<ul ref="listEl" class="item-list__items">
				<!-- Categorised groups -->
				<template v-for="group in groupedUnchecked" :key="group.categoryId ?? 'none'">
					<li v-if="catStore.categories.length" class="item-list__group-header">
						<span>{{ group.categoryName }}</span>
					</li>
					<li
						v-for="item in group.items"
						:key="item.id"
						:data-item-id="item.id"
						class="item-list__item">
						<input
							type="checkbox"
							:checked="item.checked"
							class="item-list__checkbox"
							@change="store.toggle(list.id, item.id)" />
						<span class="item-list__title">{{ item.title }}</span>
						<!-- Quantity stepper -->
						<div v-if="list.hasQuantities" class="item-list__stepper" @click.stop>
							<button
								class="item-list__step-btn"
								:disabled="(item.quantity ?? 1) <= 1"
								@click="changeQty(item, -1)">−</button>
							<span class="item-list__step-val">{{ item.quantity ?? 1 }}</span>
							<button class="item-list__step-btn" @click="changeQty(item, +1)">+</button>
						</div>
						<!-- Category badge / picker -->
						<div v-if="catStore.categories.length" class="item-list__cat-wrap">
							<button
								class="item-list__cat-badge"
								:class="{ 'item-list__cat-badge--set': item.categoryId }"
								:title="t('lists', 'Change category')"
								@click.stop="openCatPicker(item)">
								{{ categoryName(item.categoryId) }}
							</button>
							<div v-if="catPickerItemId === item.id" class="item-list__cat-picker">
								<button
									class="item-list__cat-option"
									@click="assignCategory(item, null)">
									— {{ t('lists', 'None') }}
								</button>
								<button
									v-for="cat in catStore.categories"
									:key="cat.id"
									class="item-list__cat-option"
									:class="{ 'item-list__cat-option--active': item.categoryId === cat.id }"
									@click="assignCategory(item, cat.id)">
									{{ cat.name }}
								</button>
							</div>
						</div>
						<button
							class="item-list__delete"
							:title="t('lists', 'Delete')"
							@click="store.destroy(list.id, item.id)">
							✕
						</button>
					</li>
				</template>

				<!-- Checked section -->
				<template v-if="store.checked.length">
					<li class="item-list__separator">
						<span>{{ t('lists', 'Checked') }}</span>
						<button class="item-list__clear-checked" @click="clearChecked">
							{{ t('lists', 'Clear all') }}
						</button>
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
							@change="store.toggle(list.id, item.id)" />
						<span class="item-list__title item-list__title--checked">{{ item.title }}</span>
						<button
							class="item-list__delete"
							:title="t('lists', 'Delete')"
							@click="store.destroy(list.id, item.id)">
							✕
						</button>
					</li>
				</template>
			</ul>
		</template>
	</div>
</template>

<script>
import { nextTick } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { useItemsStore } from '../store/items.js'
import { useCategoriesStore } from '../store/categories.js'
import ItemInput from './ItemInput.vue'

export default {
	name: 'ItemList',

	components: { ItemInput },

	props: {
		list: { type: Object, required: true },
	},

	setup(props) {
		const store = useItemsStore()
		const catStore = useCategoriesStore()
		store.fetchAll(props.list.id)
		catStore.fetchAll(props.list.id)
		return { store, catStore }
	},

	data() {
		return {
			addCategoryId: null,   // category pre-selected for new items
			editingCatId: null,    // category being renamed
			editingCatName: '',
			catPickerItemId: null, // item whose category picker is open
		}
	},

	computed: {
		groupedUnchecked() {
			const items = this.store.unchecked
			if (!this.catStore.categories.length) {
				return [{ categoryId: null, categoryName: '', items }]
			}

			const groups = []
			const catMap = new Map(this.catStore.categories.map((c) => [c.id, c]))

			// One group per category, in category order
			for (const cat of this.catStore.categories) {
				const catItems = items.filter((i) => i.categoryId === cat.id)
				if (catItems.length) {
					groups.push({ categoryId: cat.id, categoryName: cat.name, items: catItems })
				}
			}

			// Uncategorised at the end
			const uncategorised = items.filter((i) => !i.categoryId || !catMap.has(i.categoryId))
			if (uncategorised.length) {
				groups.push({ categoryId: null, categoryName: t('lists', 'Other'), items: uncategorised })
			}

			return groups
		},
	},

	watch: {
		'list.id'(id) {
			this.store.reset()
			this.catStore.reset()
			this.store.fetchAll(id)
			this.catStore.fetchAll(id)
			this.addCategoryId = null
			this.catPickerItemId = null
		},
	},

	mounted() {
		document.addEventListener('click', this.closeCatPicker)
	},

	beforeUnmount() {
		document.removeEventListener('click', this.closeCatPicker)
	},

	methods: {
		t,

		categoryName(categoryId) {
			if (!categoryId) return t('lists', 'Category…')
			const cat = this.catStore.categories.find((c) => c.id === categoryId)
			return cat ? cat.name : t('lists', 'Category…')
		},

		toggleAddCategory(catId) {
			this.addCategoryId = this.addCategoryId === catId ? null : catId
		},

		async onAddCategory() {
			const name = window.prompt(t('lists', 'Category name'))
			if (name?.trim()) {
				const cat = await this.catStore.create(this.list.id, name.trim())
				if (cat) this.addCategoryId = cat.id
			}
		},

		startRename(cat) {
			this.editingCatId = cat.id
			this.editingCatName = cat.name
			nextTick(() => {
				const ref = this.$refs[`catInput_${cat.id}`]
				const el = Array.isArray(ref) ? ref[0] : ref
				el?.focus()
				el?.select()
			})
		},

		async confirmRename(cat) {
			const name = this.editingCatName.trim()
			if (name && name !== cat.name) {
				await this.catStore.rename(this.list.id, cat.id, name)
			}
			this.cancelRename()
		},

		cancelRename() {
			this.editingCatId = null
			this.editingCatName = ''
		},

		async onDeleteCategory(cat) {
			if (!window.confirm(t('lists', 'Delete category "{name}"? Items will be unassigned.', { name: cat.name }))) return
			if (this.addCategoryId === cat.id) this.addCategoryId = null
			await this.catStore.destroy(this.list.id, cat.id)
			// Reflect unassignment in local item state without a full refetch
			this.store.items.forEach((item) => {
				if (item.categoryId === cat.id) item.categoryId = null
			})
		},

		openCatPicker(item) {
			this.catPickerItemId = this.catPickerItemId === item.id ? null : item.id
		},

		closeCatPicker(e) {
			if (!e.target.closest?.('.item-list__cat-wrap')) {
				this.catPickerItemId = null
			}
		},

		async assignCategory(item, categoryId) {
			this.catPickerItemId = null
			await this.store.setCategory(this.list.id, item.id, categoryId)
		},

		async onAdd({ title, quantity }) {
			await this.store.create(this.list.id, title, this.addCategoryId, quantity)
		},

		async onSelectSuggestion(item) {
			if (item.checked) {
				await this.store.toggle(this.list.id, item.id)
			}
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

		async changeQty(item, delta) {
			const current = item.quantity ?? 1
			const qty = Math.max(1, current + delta)
			await this.store.updateQuantity(this.list.id, item.id, qty)
		},

		async clearChecked() {
			const checked = [...this.store.checked]
			await Promise.all(checked.map((item) => this.store.destroy(this.list.id, item.id)))
		},
	},
}
</script>

<style scoped>
.item-list {
	padding: 24px;
}

/* ── Category bar ── */
.cat-bar {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
	margin: 12px 0;
	align-items: center;
}
.cat-bar__chip {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 4px 10px 4px 12px;
	border: 1px solid var(--color-border);
	border-radius: 20px;
	background: var(--color-background-dark);
	color: var(--color-main-text);
	font-size: 0.85em;
	cursor: pointer;
	transition: background 0.15s;
}
.cat-bar__chip--active {
	background: var(--color-primary);
	color: var(--color-primary-text);
	border-color: var(--color-primary);
}
.cat-bar__chip-name {
	cursor: text;
}
.cat-bar__chip-input {
	width: 90px;
	border: none;
	background: transparent;
	color: inherit;
	font-size: 1em;
	outline: none;
	padding: 0;
}
.cat-bar__chip-del {
	background: none;
	border: none;
	cursor: pointer;
	color: inherit;
	opacity: 0.6;
	font-size: 0.8em;
	padding: 0 2px;
	line-height: 1;
}
.cat-bar__chip-del:hover {
	opacity: 1;
}
.cat-bar__add {
	padding: 4px 12px;
	border: 1px dashed var(--color-border);
	border-radius: 20px;
	background: none;
	color: var(--color-text-lighter);
	font-size: 0.85em;
	cursor: pointer;
}
.cat-bar__add:hover {
	background: var(--color-background-hover);
}

/* ── Item list ── */
.item-list__items {
	list-style: none;
	margin: 8px 0 0;
	padding: 0;
}
.item-list__group-header {
	list-style: none;
	padding: 14px 4px 4px;
	font-size: 0.8em;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.06em;
	color: var(--color-text-lighter);
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

/* ── Category badge / picker on item ── */
.item-list__cat-wrap {
	position: relative;
}
.item-list__cat-badge {
	background: none;
	border: 1px solid var(--color-border);
	border-radius: 12px;
	padding: 2px 8px;
	font-size: 0.75em;
	color: var(--color-text-lighter);
	cursor: pointer;
	white-space: nowrap;
	max-width: 100px;
	overflow: hidden;
	text-overflow: ellipsis;
}
.item-list__cat-badge--set {
	background: var(--color-background-dark);
	color: var(--color-main-text);
}
.item-list__cat-picker {
	position: absolute;
	right: 0;
	top: calc(100% + 4px);
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
	z-index: 600;
	min-width: 140px;
	padding: 4px 0;
}
.item-list__cat-option {
	display: block;
	width: 100%;
	text-align: left;
	background: none;
	border: none;
	cursor: pointer;
	padding: 7px 14px;
	font-size: 0.9em;
	color: var(--color-main-text);
}
.item-list__cat-option:hover,
.item-list__cat-option--active {
	background: var(--color-background-hover);
}

/* ── Quantity stepper on item rows ── */
.item-list__stepper {
	display: flex;
	align-items: stretch;
	flex-shrink: 0;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	overflow: hidden;
}
.item-list__step-btn {
	background: var(--color-background-dark);
	border: none;
	cursor: pointer;
	font-size: 1.15em;
	line-height: 1;
	/* Large touch target for mobile */
	min-width: 44px;
	min-height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
	color: var(--color-main-text);
	transition: background 0.12s;
	-webkit-tap-highlight-color: transparent;
}
.item-list__step-btn:active:not(:disabled),
.item-list__step-btn:hover:not(:disabled) {
	background: var(--color-primary);
	color: var(--color-primary-text);
}
.item-list__step-btn:disabled {
	opacity: 0.35;
	cursor: default;
}
.item-list__step-val {
	min-width: 40px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 0.95em;
	font-weight: 700;
	background: var(--color-main-background);
	border-left: 1px solid var(--color-border);
	border-right: 1px solid var(--color-border);
	padding: 0 4px;
	user-select: none;
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
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 12px 4px 4px;
	font-size: 0.85em;
	color: var(--color-text-lighter);
	font-weight: bold;
	text-transform: uppercase;
	letter-spacing: 0.05em;
}
.item-list__clear-checked {
	background: none;
	border: none;
	cursor: pointer;
	font-size: 0.9em;
	font-weight: normal;
	text-transform: none;
	letter-spacing: 0;
	color: var(--color-error);
	padding: 2px 6px;
	border-radius: var(--border-radius);
}
.item-list__clear-checked:hover {
	background: var(--color-background-hover);
}
.item-list__loading {
	padding: 16px 0;
	color: var(--color-text-lighter);
}
</style>
