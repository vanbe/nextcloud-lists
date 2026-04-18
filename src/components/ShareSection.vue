<template>
	<div class="share-section">
		<h3 class="share-section__title">{{ t('lists', 'Sharing') }}</h3>

		<form class="share-section__add" @submit.prevent="onAdd">
			<select v-model="newType" class="share-section__select">
				<option :value="0">{{ t('lists', 'User') }}</option>
				<option :value="1">{{ t('lists', 'Group') }}</option>
			</select>
			<input
				v-model="newWith"
				class="share-section__input"
				type="text"
				:placeholder="t('lists', 'Username or group…')"
			/>
			<select v-model="newPerms" class="share-section__select">
				<option :value="1">{{ t('lists', 'Read') }}</option>
				<option :value="3">{{ t('lists', 'Read & write') }}</option>
			</select>
			<button class="share-section__btn" type="submit" :disabled="!newWith.trim()">
				{{ t('lists', 'Share') }}
			</button>
		</form>

		<div v-if="loading" class="share-section__loading">{{ t('lists', 'Loading…') }}</div>
		<div v-else-if="error" class="share-section__error">{{ error }}</div>
		<ul v-else-if="shares.length" class="share-section__list">
			<li v-for="share in shares" :key="share.id" class="share-section__item">
				<span class="share-section__badge">{{ share.shareType === 0 ? t('lists', 'User') : t('lists', 'Group') }}</span>
				<span class="share-section__who">{{ share.shareWith }}</span>
				<select
					:value="share.permissions"
					class="share-section__select share-section__select--inline"
					@change="onUpdatePerms(share, +$event.target.value)">
					<option :value="1">{{ t('lists', 'Read') }}</option>
					<option :value="3">{{ t('lists', 'Read & write') }}</option>
				</select>
				<button class="share-section__remove" :title="t('lists', 'Remove')" @click="onRemove(share)">✕</button>
			</li>
		</ul>
		<p v-else class="share-section__empty">{{ t('lists', 'Not shared yet.') }}</p>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { sharesApi } from '../services/api.js'

export default {
	name: 'ShareSection',

	props: {
		listId: { type: Number, required: true },
	},

	data() {
		return {
			shares: [],
			loading: false,
			error: null,
			newType: 0,
			newWith: '',
			newPerms: 1,
		}
	},

	watch: {
		listId: {
			immediate: true,
			handler() { this.fetchShares() },
		},
	},

	methods: {
		t,

		async fetchShares() {
			this.loading = true
			this.error = null
			try {
				this.shares = await sharesApi.getAll(this.listId)
			} catch (e) {
				// Non-owners get 403 — hide section silently
				this.shares = []
			} finally {
				this.loading = false
			}
		},

		async onAdd() {
			const with_ = this.newWith.trim()
			if (!with_) return
			try {
				const share = await sharesApi.create(this.listId, this.newType, with_, this.newPerms)
				this.shares.push(share)
				this.newWith = ''
			} catch (e) {
				this.error = e.message
			}
		},

		async onUpdatePerms(share, permissions) {
			try {
				const updated = await sharesApi.update(this.listId, share.id, permissions)
				const idx = this.shares.findIndex((s) => s.id === share.id)
				if (idx !== -1) this.shares[idx] = updated
			} catch (e) {
				this.error = e.message
			}
		},

		async onRemove(share) {
			try {
				await sharesApi.destroy(this.listId, share.id)
				this.shares = this.shares.filter((s) => s.id !== share.id)
			} catch (e) {
				this.error = e.message
			}
		},
	},
}
</script>

<style scoped>
.share-section {
	padding: 24px 24px 24px 0;
	border-top: 1px solid var(--color-border-dark);
	margin-top: 16px;
}
.share-section__title {
	font-size: 1em;
	font-weight: bold;
	margin: 0 0 12px;
	color: var(--color-text-lighter);
	text-transform: uppercase;
	letter-spacing: 0.05em;
}
.share-section__add {
	display: flex;
	gap: 6px;
	margin-bottom: 12px;
	flex-wrap: wrap;
}
.share-section__input {
	flex: 1;
	min-width: 120px;
	padding: 6px 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
}
.share-section__select {
	padding: 6px 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	cursor: pointer;
}
.share-section__btn {
	padding: 6px 14px;
	background: var(--color-primary);
	color: var(--color-primary-text);
	border: none;
	border-radius: var(--border-radius);
	cursor: pointer;
}
.share-section__btn:disabled {
	opacity: 0.5;
	cursor: default;
}
.share-section__list {
	list-style: none;
	margin: 0;
	padding: 0;
}
.share-section__item {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 6px 0;
	border-bottom: 1px solid var(--color-border-dark);
}
.share-section__badge {
	font-size: 0.75em;
	padding: 2px 6px;
	border-radius: 10px;
	background: var(--color-background-dark);
	color: var(--color-text-lighter);
	white-space: nowrap;
}
.share-section__who {
	flex: 1;
	font-weight: 500;
}
.share-section__select--inline {
	font-size: 0.85em;
}
.share-section__remove {
	background: none;
	border: none;
	cursor: pointer;
	color: var(--color-text-lighter);
	padding: 4px;
}
.share-section__empty,
.share-section__loading,
.share-section__error {
	color: var(--color-text-lighter);
	font-size: 0.9em;
}
.share-section__error {
	color: var(--color-error);
}
</style>
