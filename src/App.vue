<template>
	<NcApp>
		<template #navigation>
			<NcAppNavigation>
				<template #list>
					<NcAppNavigationNewItem
						:name="t('lists', 'New list')"
						@new-item="onCreate" />
					<NcAppNavigationItem
						v-for="list in store.lists"
						:key="list.id"
						:name="list.name"
						:active="list.id === store.selectedId"
						@click="store.select(list.id)">
						<template #actions>
							<NcActionButton @click="store.destroy(list.id)">
								<template #icon>
									<Delete :size="20" />
								</template>
								{{ t('lists', 'Delete') }}
							</NcActionButton>
						</template>
					</NcAppNavigationItem>
				</template>
			</NcAppNavigation>
		</template>

		<template #default>
			<NcAppContent>
				<div v-if="store.loading" class="lists-loading">
					<NcLoadingIcon :size="48" />
				</div>
				<div v-else-if="store.error" class="lists-error">
					{{ store.error }}
				</div>
				<div v-else-if="!store.selected" class="lists-empty">
					<NcEmptyContent
						:name="t('lists', 'No list selected')"
						:description="t('lists', 'Create a list or select one in the sidebar.')">
						<template #icon>
							<FormatListChecks :size="64" />
						</template>
					</NcEmptyContent>
				</div>
				<div v-else class="lists-view">
					<h2>{{ store.selected.name }}</h2>
					<p v-if="store.selected.description">{{ store.selected.description }}</p>
					<p class="lists-placeholder">
						{{ t('lists', 'Items coming in milestone 5.') }}
					</p>
				</div>
			</NcAppContent>
		</template>
	</NcApp>
</template>

<script>
import {
	NcApp,
	NcAppContent,
	NcAppNavigation,
	NcAppNavigationItem,
	NcAppNavigationNewItem,
	NcActionButton,
	NcEmptyContent,
	NcLoadingIcon,
} from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import FormatListChecks from 'vue-material-design-icons/FormatListChecks.vue'
import { useListsStore } from './store/lists.js'

export default {
	name: 'App',

	components: {
		NcApp,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNewItem,
		NcActionButton,
		NcEmptyContent,
		NcLoadingIcon,
		Delete,
		FormatListChecks,
	},

	setup() {
		const store = useListsStore()
		store.fetchAll()
		return { store }
	},

	methods: {
		async onCreate(name) {
			if (!name.trim()) return
			await this.store.create(name)
		},

		t(app, text) {
			return window.t ? window.t(app, text) : text
		},
	},
}
</script>
