<template>
	<div class="modal-overlay" @click.self="$emit('close')">
		<div class="modal-card" role="dialog" :aria-label="t('lists', 'Share list')">
			<div class="modal-card__header">
				<h2 class="modal-card__title">{{ t('lists', 'Share') }} — {{ list.name }}</h2>
				<button class="modal-card__close" :aria-label="t('lists', 'Close')" @click="$emit('close')">✕</button>
			</div>

			<div class="modal-card__body">
				<!-- Search row -->
				<div class="share-add">
					<UserGroupSearch ref="search" @select="onSearchSelect" />
					<div v-if="pending" class="share-add__pending">
						<span class="share-list__badge">
							{{ pending.type === 0 ? t('lists', 'User') : t('lists', 'Group') }}
						</span>
						<span class="share-add__name">{{ pending.displayName }}</span>
						<select v-model="pendingPerms" class="share-form__select share-form__select--sm">
							<option :value="1">{{ t('lists', 'Read') }}</option>
							<option :value="3">{{ t('lists', 'Read & write') }}</option>
						</select>
						<button class="share-add__confirm" @click="confirmAdd">{{ t('lists', 'Share') }}</button>
						<button class="share-add__cancel" @click="pending = null">✕</button>
					</div>
				</div>

				<p v-if="error" class="share-form__error">{{ error }}</p>

				<!-- Existing shares -->
				<ul v-if="shares.length" class="share-list">
					<li v-for="share in shares" :key="share.id" class="share-list__item">
						<span class="share-list__badge">
							{{ share.shareType === 0 ? t('lists', 'User') : t('lists', 'Group') }}
						</span>
						<span class="share-list__who">{{ share.shareWith }}</span>
						<select
							:value="share.permissions"
							class="share-form__select share-form__select--sm"
							@change="onUpdatePerms(share, +$event.target.value)">
							<option :value="1">{{ t('lists', 'Read') }}</option>
							<option :value="3">{{ t('lists', 'Read & write') }}</option>
						</select>
						<button class="share-list__remove" :title="t('lists', 'Remove')" @click="onRemove(share)">✕</button>
					</li>
				</ul>
				<p v-else-if="!loading" class="share-form__empty">{{ t('lists', 'Not shared yet.') }}</p>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { sharesApi } from '../services/api.js'
import UserGroupSearch from './UserGroupSearch.vue'

export default {
	name: 'ShareModal',

	components: { UserGroupSearch },

	props: {
		list: { type: Object, required: true },
	},

	emits: ['close'],

	data() {
		return {
			shares: [],
			loading: false,
			error: null,
			pending: null,     // { type, id, displayName }
			pendingPerms: 1,
		}
	},

	mounted() {
		this.fetchShares()
		document.addEventListener('keydown', this.onKeydown)
	},

	beforeUnmount() {
		document.removeEventListener('keydown', this.onKeydown)
	},

	methods: {
		t,

		onKeydown(e) {
			if (e.key === 'Escape') this.$emit('close')
		},

		async fetchShares() {
			this.loading = true
			try {
				this.shares = await sharesApi.getAll(this.list.id)
			} catch {
				this.shares = []
			} finally {
				this.loading = false
			}
		},

		onSearchSelect(result) {
			this.pending = result
			this.pendingPerms = 1
			this.error = null
		},

		async confirmAdd() {
			if (!this.pending) return
			this.error = null
			try {
				const share = await sharesApi.create(
					this.list.id,
					this.pending.type,
					this.pending.id,
					this.pendingPerms,
				)
				this.shares.push(share)
				this.pending = null
				showSuccess(t('lists', 'Shared successfully'))
			} catch (e) {
				const msg = e.response?.data?.ocs?.meta?.message || e.message
				this.error = msg
				showError(msg)
			}
		},

		async onUpdatePerms(share, permissions) {
			try {
				const updated = await sharesApi.update(this.list.id, share.id, permissions)
				const idx = this.shares.findIndex((s) => s.id === share.id)
				if (idx !== -1) this.shares[idx] = updated
				showSuccess(t('lists', 'Permissions updated'))
			} catch (e) {
				this.error = e.message
				showError(t('lists', 'Could not update permissions'))
			}
		},

		async onRemove(share) {
			try {
				await sharesApi.destroy(this.list.id, share.id)
				this.shares = this.shares.filter((s) => s.id !== share.id)
				showSuccess(t('lists', 'Share removed'))
			} catch (e) {
				this.error = e.message
				showError(t('lists', 'Could not remove share'))
			}
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
	width: 500px;
	max-width: calc(100vw - 32px);
	max-height: calc(100vh - 64px);
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
	padding: 16px 20px 20px;
	overflow-y: auto;
}
.share-add {
	margin-bottom: 16px;
}
.share-add__pending {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-top: 10px;
	padding: 8px 12px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
}
.share-add__name {
	flex: 1;
	font-weight: 500;
}
.share-add__confirm {
	padding: 5px 12px;
	background: var(--color-primary);
	color: var(--color-primary-text);
	border: none;
	border-radius: var(--border-radius);
	cursor: pointer;
}
.share-add__cancel {
	background: none;
	border: none;
	cursor: pointer;
	color: var(--color-text-lighter);
	padding: 4px 8px;
}
.share-form__select {
	padding: 6px 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	cursor: pointer;
}
.share-form__select--sm {
	font-size: 0.85em;
	padding: 4px 6px;
}
.share-form__error {
	color: var(--color-error);
	font-size: 0.9em;
	margin: 0 0 12px;
}
.share-form__empty {
	color: var(--color-text-lighter);
	font-size: 0.9em;
}
.share-list {
	list-style: none;
	margin: 0;
	padding: 0;
}
.share-list__item {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 0;
	border-bottom: 1px solid var(--color-border-dark);
}
.share-list__badge {
	font-size: 0.75em;
	padding: 2px 7px;
	border-radius: 10px;
	background: var(--color-background-dark);
	color: var(--color-text-lighter);
	white-space: nowrap;
}
.share-list__who {
	flex: 1;
	font-weight: 500;
}
.share-list__remove {
	background: none;
	border: none;
	cursor: pointer;
	color: var(--color-text-lighter);
	padding: 4px;
}
</style>
