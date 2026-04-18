<template>
	<div class="item-input" @keydown.esc="closeSuggestions">
		<div class="item-input__row">
			<input
				ref="inputEl"
				v-model="title"
				class="item-input__field"
				type="text"
				:placeholder="t('lists', 'Add an item…')"
				autocomplete="off"
				@input="onInput"
				@keydown.enter.prevent="onEnter"
				@keydown.arrow-down.prevent="moveFocus(1)"
				@keydown.arrow-up.prevent="moveFocus(-1)"
				@blur="onBlur"
			/>
			<button
				class="item-input__btn"
				type="button"
				:disabled="!title.trim()"
				@click="submit">
				{{ t('lists', 'Add') }}
			</button>
		</div>

		<ul v-if="suggestions.length" class="item-input__suggestions">
			<li
				v-for="(item, idx) in suggestions"
				:key="item.id"
				class="item-input__suggestion"
				:class="{
					'item-input__suggestion--focused': idx === focusedIdx,
					'item-input__suggestion--checked': item.checked,
				}"
				@mousedown.prevent="selectSuggestion(item)">
				<span class="item-input__suggestion-icon">{{ item.checked ? '✓' : '○' }}</span>
				<span class="item-input__suggestion-title">{{ item.title }}</span>
				<span v-if="item.checked" class="item-input__suggestion-hint">
					{{ t('lists', 'already checked — will uncheck') }}
				</span>
			</li>
		</ul>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { itemsApi } from '../services/api.js'

export default {
	name: 'ItemInput',

	props: {
		listId: { type: Number, required: true },
	},

	emits: ['add', 'select-suggestion'],

	data() {
		return {
			title: '',
			suggestions: [],
			focusedIdx: -1,
			debounceTimer: null,
		}
	},

	methods: {
		t,

		onInput() {
			clearTimeout(this.debounceTimer)
			this.focusedIdx = -1

			if (this.title.trim().length < 2) {
				this.suggestions = []
				return
			}

			this.debounceTimer = setTimeout(() => this.fetchSuggestions(), 150)
		},

		async fetchSuggestions() {
			try {
				this.suggestions = await itemsApi.suggest(this.listId, this.title.trim())
			} catch {
				this.suggestions = []
			}
		},

		moveFocus(dir) {
			if (!this.suggestions.length) return
			const max = this.suggestions.length - 1
			this.focusedIdx = Math.max(-1, Math.min(max, this.focusedIdx + dir))
		},

		onEnter() {
			if (this.focusedIdx >= 0 && this.suggestions[this.focusedIdx]) {
				this.selectSuggestion(this.suggestions[this.focusedIdx])
			} else {
				this.submit()
			}
		},

		onBlur() {
			// Delay so mousedown on a suggestion fires first
			setTimeout(() => { this.closeSuggestions() }, 150)
		},

		closeSuggestions() {
			this.suggestions = []
			this.focusedIdx = -1
		},

		async selectSuggestion(item) {
			this.title = ''
			this.closeSuggestions()
			this.$emit('select-suggestion', item)
			this.$refs.inputEl?.focus()
		},

		submit() {
			const trimmed = this.title.trim()
			if (!trimmed) return
			this.title = ''
			this.closeSuggestions()
			this.$emit('add', trimmed)
		},
	},
}
</script>

<style scoped>
.item-input {
	position: relative;
	margin-bottom: 8px;
}
.item-input__row {
	display: flex;
	gap: 8px;
}
.item-input__field {
	flex: 1;
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
}
.item-input__btn {
	padding: 8px 16px;
	background: var(--color-primary);
	color: var(--color-primary-text);
	border: none;
	border-radius: var(--border-radius);
	cursor: pointer;
}
.item-input__btn:disabled {
	opacity: 0.5;
	cursor: default;
}
.item-input__suggestions {
	position: absolute;
	top: calc(100% + 2px);
	left: 0;
	right: 0;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
	z-index: 500;
	list-style: none;
	margin: 0;
	padding: 4px 0;
}
.item-input__suggestion {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 12px;
	cursor: pointer;
}
.item-input__suggestion:hover,
.item-input__suggestion--focused {
	background: var(--color-background-hover);
}
.item-input__suggestion-icon {
	color: var(--color-text-lighter);
	font-size: 0.9em;
	width: 16px;
	text-align: center;
	flex-shrink: 0;
}
.item-input__suggestion-title {
	flex: 1;
}
.item-input__suggestion--checked .item-input__suggestion-title {
	text-decoration: line-through;
	color: var(--color-text-lighter);
}
.item-input__suggestion-hint {
	font-size: 0.75em;
	color: var(--color-text-lighter);
	white-space: nowrap;
}
</style>
