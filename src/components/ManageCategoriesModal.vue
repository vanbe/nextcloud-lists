<template>
	<NcDialog
		:name="t('lists', 'Manage categories')"
		size="normal"
		@closing="$emit('close')">
		<div class="mc__body">
			<ul v-if="categories.length" ref="sortableRoot" class="mc__list">
				<li
					v-for="cat in categories"
					:key="cat.id"
					class="mc__item"
					:data-id="cat.id">
					<button
						class="mc__handle"
						type="button"
						:aria-label="t('lists', 'Drag to reorder')"
						:title="t('lists', 'Drag to reorder')">
						<DragVertical :size="20" />
					</button>

					<button
						class="mc__icon-display"
						:title="t('lists', 'Change icon')"
						@click="openPickerForCat(cat)">
						<span v-if="cat.icon" class="mc__icon-val">{{ cat.icon }}</span>
						<span v-else class="mc__icon-placeholder">🙂</span>
					</button>

					<input
						v-model="drafts[cat.id]"
						class="mc__name-input"
						type="text"
						maxlength="255"
						:placeholder="t('lists', 'Name')"
						@blur="onNameBlur(cat)"
						@keydown.enter.prevent="onNameBlur(cat)" />

					<button
						class="mc__delete"
						:title="t('lists', 'Delete category')"
						@click="onDelete(cat)">
						✕
					</button>
				</li>
			</ul>
			<p v-else class="mc__empty">{{ t('lists', 'No categories yet.') }}</p>

			<div class="mc__add">
				<button
					class="mc__icon-display"
					:title="t('lists', 'Choose icon')"
					@click="openPickerForNew">
					<span v-if="newIcon" class="mc__icon-val">{{ newIcon }}</span>
					<span v-else class="mc__icon-placeholder">🙂</span>
				</button>
				<input
					v-model="newName"
					class="mc__name-input"
					type="text"
					maxlength="255"
					:placeholder="t('lists', 'New category name')"
					@keydown.enter.prevent="onAdd" />
				<NcButton type="primary" :disabled="!newName.trim()" @click="onAdd">
					+ {{ t('lists', 'Add') }}
				</NcButton>
			</div>
		</div>

		<template #actions>
			<NcButton type="primary" @click="$emit('close')">
				{{ t('lists', 'Done') }}
			</NcButton>
		</template>

		<IconPickerDialog
			v-if="picker.open"
			:current="picker.current"
			@save="onIconSave"
			@close="picker.open = false" />
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import Sortable from 'sortablejs'
import DragVertical from 'vue-material-design-icons/DragVertical.vue'
import IconPickerDialog from './IconPickerDialog.vue'
import { useCategoriesStore } from '../store/categories.js'
import { useItemsStore } from '../store/items.js'

export default {
	name: 'ManageCategoriesModal',

	components: { NcDialog, NcButton, DragVertical, IconPickerDialog },

	props: {
		listId: { type: Number, required: true },
	},

	emits: ['close'],

	setup() {
		const catStore = useCategoriesStore()
		const itemsStore = useItemsStore()
		return { catStore, itemsStore }
	},

	data() {
		return {
			drafts: {},
			newName: '',
			newIcon: '',
			picker: { open: false, target: null, current: '' }, // target: catId | 'new'
		}
	},

	computed: {
		categories() {
			return this.catStore.categories
		},
	},

	watch: {
		categories: {
			immediate: true,
			handler(list) {
				const next = {}
				for (const c of list) {
					next[c.id] = this.drafts[c.id] ?? c.name
				}
				this.drafts = next
			},
		},
	},

	mounted() {
		this.attachSortable()
	},

	updated() {
		// The <ul> is conditional on categories.length, so it may appear/disappear.
		// Re-attach Sortable when the ref shows up and we don't have an instance yet.
		if (this.$refs.sortableRoot && !this._sortable) {
			this.attachSortable()
		} else if (!this.$refs.sortableRoot && this._sortable) {
			this._sortable.destroy()
			this._sortable = null
		}
	},

	beforeUnmount() {
		this._sortable?.destroy()
		this._sortable = null
	},

	methods: {
		t,

		openPickerForCat(cat) {
			this.picker = { open: true, target: cat.id, current: cat.icon || '' }
		},

		openPickerForNew() {
			this.picker = { open: true, target: 'new', current: this.newIcon }
		},

		async onIconSave(icon) {
			const target = this.picker.target
			this.picker.open = false
			if (target === 'new') {
				this.newIcon = icon
			} else {
				const cat = this.categories.find((c) => c.id === target)
				if (cat && cat.icon !== icon) {
					await this.catStore.update(this.listId, target, { icon })
				}
			}
		},

		async onNameBlur(cat) {
			const draft = (this.drafts[cat.id] ?? '').trim()
			if (!draft || draft === cat.name) {
				this.drafts[cat.id] = cat.name
				return
			}
			await this.catStore.update(this.listId, cat.id, { name: draft })
		},

		async onDelete(cat) {
			if (!window.confirm(t('lists', 'Delete category "{name}"? Items will be unassigned.', { name: cat.name }))) return
			await this.catStore.destroy(this.listId, cat.id)
			this.itemsStore.items.forEach((item) => {
				if (item.categoryId === cat.id) item.categoryId = null
			})
		},

		async onAdd() {
			const name = this.newName.trim()
			if (!name) return
			const cat = await this.catStore.create(this.listId, name, this.newIcon)
			if (cat) {
				this.newName = ''
				this.newIcon = ''
			}
		},

		attachSortable() {
			// Raw SortableJS rather than vuedraggable@4 (the latter's slot-VNode mutation
			// loops on @vue/compat MODE:2). Sortable moves the DOM; the store action
			// updates the array, and v-for diffs to a no-op since the DOM already matches.
			this._sortable = Sortable.create(this.$refs.sortableRoot, {
				handle: '.mc__handle',
				animation: 180,
				forceFallback: true,
				fallbackTolerance: 3,
				scrollSensitivity: 80,
				ghostClass: 'mc__item--ghost',
				chosenClass: 'mc__item--chosen',
				dragClass: 'mc__item--drag',
				onEnd: this.onSortEnd,
			})
		},

		onSortEnd(evt) {
			if (evt.oldIndex === evt.newIndex) return
			// Derive the new id order from the DOM (Sortable already reordered it).
			const orderedIds = Array.from(this.$refs.sortableRoot.children)
				.map((el) => Number(el.dataset.id))
				.filter((id) => !Number.isNaN(id))
			this.catStore.reorder(this.listId, orderedIds)
		},
	},
}
</script>

<style scoped>
.mc__body {
	display: flex;
	flex-direction: column;
	gap: 12px;
	min-height: 200px;
}
.mc__list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 8px;
	max-height: 50vh;
	overflow-y: auto;
}
.mc__item,
.mc__add {
	display: flex;
	align-items: center;
	gap: 8px;
}
.mc__add {
	padding-top: 12px;
	border-top: 1px solid var(--color-border-dark);
	padding-left: 32px; /* align with item content (which has a 32px wide handle column) */
}
.mc__handle {
	background: transparent;
	border: none;
	color: var(--color-text-lighter);
	cursor: grab;
	padding: 6px;
	border-radius: var(--border-radius);
	display: flex;
	align-items: center;
	justify-content: center;
	touch-action: none;
	flex-shrink: 0;
}
.mc__handle:hover {
	background: var(--color-background-hover);
	color: var(--color-main-text);
}
.mc__handle:active {
	cursor: grabbing;
}
/* SortableJS state classes */
.mc__item--ghost {
	opacity: 0.35;
	background: var(--color-primary-light, rgba(0, 130, 201, 0.12));
}
.mc__item--chosen {
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
}
.mc__item--drag {
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
	background: var(--color-main-background);
	border-radius: var(--border-radius);
}
.mc__icon-display {
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	cursor: pointer;
	width: 40px;
	height: 40px;
	font-size: 1.4em;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	line-height: 1;
}
.mc__icon-display:hover {
	background: var(--color-background-hover);
}
.mc__icon-placeholder {
	opacity: 0.35;
}
.mc__name-input {
	flex: 1;
	padding: 8px 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 16px;
	box-sizing: border-box;
}
.mc__name-input:focus {
	outline: none;
	border-color: var(--color-primary);
}
.mc__delete {
	background: none;
	border: none;
	cursor: pointer;
	color: var(--color-text-lighter);
	padding: 4px 8px;
	border-radius: var(--border-radius);
	font-size: 1.1em;
}
.mc__delete:hover {
	background: var(--color-background-hover);
	color: var(--color-error);
}
.mc__empty {
	color: var(--color-text-lighter);
	font-size: 0.9em;
	margin: 0;
}
</style>
