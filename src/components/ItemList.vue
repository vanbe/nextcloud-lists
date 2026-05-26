<template>
	<div
		class="item-list"
		@touchstart.passive="onPullStart"
		@touchmove="onPullMove"
		@touchend.passive="onPullEnd"
		@touchcancel.passive="onPullEnd">
		<div
			class="item-list__pull"
			:class="{
				'item-list__pull--refreshing': pullRefreshing,
				'item-list__pull--animate': !pull.tracking,
			}"
			:style="{ height: pullHeight + 'px' }">
			<span v-if="pullRefreshing" class="item-list__pull-label">{{ t('lists', 'Refreshing…') }}</span>
			<span v-else-if="pullHeight >= pullThreshold" class="item-list__pull-label">{{ t('lists', 'Release to refresh') }}</span>
			<span v-else-if="pullHeight > 0" class="item-list__pull-label">{{ t('lists', 'Pull to refresh') }}</span>
		</div>

		<ConfirmModal
			v-if="confirm.show"
			:message="confirm.message"
			:confirm-label="confirm.label"
			@confirm="confirm.resolve(true); confirm.show = false"
			@cancel="confirm.resolve(false); confirm.show = false" />

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
					:class="{ 'cat-bar__chip--active': addCategoryId === cat.id, 'cat-bar__chip--icon': !!cat.icon }"
					:title="cat.name"
					@click="toggleAddCategory(cat.id)">
					<span v-if="cat.icon" class="cat-bar__chip-icon">{{ cat.icon }}</span>
					<span v-else class="cat-bar__chip-name">{{ cat.name }}</span>
				</button>
				<button class="cat-bar__manage" :title="t('lists', 'Manage categories')" @click="manageOpen = true">
					⚙
				</button>
				<button class="cat-bar__add" @click="onAddCategory">
					+
				</button>
			</div>

			<ManageCategoriesModal
				v-if="manageOpen"
				:list-id="list.id"
				@close="manageOpen = false" />

			<!-- Unchecked items grouped by category -->
			<ul ref="listEl" class="item-list__items">
				<!-- "Check all active" header -->
				<li v-if="store.unchecked.length > 1" class="item-list__separator item-list__separator--top">
					<span></span>
					<button class="item-list__bulk-btn" @click="askCheckAll">
						{{ t('lists', 'Check all') }}
					</button>
				</li>

				<!-- Categorised groups -->
				<template v-for="group in groupedUnchecked" :key="group.categoryId ?? 'none'">
					<li v-if="catStore.categories.length" class="item-list__group-header">
						<span>
							<span v-if="group.categoryIcon" class="item-list__group-icon">{{ group.categoryIcon }}</span>
							{{ group.categoryName }}
						</span>
						<button
							v-if="group.items.length > 1"
							class="item-list__bulk-btn"
							@click="askCheckCategory(group)">
							{{ t('lists', 'Check all') }}
						</button>
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
						<!-- Quantity stepper — left of title, fixed-width so titles align -->
						<div v-if="list.hasQuantities" class="item-list__stepper" @click.stop>
							<button
								class="item-list__step-btn"
								:disabled="(item.quantity ?? 1) <= 1"
								@click="changeQty(item, -1)">−</button>
							<span class="item-list__step-val">{{ item.quantity ?? 1 }}</span>
							<button class="item-list__step-btn" @click="changeQty(item, +1)">+</button>
						</div>
						<span
							v-if="editingItemId !== item.id"
							class="item-list__title"
							:title="t('lists', 'Double-click to rename')"
							@dblclick="startEdit(item)">{{ item.title }}</span>
						<input
							v-else
							class="item-list__title-input"
							:value="editDraft"
							@input="editDraft = $event.target.value"
							@blur="commitEdit(item)"
							@keydown.enter.prevent="commitEdit(item)"
							@keydown.escape.prevent="cancelEdit" />
						<button
							v-if="editingItemId !== item.id"
							class="item-list__edit"
							:title="t('lists', 'Rename')"
							@click.stop="startEdit(item)">✎</button>
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
									<span v-if="cat.icon" class="item-list__cat-option-icon">{{ cat.icon }}</span>
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
						<div class="item-list__separator-actions">
							<button
								class="item-list__bulk-btn"
								:title="checkedSortTitle"
								@click="store.toggleCheckedSort">
								{{ checkedSortLabel }}
							</button>
							<button class="item-list__bulk-btn" @click="askUncheckAll">
								{{ t('lists', 'Uncheck all') }}
							</button>
							<button class="item-list__clear-checked" @click="askClearChecked">
								{{ t('lists', 'Clear all') }}
							</button>
						</div>
					</li>
					<!-- Search filter -->
					<li class="item-list__checked-search">
						<input
							v-model="checkedSearch"
							class="item-list__checked-search-input"
							type="search"
							:placeholder="t('lists', 'Search checked items…')" />
					</li>
					<!-- Items -->
					<li
						v-for="item in filteredChecked"
						:key="item.id"
						:data-item-id="item.id"
						class="item-list__item item-list__item--checked">
						<input
							type="checkbox"
							:checked="item.checked"
							class="item-list__checkbox"
							@change="store.toggle(list.id, item.id)" />
						<span
							v-if="editingItemId !== item.id"
							class="item-list__title item-list__title--checked"
							:title="t('lists', 'Double-click to rename')"
							@dblclick="startEdit(item)">{{ item.title }}</span>
						<input
							v-else
							class="item-list__title-input item-list__title-input--checked"
							:value="editDraft"
							@input="editDraft = $event.target.value"
							@blur="commitEdit(item)"
							@keydown.enter.prevent="commitEdit(item)"
							@keydown.escape.prevent="cancelEdit" />
						<span
							v-if="catStore.categories.length && item.categoryId && editingItemId !== item.id"
							class="item-list__cat-chip">
							{{ categoryLabel(item.categoryId) }}
						</span>
						<button
							v-if="editingItemId !== item.id"
							class="item-list__edit"
							:title="t('lists', 'Rename')"
							@click.stop="startEdit(item)">✎</button>
						<button
							class="item-list__delete"
							:title="t('lists', 'Delete')"
							@click="store.destroy(list.id, item.id)">
							✕
						</button>
					</li>
					<li v-if="filteredChecked.length === 0 && checkedSearch.trim()" class="item-list__no-results">
						{{ t('lists', 'No items match your search') }}
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
import ConfirmModal from './ConfirmModal.vue'
import ManageCategoriesModal from './ManageCategoriesModal.vue'

export default {
	name: 'ItemList',

	components: { ItemInput, ConfirmModal, ManageCategoriesModal },

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
			addCategoryId: null,
			catPickerItemId: null,
			manageOpen: false,
			confirm: { show: false, message: '', label: 'OK', resolve: null },
			editingItemId: null,
			editDraft: '',
			checkedSearch: '',
			// Pull-to-refresh state
			pull: { startY: 0, tracking: false },
			pullHeight: 0,
			pullThreshold: 70,
			pullMax: 110,
			pullRefreshing: false,
		}
	},

	computed: {
		checkedSortLabel() {
			return { alpha: t('lists', 'A-Z'), recent: t('lists', 'Recent'), category: t('lists', 'Category') }[this.store.checkedSortMode] ?? 'A-Z'
		},

		checkedSortTitle() {
			return {
				alpha: t('lists', 'Sort by check date'),
				recent: t('lists', 'Sort by category'),
				category: t('lists', 'Sort A-Z'),
			}[this.store.checkedSortMode] ?? ''
		},

		filteredChecked() {
			let items = this.store.checked

			// Re-sort by category name when mode is 'category' (store only has categoryId, not name)
			if (this.store.checkedSortMode === 'category') {
				const catMap = new Map(this.catStore.categories.map((c) => [c.id, c]))
				items = [...items].sort((a, b) => {
					const catA = catMap.get(a.categoryId)?.name ?? '￿'
					const catB = catMap.get(b.categoryId)?.name ?? '￿'
					const catCmp = catA.localeCompare(catB, undefined, { sensitivity: 'base' })
					if (catCmp !== 0) return catCmp
					return (a.title ?? '').localeCompare(b.title ?? '', undefined, { sensitivity: 'base', numeric: true })
				})
			}

			const q = this.checkedSearch.trim().toLowerCase()
			if (!q) return items
			return items.filter((i) => i.title?.toLowerCase().includes(q))
		},

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
					groups.push({ categoryId: cat.id, categoryName: cat.name, categoryIcon: cat.icon || '', items: catItems })
				}
			}

			// Uncategorised at the end
			const uncategorised = items.filter((i) => !i.categoryId || !catMap.has(i.categoryId))
			if (uncategorised.length) {
				groups.push({ categoryId: null, categoryName: t('lists', 'Other'), categoryIcon: '', items: uncategorised })
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
			this.checkedSearch = ''
			this.editingItemId = null
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

		// Returns the icon (if set) or the name — used for the compact chip on checked items
		categoryLabel(categoryId) {
			const cat = this.catStore.categories.find((c) => c.id === categoryId)
			return cat ? (cat.icon || cat.name) : ''
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

		onPullStart(e) {
			if (this.pullRefreshing) return
			if (e.touches.length !== 1) return
			// Only arm when the scroll container is at top.
			const scroller = this.getScroller()
			const atTop = scroller ? scroller.scrollTop <= 0 : (window.scrollY <= 0)
			if (!atTop) return
			// Skip if touch starts inside an interactive control
			if (e.target?.closest?.('input, textarea, button, [contenteditable="true"]')) return
			this.pull = { startY: e.touches[0].clientY, tracking: true }
			this.pullHeight = 0
		},

		onPullMove(e) {
			if (!this.pull.tracking) return
			const dy = e.touches[0].clientY - this.pull.startY
			if (dy <= 0) {
				this.pullHeight = 0
				return
			}
			// Block default scroll/refresh once we're clearly pulling down
			if (dy > 10 && e.cancelable) e.preventDefault()
			// Resistance: pull diminishes past threshold
			const resisted = dy <= this.pullThreshold
				? dy
				: this.pullThreshold + (dy - this.pullThreshold) * 0.4
			this.pullHeight = Math.min(resisted, this.pullMax)
		},

		async onPullEnd() {
			if (!this.pull.tracking) return
			this.pull.tracking = false
			if (this.pullHeight >= this.pullThreshold && !this.pullRefreshing) {
				this.pullRefreshing = true
				try {
					await Promise.all([
						this.store.refresh(this.list.id),
						this.catStore.refresh(this.list.id),
					])
				} finally {
					this.pullRefreshing = false
					this.pullHeight = 0
				}
			} else {
				this.pullHeight = 0
			}
		},

		getScroller() {
			// Find nearest ancestor that actually scrolls; falls back to window.
			let el = this.$el?.parentElement
			while (el && el !== document.body) {
				const style = getComputedStyle(el)
				if (/(auto|scroll|overlay)/.test(style.overflowY) && el.scrollHeight > el.clientHeight) {
					return el
				}
				el = el.parentElement
			}
			return null
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

		startEdit(item) {
			this.catPickerItemId = null
			this.editingItemId = item.id
			this.editDraft = item.title
			nextTick(() => {
				const input = this.$el.querySelector('.item-list__title-input')
				if (input) {
					input.focus()
					input.select()
				}
			})
		},

		async commitEdit(item) {
			if (this.editingItemId !== item.id) return
			const newTitle = this.editDraft.trim()
			this.editingItemId = null
			this.editDraft = ''
			if (newTitle && newTitle !== item.title) {
				await this.store.rename(this.list.id, item.id, newTitle)
			}
		},

		cancelEdit() {
			this.editingItemId = null
			this.editDraft = ''
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

		async askClearChecked() {
			const n = this.store.checked.length
			const ok = await this.ask(
				t('lists', 'Delete all {n} checked items? This cannot be undone.', { n }),
				t('lists', 'Clear all'),
			)
			if (!ok) return
			await this.clearChecked()
		},

		// ── Bulk actions ──────────────────────────────────────────────────────

		ask(message, label) {
			return new Promise((resolve) => {
				this.confirm = { show: true, message, label, resolve }
			})
		},

		async askCheckAll() {
			const n = this.store.unchecked.length
			const ok = await this.ask(
				t('lists', 'Check all {n} active items?', { n }),
				t('lists', 'Check all'),
			)
			if (!ok) return
			await Promise.all(this.store.unchecked.map((item) => this.store.toggle(this.list.id, item.id)))
		},

		async askCheckCategory(group) {
			const n = group.items.length
			const ok = await this.ask(
				t('lists', 'Check all {n} items in "{name}"?', { n, name: group.categoryName }),
				t('lists', 'Check all'),
			)
			if (!ok) return
			await Promise.all(group.items.map((item) => this.store.toggle(this.list.id, item.id)))
		},

		async askUncheckAll() {
			const n = this.store.checked.length
			const ok = await this.ask(
				t('lists', 'Uncheck all {n} checked items?', { n }),
				t('lists', 'Uncheck all'),
			)
			if (!ok) return
			await Promise.all(this.store.checked.map((item) => this.store.toggle(this.list.id, item.id)))
		},
	},
}
</script>

<style scoped>
.item-list {
	padding: 24px;
}

/* ── Pull to refresh ── */
.item-list__pull {
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
	color: var(--color-text-lighter);
	font-size: 0.85em;
}
.item-list__pull--animate {
	transition: height 0.2s ease-out;
}
.item-list__pull--refreshing {
	color: var(--color-primary);
}
.item-list__pull-label {
	white-space: nowrap;
}

/* ── Category bar ── */
.cat-bar {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	margin: 8px 0;
	align-items: center;
}
.cat-bar__chip {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 2px 10px;
	border: 1px solid var(--color-border);
	border-radius: 14px;
	background: var(--color-background-dark);
	color: var(--color-main-text);
	font-size: 0.85em;
	cursor: pointer;
	transition: background 0.15s;
	min-height: 26px;
}
.cat-bar__chip--icon {
	padding: 0;
	width: 32px;
	height: 32px;
	border-radius: 50%;
	font-size: 1.05em;
}
.cat-bar__chip:hover {
	background: var(--color-background-hover);
}
.cat-bar__chip--active {
	background: var(--color-primary);
	color: var(--color-primary-text);
	border-color: var(--color-primary);
}
.cat-bar__chip--active:hover {
	background: var(--color-primary);
	filter: brightness(1.05);
}
.cat-bar__chip-icon {
	line-height: 1;
}
.cat-bar__chip-name {
	line-height: 1;
}
.cat-bar__manage,
.cat-bar__add {
	width: 32px;
	height: 32px;
	border: 1px dashed var(--color-border);
	border-radius: 50%;
	background: none;
	color: var(--color-text-lighter);
	font-size: 0.95em;
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	justify-content: center;
}
.cat-bar__manage:hover,
.cat-bar__add:hover {
	background: var(--color-background-hover);
	color: var(--color-main-text);
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
	display: flex;
	align-items: center;
	justify-content: space-between;
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
	font-size: 1.05em;
	font-weight: 600;
	cursor: text;
}
.item-list__title--checked {
	text-decoration: line-through;
	color: var(--color-text-lighter);
	font-weight: 400;
	font-size: 1em;
}
.item-list__title-input {
	flex: 1;
	font-size: 1.05em;
	font-weight: 600;
	border: none;
	border-bottom: 2px solid var(--color-primary);
	border-radius: 0;
	background: transparent;
	color: var(--color-main-text);
	padding: 0 2px;
	outline: none;
	min-width: 0;
	line-height: inherit;
}
.item-list__title-input--checked {
	font-size: 1em;
	font-weight: 400;
	color: var(--color-text-lighter);
}
.item-list__edit {
	background: none;
	border: none;
	cursor: pointer;
	opacity: 0;
	color: var(--color-text-lighter);
	padding: 4px;
	font-size: 0.9em;
	flex-shrink: 0;
	transition: opacity 0.12s, color 0.12s;
}
.item-list__item:hover .item-list__edit {
	opacity: 1;
}
.item-list__edit:hover {
	color: var(--color-primary);
}
/* Always slightly visible on touch devices (no hover) */
@media (hover: none) {
	.item-list__edit {
		opacity: 0.35;
	}
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
	align-items: center;
	flex-shrink: 0;
	gap: 2px;
}
.item-list__step-btn {
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-radius: 50%;
	cursor: pointer;
	font-size: 1em;
	line-height: 1;
	width: 28px;
	height: 28px;
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
	border-color: var(--color-primary);
}
.item-list__step-btn:disabled {
	opacity: 0.35;
	cursor: default;
}
.item-list__step-val {
	width: 32px;
	text-align: center;
	font-size: 0.9em;
	font-weight: 700;
	color: var(--color-primary);
	user-select: none;
	flex-shrink: 0;
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
.item-list__separator--top {
	padding: 4px 4px 4px;
	border-bottom: 1px solid var(--color-border-dark);
	margin-bottom: 4px;
}
.item-list__separator-actions {
	display: flex;
	gap: 6px;
	align-items: center;
}
.item-list__bulk-btn {
	background: none;
	border: 1px solid var(--color-border);
	border-radius: 12px;
	cursor: pointer;
	font-size: 0.78em;
	font-weight: normal;
	text-transform: none;
	letter-spacing: 0;
	color: var(--color-text-lighter);
	padding: 2px 8px;
	white-space: nowrap;
	transition: background 0.12s, color 0.12s;
}
.item-list__bulk-btn:hover {
	background: var(--color-background-hover);
	color: var(--color-main-text);
}
.item-list__group-header .item-list__bulk-btn {
	margin-left: auto;
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

/* ── Checked items — search bar ── */
.item-list__checked-search {
	list-style: none;
	padding: 6px 4px 2px;
}
.item-list__checked-search-input {
	width: 100%;
	box-sizing: border-box;
	padding: 6px 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-pill, 20px);
	background: var(--color-background-dark);
	color: var(--color-main-text);
	font-size: 0.9em;
	outline: none;
	transition: border-color 0.15s;
}
.item-list__checked-search-input:focus {
	border-color: var(--color-primary);
	background: var(--color-main-background);
}
.item-list__checked-search-input::placeholder {
	color: var(--color-text-maxcontrast);
}

/* ── Category chip on checked items ── */
.item-list__cat-chip {
	flex-shrink: 0;
	font-size: 0.75em;
	color: var(--color-text-lighter);
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-radius: 10px;
	padding: 1px 7px;
	white-space: nowrap;
	max-width: 80px;
	overflow: hidden;
	text-overflow: ellipsis;
}

/* ── No search results ── */
.item-list__no-results {
	list-style: none;
	padding: 10px 4px;
	font-size: 0.9em;
	color: var(--color-text-lighter);
	font-style: italic;
}
</style>
