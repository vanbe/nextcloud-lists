<template>
	<NcDialog
		:name="t('lists', 'Duplicate list')"
		size="small"
		close-on-click-outside
		@closing="$emit('close')">
		<form class="dup__form" @submit.prevent="onSubmit">
			<p class="dup__hint">
				{{ t('lists', 'A copy of "{name}" will be created, with all its items, categories and shares.', { name: list.name }) }}
			</p>

			<label class="dup__label" for="dup-name">{{ t('lists', 'Name') }}</label>
			<input
				id="dup-name"
				v-model="form.name"
				class="dup__input"
				type="text"
				maxlength="255"
				autofocus
				:placeholder="t('lists', 'List name')" />

			<label class="dup__label" for="dup-desc">{{ t('lists', 'Description') }}</label>
			<textarea
				id="dup-desc"
				v-model="form.description"
				class="dup__textarea"
				rows="3"
				:placeholder="t('lists', 'Optional description…')"
				maxlength="1000" />

			<button type="submit" class="dup__hidden-submit" tabindex="-1" aria-hidden="true" />
		</form>

		<template #actions>
			<NcButton type="secondary" @click="$emit('close')">
				{{ t('lists', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" :disabled="!form.name.trim()" @click="onSubmit">
				{{ t('lists', 'Duplicate') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'

export default {
	name: 'DuplicateModal',

	components: { NcDialog, NcButton },

	props: {
		list: { type: Object, required: true },
	},

	emits: ['close', 'submit'],

	data() {
		return {
			form: {
				name: t('lists', '{name} (copy)', { name: this.list.name }),
				description: this.list.description ?? '',
			},
		}
	},

	methods: {
		t,

		onSubmit() {
			const name = this.form.name.trim()
			if (!name) return
			this.$emit('submit', {
				name,
				description: this.form.description.trim() || null,
			})
		},
	},
}
</script>

<style scoped>
.dup__form {
	display: flex;
	flex-direction: column;
	gap: 4px;
	padding: 4px 0;
}
.dup__hint {
	margin: 0 0 8px;
	font-size: 0.9em;
	color: var(--color-text-lighter);
}
.dup__label {
	font-size: 0.85em;
	font-weight: 600;
	color: var(--color-text-lighter);
	margin-top: 8px;
}
.dup__input,
.dup__textarea {
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
.dup__input:focus,
.dup__textarea:focus {
	outline: none;
	border-color: var(--color-primary);
	box-shadow: 0 0 0 2px var(--color-primary-light, rgba(0, 130, 201, 0.15));
}
.dup__hidden-submit {
	position: absolute;
	left: -9999px;
	width: 1px;
	height: 1px;
}
</style>
