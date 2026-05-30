<template>
	<NcDialog
		:name="t('lists', 'Reorder lists')"
		size="small"
		close-on-click-outside
		@closing="$emit('close')">
		<p class="reorder-modal__hint">
			{{ t('lists', 'Drag the handle to reorder. Changes are saved automatically.') }}
		</p>

		<draggable
			:list="ordered"
			tag="ul"
			class="reorder-modal__list"
			handle=".reorder-modal__handle"
			item-key="id"
			:animation="180"
			:force-fallback="true"
			:fallback-tolerance="3"
			:scroll-sensitivity="80"
			ghost-class="reorder-modal__item--ghost"
			chosen-class="reorder-modal__item--chosen"
			drag-class="reorder-modal__item--drag"
			@end="onDragEnd">
			<template #item="{ element: list }">
				<li class="reorder-modal__item">
					<button
						class="reorder-modal__handle"
						type="button"
						:aria-label="t('lists', 'Drag to reorder')"
						:title="t('lists', 'Drag to reorder')">
						<DragVertical :size="20" />
					</button>
					<span class="reorder-modal__name">{{ list.name }}</span>
				</li>
			</template>
		</draggable>

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
import draggable from 'vuedraggable'
import DragVertical from 'vue-material-design-icons/DragVertical.vue'

export default {
	name: 'ReorderModal',

	components: { NcDialog, NcButton, draggable, DragVertical },

	props: {
		lists: { type: Array, required: true },
	},

	emits: ['save', 'close'],

	data() {
		return {
			ordered: [...this.lists],
		}
	},

	methods: {
		t,

		onDragEnd(evt) {
			if (evt.oldIndex === evt.newIndex) return
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
