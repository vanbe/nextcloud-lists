<template>
	<div class="ugs" @keydown.esc="close">
		<div class="ugs__row">
			<div class="ugs__type-toggle">
				<button
					class="ugs__type-btn"
					:class="{ 'ugs__type-btn--active': type === 0 }"
					type="button"
					@click="setType(0)">
					{{ t('lists', 'User') }}
				</button>
				<button
					class="ugs__type-btn"
					:class="{ 'ugs__type-btn--active': type === 1 }"
					type="button"
					@click="setType(1)">
					{{ t('lists', 'Group') }}
				</button>
			</div>
			<input
				ref="inputEl"
				v-model="query"
				class="ugs__input"
				type="text"
				:placeholder="type === 0 ? t('lists', 'Search user…') : t('lists', 'Search group…')"
				autocomplete="off"
				@input="onInput"
				@keydown.arrow-down.prevent="moveFocus(1)"
				@keydown.arrow-up.prevent="moveFocus(-1)"
				@keydown.enter.prevent="confirmFocused"
				@blur="onBlur"
			/>
		</div>

		<ul v-if="results.length" class="ugs__dropdown">
			<li
				v-for="(r, idx) in results"
				:key="r.uid || r.gid"
				class="ugs__result"
				:class="{ 'ugs__result--focused': idx === focusedIdx }"
				@mousedown.prevent="select(r)">
				<span class="ugs__result-name">{{ r.displayName }}</span>
				<span class="ugs__result-id">{{ r.uid || r.gid }}</span>
			</li>
		</ul>
		<p v-else-if="searched && !loading" class="ugs__empty">
			{{ t('lists', 'No results.') }}
		</p>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { usersApi } from '../services/api.js'

export default {
	name: 'UserGroupSearch',

	emits: ['select'],

	data() {
		return {
			type: 0,
			query: '',
			results: [],
			focusedIdx: -1,
			loading: false,
			searched: false,
			debounceTimer: null,
		}
	},

	methods: {
		t,

		setType(t) {
			this.type = t
			this.results = []
			this.searched = false
			this.query = ''
			this.$refs.inputEl?.focus()
		},

		onInput() {
			clearTimeout(this.debounceTimer)
			this.focusedIdx = -1
			this.searched = false
			if (this.query.trim().length < 2) {
				this.results = []
				return
			}
			this.debounceTimer = setTimeout(() => this.search(), 150)
		},

		async search() {
			this.loading = true
			try {
				if (this.type === 0) {
					this.results = await usersApi.searchUsers(this.query.trim())
				} else {
					this.results = await usersApi.searchGroups(this.query.trim())
				}
				this.searched = true
			} catch {
				this.results = []
			} finally {
				this.loading = false
			}
		},

		moveFocus(dir) {
			if (!this.results.length) return
			const max = this.results.length - 1
			this.focusedIdx = Math.max(0, Math.min(max, this.focusedIdx + dir))
		},

		confirmFocused() {
			if (this.focusedIdx >= 0 && this.results[this.focusedIdx]) {
				this.select(this.results[this.focusedIdx])
			}
		},

		onBlur() {
			setTimeout(() => this.close(), 150)
		},

		close() {
			this.results = []
			this.focusedIdx = -1
		},

		select(result) {
			const id = result.uid || result.gid
			this.$emit('select', { type: this.type, id, displayName: result.displayName })
			this.query = ''
			this.results = []
			this.searched = false
		},
	},
}
</script>

<style scoped>
.ugs {
	position: relative;
}
.ugs__row {
	display: flex;
	gap: 6px;
	align-items: center;
}
.ugs__type-toggle {
	display: flex;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	overflow: hidden;
	flex-shrink: 0;
}
.ugs__type-btn {
	background: none;
	border: none;
	padding: 7px 10px;
	cursor: pointer;
	font-size: 0.85em;
	color: var(--color-text-lighter);
}
.ugs__type-btn--active {
	background: var(--color-primary);
	color: var(--color-primary-text);
}
.ugs__input {
	flex: 1;
	padding: 7px 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
}
.ugs__dropdown {
	position: absolute;
	top: calc(100% + 2px);
	left: 0;
	right: 0;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
	z-index: 600;
	list-style: none;
	margin: 0;
	padding: 4px 0;
}
.ugs__result {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 12px;
	cursor: pointer;
}
.ugs__result:hover,
.ugs__result--focused {
	background: var(--color-background-hover);
}
.ugs__result-name {
	font-weight: 500;
	flex: 1;
}
.ugs__result-id {
	font-size: 0.8em;
	color: var(--color-text-lighter);
}
.ugs__empty {
	font-size: 0.85em;
	color: var(--color-text-lighter);
	padding: 6px 0 0;
}
</style>
