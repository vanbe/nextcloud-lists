<template>
	<div class="qty-picker" @click.stop>
		<!-- Backdrop (mobile bottom-sheet only) -->
		<div class="qty-picker__backdrop" @click="$emit('close')"></div>

		<div
			class="qty-picker__panel"
			:style="panelStyle"
			@touchstart.stop="onDragStart"
			@touchmove.stop="onDragMove"
			@touchend.stop="onDragEnd"
			@touchcancel.stop="onDragEnd">
			<div class="qty-picker__handle"></div>
			<div class="qty-picker__title">{{ t('lists', 'Quantity') }}</div>

			<div class="qty-picker__chips">
				<button
					v-for="n in presets"
					:key="n"
					type="button"
					class="qty-picker__chip"
					:class="{ 'qty-picker__chip--active': n === currentValue }"
					@click="apply(n)">
					{{ n }}
				</button>
			</div>

			<div class="qty-picker__custom">
				<button
					type="button"
					class="qty-picker__step"
					:disabled="draft <= 1"
					:aria-label="t('lists', 'Decrease')"
					@click="draft = Math.max(1, draft - 1)">−</button>
				<input
					ref="inputEl"
					v-model.number="draft"
					class="qty-picker__input"
					type="number"
					inputmode="numeric"
					min="1"
					max="999"
					@keydown.enter.prevent="apply(draft)"
					@keydown.escape.prevent="$emit('close')" />
				<button
					type="button"
					class="qty-picker__step"
					:aria-label="t('lists', 'Increase')"
					@click="draft = draft + 1">+</button>
				<button
					type="button"
					class="qty-picker__ok"
					:disabled="!Number.isFinite(draft) || draft < 1"
					@click="apply(draft)">
					OK
				</button>
			</div>
		</div>
	</div>
</template>

<script>
import { nextTick } from 'vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'QuantityPicker',

	props: {
		value: { type: Number, default: 1 },
	},

	emits: ['apply', 'close'],

	data() {
		return {
			presets: [1, 2, 3, 5, 10],
			draft: this.value || 1,
			// Swipe-to-dismiss (bottom sheet on mobile)
			dragStartY: null,
			dragY: 0,
		}
	},

	computed: {
		currentValue() {
			return this.value || 1
		},

		panelStyle() {
			if (this.dragStartY !== null && this.dragY > 0) {
				return { transform: `translateY(${this.dragY}px)`, transition: 'none' }
			}
			return {}
		},
	},

	mounted() {
		// Only autofocus the field on desktop — on mobile we don't want the
		// numeric keyboard to immediately cover the chips. The user can tap
		// the field if they want free entry.
		if (window.matchMedia('(min-width: 768px)').matches) {
			nextTick(() => {
				const el = this.$refs.inputEl
				if (el) {
					el.focus()
					el.select?.()
				}
			})
		}
	},

	methods: {
		t,

		apply(n) {
			const v = Math.max(1, Math.floor(Number(n) || 1))
			this.$emit('apply', v)
		},

		// ── Swipe-to-dismiss ──────────────────────────────────────────────
		onDragStart(e) {
			// Ignore drags that start on interactive controls (let them work normally)
			if (e.target?.closest?.('input, button')) {
				this.dragStartY = null
				return
			}
			if (e.touches.length !== 1) return
			this.dragStartY = e.touches[0].clientY
			this.dragY = 0
		},

		onDragMove(e) {
			if (this.dragStartY === null) return
			const dy = e.touches[0].clientY - this.dragStartY
			// Only track downward drags
			this.dragY = Math.max(0, dy)
		},

		onDragEnd() {
			if (this.dragStartY === null) return
			const shouldClose = this.dragY > 80
			this.dragStartY = null
			this.dragY = 0
			if (shouldClose) this.$emit('close')
		},
	},
}
</script>

<style scoped>
/* ──────────────────────────────────────────────────────────────────────
   Desktop (default): anchored popover. The parent element must be
   position: relative for this to anchor to the badge.
   ────────────────────────────────────────────────────────────────────── */
.qty-picker__backdrop {
	display: none;
}
.qty-picker__panel {
	position: absolute;
	z-index: 700;
	top: calc(100% + 4px);
	left: 0;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-shadow: 0 6px 24px rgba(0, 0, 0, 0.18);
	padding: 8px;
	display: flex;
	flex-direction: column;
	gap: 8px;
	min-width: 220px;
}
.qty-picker__handle,
.qty-picker__title {
	display: none;
}
.qty-picker__chips {
	display: flex;
	gap: 4px;
	flex-wrap: wrap;
}
.qty-picker__chip {
	flex: 1 1 auto;
	min-width: 36px;
	padding: 8px 10px;
	border: 1px solid var(--color-border);
	border-radius: 14px;
	background: var(--color-background-dark);
	color: var(--color-main-text);
	font-weight: 600;
	cursor: pointer;
	-webkit-tap-highlight-color: transparent;
}
.qty-picker__chip:hover {
	background: var(--color-background-hover);
}
.qty-picker__chip--active {
	background: var(--color-primary);
	color: var(--color-primary-text);
	border-color: var(--color-primary);
}
.qty-picker__custom {
	display: flex;
	gap: 4px;
	align-items: stretch;
}
.qty-picker__step {
	width: 36px;
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	cursor: pointer;
	font-size: 1.1em;
	color: var(--color-main-text);
}
.qty-picker__step:disabled {
	opacity: 0.4;
	cursor: default;
}
.qty-picker__input {
	flex: 1;
	min-width: 50px;
	padding: 6px 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	text-align: center;
	font-weight: 600;
	font-size: 1em;
}
.qty-picker__input::-webkit-outer-spin-button,
.qty-picker__input::-webkit-inner-spin-button {
	-webkit-appearance: none;
	margin: 0;
}
.qty-picker__input[type=number] {
	-moz-appearance: textfield;
}
.qty-picker__ok {
	padding: 6px 14px;
	background: var(--color-primary);
	color: var(--color-primary-text);
	border: none;
	border-radius: var(--border-radius);
	cursor: pointer;
	font-weight: 600;
}
.qty-picker__ok:disabled {
	opacity: 0.5;
	cursor: default;
}

/* ──────────────────────────────────────────────────────────────────────
   Mobile (< 768px): native-feeling bottom sheet, full width.
   No overflow problems, sits next to the numeric keyboard.
   ────────────────────────────────────────────────────────────────────── */
@media (max-width: 767px) {
	.qty-picker__backdrop {
		display: block;
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, 0.4);
		z-index: 10000;
		animation: qty-fade 0.15s ease-out;
	}
	.qty-picker__panel {
		position: fixed;
		z-index: 10001;
		top: auto;
		left: 0;
		right: 0;
		bottom: 0;
		min-width: 0;
		border: none;
		border-radius: 18px 18px 0 0;
		padding: 8px 16px calc(16px + env(safe-area-inset-bottom, 0px));
		gap: 14px;
		box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.25);
		animation: qty-slide-up 0.2s ease-out;
		transition: transform 0.2s ease-out;
		touch-action: none;
	}
	.qty-picker__handle {
		display: block;
		width: 40px;
		height: 4px;
		border-radius: 2px;
		background: var(--color-border-dark);
		margin: 4px auto 0;
	}
	.qty-picker__title {
		display: block;
		text-align: center;
		font-weight: 700;
		font-size: 0.9em;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--color-text-lighter);
	}
	.qty-picker__chips {
		gap: 8px;
	}
	.qty-picker__chip {
		min-height: 48px;
		font-size: 1.15em;
		border-radius: 12px;
	}
	.qty-picker__custom {
		gap: 8px;
	}
	.qty-picker__step {
		width: 52px;
		min-height: 48px;
		font-size: 1.4em;
	}
	.qty-picker__input {
		min-height: 48px;
		font-size: 1.2em;
	}
	.qty-picker__ok {
		padding: 0 22px;
		min-height: 48px;
		font-size: 1.05em;
	}
}

@keyframes qty-slide-up {
	from { transform: translateY(100%); }
	to { transform: translateY(0); }
}
@keyframes qty-fade {
	from { opacity: 0; }
	to { opacity: 1; }
}
</style>
