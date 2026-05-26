<template>
	<NcDialog
		:name="title"
		size="small"
		close-on-click-outside
		@closing="$emit('close')">
		<form class="lf__form" @submit.prevent="onSubmit">
			<label class="lf__label" for="lf-name">{{ t('lists', 'Name') }}</label>
			<input
				id="lf-name"
				v-model="form.name"
				class="lf__input"
				type="text"
				maxlength="255"
				autofocus
				:placeholder="t('lists', 'List name')" />

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

			<button type="submit" class="lf__hidden-submit" tabindex="-1" aria-hidden="true" />
		</form>

		<template #actions>
			<NcButton type="secondary" @click="$emit('close')">
				{{ t('lists', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" :disabled="!form.name.trim()" @click="onSubmit">
				{{ list ? t('lists', 'Save') : t('lists', 'Create') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'

export default {
	name: 'ListFormModal',

	components: { NcDialog, NcButton },

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

	methods: {
		t,

		onSubmit() {
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
.lf__form {
	display: flex;
	flex-direction: column;
	gap: 4px;
	padding: 4px 0;
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
	padding: 10px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 16px;
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
.lf__hidden-submit {
	position: absolute;
	left: -9999px;
	width: 1px;
	height: 1px;
}
</style>
