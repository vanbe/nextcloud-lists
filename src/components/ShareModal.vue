<template>
	<NcDialog
		:name="t('lists', 'Share') + ' — ' + list.name"
		size="small"
		close-on-click-outside
		@closing="$emit('close')">
		<div class="share-modal__body">
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

		<template #actions>
			<NcButton type="primary" @click="$emit('close')">
				{{ t('lists', 'Done') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import { sharesApi } from '../services/api.js'
import UserGroupSearch from './UserGroupSearch.vue'

export default {
	name: 'ShareModal',

	components: { NcDialog, NcButton, UserGroupSearch },

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
	},

	methods: {
		t,

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
.share-modal__body {
	display: flex;
	flex-direction: column;
	min-height: 200px;
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
	font-size: 16px;
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
