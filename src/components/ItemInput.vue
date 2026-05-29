<template>
	<div class="item-input" @keydown.esc="closeSuggestions">
		<div class="item-input__row">
			<!-- Quantity badge (left of title, only when list has quantities) -->
			<div v-if="hasQuantities" class="item-input__qty-wrap" @click.stop>
				<button
					type="button"
					class="item-input__qty-badge"
					:title="t('lists', 'Change quantity')"
					@click="qtyPickerOpen = !qtyPickerOpen">
					×{{ quantity }}
				</button>
				<QuantityPicker
					v-if="qtyPickerOpen"
					:value="quantity"
					@apply="onQtyApply"
					@close="qtyPickerOpen = false" />
			</div>

			<input
				ref="inputEl"
				v-model="title"
				class="item-input__field"
				type="text"
				:placeholder="t('lists', 'Add an item…')"
				autocomplete="off"
				maxlength="255"
				@input="onInput"
				@keydown.enter.prevent="onEnter"
				@keydown.arrow-down.prevent="moveFocus(1)"
				@keydown.arrow-up.prevent="moveFocus(-1)"
				@blur="onBlur" />

			<span v-if="defaultCategoryId && activeCategoryName" class="item-input__cat-hint">
				{{ activeCategoryName }}
			</span>

			<button
				class="item-input__btn"
				type="button"
				:disabled="!title.trim()"
				:aria-label="t('lists', 'Add')"
				:title="t('lists', 'Add')"
				@click="submit">
				<span class="item-input__btn-text">{{ t('lists', 'Add') }}</span>
				<span class="item-input__btn-icon" aria-hidden="true">+</span>
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
				<span v-if="hasQuantities" class="item-input__suggestion-qty">×{{ item.quantity ?? 1 }}</span>
				<span class="item-input__suggestion-hint">{{ suggestionHint(item) }}</span>
			</li>
		</ul>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { itemsApi } from '../services/api.js'
import QuantityPicker from './QuantityPicker.vue'

export default {
	name: 'ItemInput',

	components: { QuantityPicker },

	props: {
		listId: { type: Number, required: true },
		hasQuantities: { type: Boolean, default: false },
		categories: { type: Array, default: () => [] },
		defaultCategoryId: { type: Number, default: null },
	},

	emits: ['add', 'select-suggestion'],

	data() {
		return {
			title: '',
			quantity: 1,
			quantityTouched: false,
			qtyPickerOpen: false,
			suggestions: [],
			focusedIdx: -1,
			debounceTimer: null,
		}
	},

	computed: {
		activeCategoryName() {
			if (!this.defaultCategoryId) return null
			const cat = this.categories.find((c) => c.id === this.defaultCategoryId)
			return cat ? cat.name : null
		},
	},

	methods: {
		t,

		onInput() {
			clearTimeout(this.debounceTimer)
			this.focusedIdx = -1
			if (this.title.trim().length < 1) {
				this.suggestions = []
				return
			}
			this.debounceTimer = setTimeout(() => this.fetchSuggestions(), 150)
		},

		// Adaptive hint shown to the right of a suggestion
		suggestionHint(item) {
			const willTransfer = this.hasQuantities && this.quantityTouched
			const current = item.quantity ?? 1
			if (item.checked) {
				if (willTransfer && this.quantity !== current) {
					return t('lists', 'will uncheck · qty → {q}', { q: this.quantity })
				}
				return t('lists', 'will uncheck')
			}
			// Already in the active list
			if (willTransfer && this.quantity !== current) {
				return t('lists', 'in list · qty → {q}', { q: this.quantity })
			}
			return t('lists', 'already in list')
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

		onQtyApply(qty) {
			this.quantity = qty
			this.quantityTouched = true
			this.qtyPickerOpen = false
		},

		onBlur() {
			setTimeout(() => { this.closeSuggestions() }, 150)
		},

		closeSuggestions() {
			this.suggestions = []
			this.focusedIdx = -1
		},

		async selectSuggestion(item) {
			// Only transfer the typed quantity if the user explicitly set one
			const quantity = (this.hasQuantities && this.quantityTouched) ? this.quantity : null
			this.title = ''
			this.quantity = 1
			this.quantityTouched = false
			this.qtyPickerOpen = false
			this.closeSuggestions()
			this.$emit('select-suggestion', { item, quantity })
			this.$refs.inputEl?.focus()
		},

		submit() {
			const trimmed = this.title.trim()
			if (!trimmed) return
			this.$emit('add', { title: trimmed, quantity: this.hasQuantities ? this.quantity : null })
			this.title = ''
			this.quantity = 1
			this.quantityTouched = false
			this.qtyPickerOpen = false
			this.closeSuggestions()
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
	align-items: stretch;
}

/* ── Quantity badge in the add row ── */
.item-input__qty-wrap {
	position: relative;
	flex-shrink: 0;
	display: flex;
}
.item-input__qty-badge {
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	cursor: pointer;
	color: var(--color-primary);
	font-weight: 700;
	font-size: 0.95em;
	line-height: 1;
	padding: 0 12px;
	min-width: 48px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	transition: background 0.12s;
	-webkit-tap-highlight-color: transparent;
}
.item-input__qty-badge:hover,
.item-input__qty-badge:active {
	background: var(--color-primary);
	color: var(--color-primary-text);
	border-color: var(--color-primary);
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
	white-space: nowrap;
	display: inline-flex;
	align-items: center;
	justify-content: center;
}
.item-input__btn-icon {
	display: none;
	font-size: 1.4em;
	line-height: 1;
}
/* Mobile: compact "+" button */
@media (max-width: 767px) {
	.item-input__btn {
		padding: 0;
		width: 44px;
		flex-shrink: 0;
	}
	.item-input__btn-text {
		display: none;
	}
	.item-input__btn-icon {
		display: inline;
	}
}
.item-input__btn:disabled {
	opacity: 0.5;
	cursor: default;
}
.item-input__cat-hint {
	display: flex;
	align-items: center;
	font-size: 0.8em;
	color: var(--color-primary);
	background: var(--color-primary-light, #e8f4ff);
	padding: 2px 8px;
	border-radius: 12px;
	white-space: nowrap;
	flex-shrink: 0;
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
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.item-input__suggestion--checked .item-input__suggestion-title {
	text-decoration: line-through;
	color: var(--color-text-lighter);
}
.item-input__suggestion-qty {
	font-size: 0.85em;
	font-weight: 700;
	color: var(--color-primary);
	flex-shrink: 0;
}
.item-input__suggestion-hint {
	font-size: 0.75em;
	color: var(--color-text-lighter);
	white-space: nowrap;
	flex-shrink: 0;
}
</style>
