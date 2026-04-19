<template>
	<div class="reorder-modal__backdrop" @mousedown.self="$emit('close')">
		<div class="reorder-modal__box" role="dialog" aria-modal="true">
			<h3 class="reorder-modal__title">{{ t('lists', 'Reorder lists') }}</h3>

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

			<div class="reorder-modal__footer">
				<button class="reorder-modal__cancel" @click="$emit('close')">
					{{ t('lists', 'Cancel') }}
				</button>
				<button class="reorder-modal__save" @click="save">
					{{ t('lists', 'Save') }}
				</button>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ReorderModal',

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

	mounted() {
		document.addEventListener('keydown', this.onKey)
	},

	beforeUnmount() {
		document.removeEventListener('keydown', this.onKey)
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

		onKey(e) {
			if (e.key === 'Escape') this.$emit('close')
		},
	},
}
</script>

<style scoped>
.reorder-modal__backdrop {
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.45);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9000;
}
.reorder-modal__box {
	background: var(--color-main-background);
	border-radius: var(--border-radius-large, 8px);
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.24);
	padding: 24px 28px;
	width: min(480px, 92vw);
	max-height: 80vh;
	display: flex;
	flex-direction: column;
	gap: 16px;
}
.reorder-modal__title {
	margin: 0;
	font-size: 1.1em;
	font-weight: 600;
}
.reorder-modal__list {
	list-style: none;
	margin: 0;
	padding: 0;
	overflow-y: auto;
	flex: 1;
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
.reorder-modal__footer {
	display: flex;
	justify-content: flex-end;
	gap: 10px;
}
.reorder-modal__cancel {
	padding: 8px 18px;
	border-radius: var(--border-radius);
	border: 1px solid var(--color-border);
	background: var(--color-background-dark);
	color: var(--color-main-text);
	cursor: pointer;
}
.reorder-modal__cancel:hover {
	background: var(--color-background-hover);
}
.reorder-modal__save {
	padding: 8px 18px;
	border-radius: var(--border-radius);
	border: 1px solid var(--color-primary);
	background: var(--color-primary);
	color: var(--color-primary-text);
	cursor: pointer;
}
.reorder-modal__save:hover {
	filter: brightness(1.1);
}
</style>
