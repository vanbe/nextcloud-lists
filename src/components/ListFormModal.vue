<template>
	<div class="modal-overlay" @click.self="$emit('close')">
		<div class="modal-card" role="dialog" :aria-label="title">
			<div class="modal-card__header">
				<h2 class="modal-card__title">{{ title }}</h2>
				<button class="modal-card__close" :aria-label="t('lists', 'Close')" @click="$emit('close')">✕</button>
			</div>

			<div class="modal-card__body">
				<label class="lf__label" for="lf-name">{{ t('lists', 'Name') }}</label>
				<input
					id="lf-name"
					ref="nameInput"
					v-model="form.name"
					class="lf__input"
					type="text"
					maxlength="255"
					:placeholder="t('lists', 'List name')"
					@keydown.enter.prevent="submit"
					@keydown.esc.prevent="$emit('close')" />

				<label class="lf__label" for="lf-desc">{{ t('lists', 'Description') }}</label>
				<textarea
					id="lf-desc"
					v-model="form.description"
					class="lf__textarea"
					rows="3"
					:placeholder="t('lists', 'Optional description…')"
					maxlength="1000" />

				<label class="lf__checkbox-row">
					<input v-model="form.hasQuantities" type="checkbox" />
					{{ t('lists', 'Items have quantities') }}
				</label>

				<p v-if="error" class="lf__error">{{ error }}</p>
			</div>

			<div class="modal-card__footer">
				<button class="lf__btn lf__btn--cancel" @click="$emit('close')">{{ t('lists', 'Cancel') }}</button>
				<button class="lf__btn lf__btn--primary" :disabled="!form.name.trim()" @click="submit">
					{{ list ? t('lists', 'Save') : t('lists', 'Create') }}
				</button>
			</div>
		</div>
	</div>
</template>

<script>
import { nextTick } from 'vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ListFormModal',

	props: {
		list: { type: Object, default: null }, // null = create mode
	},

	emits: ['close', 'submit'],

	data() {
		return {
			form: {
				name: this.list?.name ?? '',
				description: this.list?.description ?? '',
				hasQuantities: this.list?.hasQuantities ?? false,
			},
			error: null,
		}
	},

	computed: {
		title() {
			return this.list ? t('lists', 'Edit list') : t('lists', 'New list')
		},
	},

	mounted() {
		document.addEventListener('keydown', this.onKeydown)
		nextTick(() => this.$refs.nameInput?.focus())
	},

	beforeUnmount() {
		document.removeEventListener('keydown', this.onKeydown)
	},

	methods: {
		t,

		onKeydown(e) {
			if (e.key === 'Escape') this.$emit('close')
		},

		submit() {
			const name = this.form.name.trim()
			if (!name) return
			this.$emit('submit', {
				name,
				description: this.form.description.trim() || null,
				hasQuantities: this.form.hasQuantities,
			})
		},
	},
}
</script>

<style scoped>
.modal-overlay {
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 9999;
	display: flex;
	align-items: center;
	justify-content: center;
}
.modal-card {
	background: var(--color-main-background);
	border-radius: var(--border-radius-large);
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
	width: 440px;
	max-width: calc(100vw - 32px);
	display: flex;
	flex-direction: column;
}
.modal-card__header {
	display: flex;
	align-items: center;
	padding: 20px 20px 12px;
	border-bottom: 1px solid var(--color-border-dark);
}
.modal-card__title {
	flex: 1;
	font-size: 1.1em;
	font-weight: bold;
	margin: 0;
}
.modal-card__close {
	background: none;
	border: none;
	cursor: pointer;
	font-size: 1em;
	color: var(--color-text-lighter);
	padding: 4px 8px;
	border-radius: var(--border-radius);
}
.modal-card__close:hover {
	background: var(--color-background-hover);
}
.modal-card__body {
	padding: 16px 20px;
	display: flex;
	flex-direction: column;
	gap: 4px;
}
.modal-card__footer {
	padding: 12px 20px 20px;
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
.lf__label {
	font-size: 0.85em;
	font-weight: 600;
	color: var(--color-text-lighter);
	margin-top: 8px;
}
.lf__input,
.lf__textarea {
	width: 100%;
	padding: 8px 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 0.95em;
	box-sizing: border-box;
	resize: vertical;
}
.lf__input:focus,
.lf__textarea:focus {
	outline: none;
	border-color: var(--color-primary);
	box-shadow: 0 0 0 2px var(--color-primary-light, rgba(0, 130, 201, 0.15));
}
.lf__checkbox-row {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-top: 12px;
	font-size: 0.95em;
	cursor: pointer;
	user-select: none;
}
.lf__checkbox-row input[type="checkbox"] {
	width: 16px;
	height: 16px;
	cursor: pointer;
}
.lf__error {
	color: var(--color-error);
	font-size: 0.9em;
	margin: 8px 0 0;
}
.lf__btn {
	padding: 8px 18px;
	border: none;
	border-radius: var(--border-radius);
	cursor: pointer;
	font-size: 0.95em;
}
.lf__btn--cancel {
	background: var(--color-background-dark);
	color: var(--color-main-text);
}
.lf__btn--cancel:hover {
	background: var(--color-background-hover);
}
.lf__btn--primary {
	background: var(--color-primary);
	color: var(--color-primary-text);
}
.lf__btn--primary:disabled {
	opacity: 0.5;
	cursor: default;
}
</style>
