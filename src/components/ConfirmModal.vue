<template>
	<div class="confirm-modal__backdrop" @mousedown.self="$emit('cancel')">
		<div class="confirm-modal__box" role="dialog" aria-modal="true">
			<p class="confirm-modal__message">{{ message }}</p>
			<div class="confirm-modal__actions">
				<button class="confirm-modal__btn confirm-modal__btn--cancel" @click="$emit('cancel')">
					{{ t('lists', 'Cancel') }}
				</button>
				<button class="confirm-modal__btn confirm-modal__btn--confirm" @click="$emit('confirm')">
					{{ confirmLabel }}
				</button>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ConfirmModal',

	props: {
		message: { type: String, required: true },
		confirmLabel: { type: String, default: 'OK' },
	},

	emits: ['confirm', 'cancel'],

	mounted() {
		document.addEventListener('keydown', this.onKey)
	},

	beforeUnmount() {
		document.removeEventListener('keydown', this.onKey)
	},

	methods: {
		t,
		onKey(e) {
			if (e.key === 'Escape') this.$emit('cancel')
			if (e.key === 'Enter') this.$emit('confirm')
		},
	},
}
</script>

<style scoped>
.confirm-modal__backdrop {
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.45);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9000;
}
.confirm-modal__box {
	background: var(--color-main-background);
	border-radius: var(--border-radius-large, 8px);
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.24);
	padding: 28px 32px 24px;
	max-width: 420px;
	width: 90vw;
}
.confirm-modal__message {
	margin: 0 0 24px;
	font-size: 1em;
	line-height: 1.5;
	color: var(--color-main-text);
}
.confirm-modal__actions {
	display: flex;
	gap: 10px;
	justify-content: flex-end;
}
.confirm-modal__btn {
	padding: 8px 18px;
	border-radius: var(--border-radius);
	border: 1px solid var(--color-border);
	cursor: pointer;
	font-size: 0.95em;
}
.confirm-modal__btn--cancel {
	background: var(--color-background-dark);
	color: var(--color-main-text);
}
.confirm-modal__btn--cancel:hover {
	background: var(--color-background-hover);
}
.confirm-modal__btn--confirm {
	background: var(--color-primary);
	color: var(--color-primary-text);
	border-color: var(--color-primary);
}
.confirm-modal__btn--confirm:hover {
	filter: brightness(1.1);
}
</style>
