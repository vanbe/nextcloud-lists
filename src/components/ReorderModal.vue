<template>
	<NcDialog
		:name="t('lists', 'Reorder lists')"
		size="small"
		close-on-click-outside
		@closing="$emit('close')">
		<ul class="reorder-modal__list">
			<li
				v-for="(list, idx) in ordered"
				:key="list.id"
				class="reorder-modal__item"
				:class="{ 'reorder-modal__item--shared': !isOwned(list) }">
				<span class="reorder-modal__name">{{ list.name }}</span>
				<span v-if="!isOwned(list)" class="reorder-modal__shared-badge">
					{{ t('lists', 'Shared') }}
				</span>
				<div v-if="isOwned(list)" class="reorder-modal__btns">
					<button
						class="reorder-modal__btn"
						:disabled="idx === 0 || !isOwned(ordered[idx - 1])"
						:title="t('lists', 'Move up')"
						@click="move(idx, -1)">
						▲
					</button>
					<button
						class="reorder-modal__btn"
						:disabled="idx === ordered.length - 1 || !isOwned(ordered[idx + 1])"
						:title="t('lists', 'Move down')"
						@click="move(idx, +1)">
						▼
					</button>
				</div>
			</li>
		</ul>

		<template #actions>
			<NcButton type="secondary" @click="$emit('close')">
				{{ t('lists', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" @click="save">
				{{ t('lists', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'

export default {
	name: 'ReorderModal',

	components: { NcDialog, NcButton },

	props: {
		lists: { type: Array, required: true },
		currentUser: { type: String, required: true },
	},

	emits: ['save', 'close'],

	data() {
		return {
			ordered: [...this.lists],
		}
	},

	methods: {
		t,

		isOwned(list) {
			return list.uid === this.currentUser
		},

		move(idx, dir) {
			const arr = [...this.ordered]
			const target = idx + dir
			;[arr[idx], arr[target]] = [arr[target], arr[idx]]
			this.ordered = arr
		},

		save() {
			// Send only owned IDs in their new order; backend ignores non-owned
			const ownedIds = this.ordered.filter(this.isOwned).map((l) => l.id)
			this.$emit('save', ownedIds)
		},
	},
}
</script>

<style scoped>
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
	padding: 10px 6px;
	border-bottom: 1px solid var(--color-border-dark);
}
.reorder-modal__item--shared {
	opacity: 0.6;
}
.reorder-modal__name {
	flex: 1;
	font-weight: 500;
	font-size: 1em;
}
.reorder-modal__shared-badge {
	font-size: 0.75em;
	color: var(--color-text-lighter);
	background: var(--color-background-dark);
	border-radius: 10px;
	padding: 1px 7px;
}
.reorder-modal__btns {
	display: flex;
	gap: 4px;
}
.reorder-modal__btn {
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	cursor: pointer;
	width: 32px;
	height: 32px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 0.8em;
	color: var(--color-main-text);
	transition: background 0.12s;
}
.reorder-modal__btn:hover:not(:disabled) {
	background: var(--color-primary);
	color: var(--color-primary-text);
	border-color: var(--color-primary);
}
.reorder-modal__btn:disabled {
	opacity: 0.3;
	cursor: default;
}
</style>
