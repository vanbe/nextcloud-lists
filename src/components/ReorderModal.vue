<template>
	<NcDialog
		:name="t('lists', 'Reorder lists')"
		size="small"
		@closing="$emit('close')">
		<p class="reorder-modal__hint">
			{{ t('lists', 'Drag the handle to reorder. Changes are saved automatically.') }}
		</p>

		<ul ref="sortableRoot" class="reorder-modal__list">
			<li
				v-for="list in ordered"
				:key="list.id"
				class="reorder-modal__item"
				:data-id="list.id">
				<button
					class="reorder-modal__handle"
					type="button"
					:aria-label="t('lists', 'Drag to reorder')"
					:title="t('lists', 'Drag to reorder')">
					<DragVertical :size="20" />
				</button>
				<span class="reorder-modal__name">{{ list.name }}</span>
			</li>
		</ul>

		<p v-if="!ordered.length" class="reorder-modal__empty">
			{{ t('lists', 'You have no lists to reorder.') }}
		</p>

		<template #actions>
			<NcButton type="primary" @click="$emit('close')">
				{{ t('lists', 'Done') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import Sortable from 'sortablejs'
import DragVertical from 'vue-material-design-icons/DragVertical.vue'

export default {
	name: 'ReorderModal',

	components: { NcDialog, NcButton, DragVertical },

	props: {
		lists: { type: Array, required: true },
	},

	emits: ['save', 'close'],

	data() {
		return {
			ordered: [...this.lists],
		}
	},

	mounted() {
		// Raw SortableJS rather than vuedraggable@4: the latter's render mutates the
		// slot's VNodes (key + props), which doesn't survive the Vue-2 VNode shape
		// emitted by @vue/compat MODE:2 → re-render loop. Sortable just moves the DOM;
		// we sync `ordered` to the new order in onEnd, which keeps Vue in sync for the
		// next render.
		this._sortable = Sortable.create(this.$refs.sortableRoot, {
			handle: '.reorder-modal__handle',
			animation: 180,
			forceFallback: true,
			fallbackTolerance: 3,
			scrollSensitivity: 80,
			ghostClass: 'reorder-modal__item--ghost',
			chosenClass: 'reorder-modal__item--chosen',
			dragClass: 'reorder-modal__item--drag',
			onEnd: this.onSortEnd,
		})
	},

	beforeUnmount() {
		this._sortable?.destroy()
		this._sortable = null
	},

	methods: {
		t,

		onSortEnd(evt) {
			if (evt.oldIndex === evt.newIndex) return
			// Sortable moved the DOM; mirror that in the Vue data array so the next
			// render aligns with the DOM (otherwise Vue would re-insert in old order).
			const moved = this.ordered.splice(evt.oldIndex, 1)[0]
			this.ordered.splice(evt.newIndex, 0, moved)
			this.$emit('save', this.ordered.map((l) => l.id))
		},
	},
}
</script>

<style scoped>
.reorder-modal__hint {
	margin: 0 0 12px;
	font-size: 0.9em;
	color: var(--color-text-lighter);
}
.reorder-modal__list {
	list-style: none;
	margin: 0;
	padding: 0;
	overflow-y: auto;
	max-height: 60vh;
}
.reorder-modal__item {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 8px 6px;
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	transition: background 0.15s;
}
.reorder-modal__item + .reorder-modal__item {
	border-top: 1px solid var(--color-border);
}
.reorder-modal__handle {
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
}
.reorder-modal__handle:hover {
	background: var(--color-background-hover);
	color: var(--color-main-text);
}
.reorder-modal__handle:active {
	cursor: grabbing;
}
.reorder-modal__name {
	flex: 1;
	font-size: 1em;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

/* SortableJS state classes */
.reorder-modal__item--ghost {
	opacity: 0.35;
	background: var(--color-primary-light, rgba(0, 130, 201, 0.12));
}
.reorder-modal__item--chosen {
	background: var(--color-background-hover);
}
.reorder-modal__item--drag {
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
	background: var(--color-main-background);
	border-radius: var(--border-radius);
}
.reorder-modal__empty {
	margin: 8px 0 0;
	padding: 12px;
	text-align: center;
	color: var(--color-text-lighter);
	font-style: italic;
}
</style>
