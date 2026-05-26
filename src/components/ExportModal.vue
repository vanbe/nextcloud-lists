<template>
	<NcDialog
		:name="t('lists', 'Export') + ' — ' + list.name"
		size="normal"
		close-on-click-outside
		@closing="$emit('close')">
		<div class="ex__body">
			<div v-if="loading" class="ex__loading">{{ t('lists', 'Loading…') }}</div>
			<div v-else class="ex__preview" v-html="html" />
		</div>

		<template #actions>
			<NcButton type="secondary" @click="onCopy">
				{{ copied ? t('lists', 'Copied!') : t('lists', 'Copy markdown') }}
			</NcButton>
			<NcButton type="secondary" @click="onPrint">
				{{ t('lists', 'Download PDF') }}
			</NcButton>
			<NcButton type="primary" @click="$emit('close')">
				{{ t('lists', 'Done') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'
import MarkdownIt from 'markdown-it'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import { itemsApi, categoriesApi } from '../services/api.js'

const md = new MarkdownIt({ html: false, breaks: true, linkify: true })

export default {
	name: 'ExportModal',

	components: { NcDialog, NcButton },

	props: {
		list: { type: Object, required: true },
	},

	emits: ['close'],

	data() {
		return {
			items: [],
			categories: [],
			loading: true,
			copied: false,
		}
	},

	computed: {
		markdown() {
			return this.buildMarkdown()
		},
		html() {
			const rendered = md.render(this.markdown)
			// markdown-it core doesn't handle GFM task lists — replace the plain-text markers
			// that appear in rendered <li> content with real checkbox inputs.
			return rendered
				.replace(/<li>\[x\] /gi, '<li><input type="checkbox" checked disabled> ')
				.replace(/<li>\[ \] /g, '<li><input type="checkbox" disabled> ')
		},
	},

	async mounted() {
		try {
			const [items, categories] = await Promise.all([
				itemsApi.getAll(this.list.id),
				categoriesApi.getAll(this.list.id),
			])
			this.items = items
			this.categories = categories
		} catch {
			showError(t('lists', 'Could not load list data'))
		} finally {
			this.loading = false
		}
	},

	methods: {
		t,

		buildMarkdown() {
			const lines = []
			lines.push(`# ${this.list.name}`)
			lines.push('')
			if (this.list.description) {
				lines.push(this.list.description)
				lines.push('')
			}

			const catMap = new Map(this.categories.map((c) => [c.id, c]))
			const unchecked = this.items.filter((i) => !i.checked)
			const checked = this.items.filter((i) => i.checked)

			const renderItem = (item) => {
				const box = item.checked ? '[x]' : '[ ]'
				const qty = (this.list.hasQuantities && (item.quantity ?? 1) > 1) ? ` (×${item.quantity})` : ''
				return `- ${box} ${item.title}${qty}`
			}

			if (this.categories.length) {
				// Group unchecked by category in category order
				for (const cat of this.categories) {
					const inCat = unchecked.filter((i) => i.categoryId === cat.id)
					if (!inCat.length) continue
					const heading = cat.icon ? `## ${cat.icon} ${cat.name}` : `## ${cat.name}`
					lines.push(heading)
					lines.push('')
					inCat.forEach((i) => lines.push(renderItem(i)))
					lines.push('')
				}
				// Uncategorised unchecked
				const uncat = unchecked.filter((i) => !i.categoryId || !catMap.has(i.categoryId))
				if (uncat.length) {
					lines.push(`## ${t('lists', 'Other')}`)
					lines.push('')
					uncat.forEach((i) => lines.push(renderItem(i)))
					lines.push('')
				}
			} else if (unchecked.length) {
				unchecked.forEach((i) => lines.push(renderItem(i)))
				lines.push('')
			}

			if (checked.length) {
				lines.push(`## ${t('lists', 'Checked')}`)
				lines.push('')
				checked.forEach((i) => lines.push(renderItem(i)))
				lines.push('')
			}

			lines.push('---')
			const total = this.items.length
			const doneCount = checked.length
			lines.push(`*${t('lists', 'Items')}: ${total} — ${t('lists', 'Checked')}: ${doneCount}/${total}*`)
			lines.push(`*${t('lists', 'Exported')}: ${new Date().toLocaleString()}*`)
			return lines.join('\n')
		},

		async onCopy() {
			try {
				await navigator.clipboard.writeText(this.markdown)
				this.copied = true
				showSuccess(t('lists', 'Copied to clipboard'))
				setTimeout(() => { this.copied = false }, 2000)
			} catch {
				showError(t('lists', 'Clipboard not available'))
			}
		},

		onPrint() {
			const win = window.open('', '_blank', 'width=800,height=900')
			if (!win) {
				showError(t('lists', 'Pop-up blocked — allow pop-ups to print'))
				return
			}
			const doc = win.document
			doc.open()
			doc.write(`<!doctype html><html><head><meta charset="utf-8"><title>${escapeHtml(this.list.name)}</title>
<style>
	body { font-family: -apple-system, "Segoe UI", Roboto, sans-serif; color: #222; padding: 24px; max-width: 700px; margin: 0 auto; line-height: 1.5; }
	h1 { border-bottom: 1px solid #ddd; padding-bottom: 8px; }
	h2 { margin-top: 24px; color: #444; }
	ul { list-style: none; padding-left: 0; }
	li { padding: 4px 0; }
	hr { border: none; border-top: 1px solid #ddd; margin: 24px 0 12px; }
	em { color: #888; font-style: normal; font-size: 0.85em; display: block; }
	input[type="checkbox"] { margin-right: 8px; }
	@media print {
		body { padding: 0; }
	}
</style>
</head><body>${this.html}<script>setTimeout(() => { window.print(); }, 150);<\/script></body></html>`)
			doc.close()
		},
	},
}

function escapeHtml(s) {
	return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', '\'': '&#39;' }[c]))
}
</script>

<style scoped>
.ex__body {
	min-height: 280px;
	max-height: 60vh;
	overflow-y: auto;
}
.ex__loading {
	color: var(--color-text-lighter);
	padding: 40px 0;
	text-align: center;
}
.ex__preview {
	padding: 8px 4px;
	color: var(--color-main-text);
}

/* Style for the rendered markdown */
.ex__preview :deep(h1) {
	margin: 8px 0 12px;
	font-size: 1.5em;
	border-bottom: 1px solid var(--color-border);
	padding-bottom: 6px;
}
.ex__preview :deep(h2) {
	margin: 20px 0 8px;
	font-size: 1.1em;
	color: var(--color-text-lighter);
}
.ex__preview :deep(p) {
	margin: 6px 0;
}
.ex__preview :deep(ul) {
	list-style: none;
	padding-left: 0;
	margin: 4px 0;
}
.ex__preview :deep(li) {
	padding: 2px 0;
}
.ex__preview :deep(hr) {
	border: none;
	border-top: 1px solid var(--color-border);
	margin: 20px 0 10px;
}
.ex__preview :deep(em) {
	color: var(--color-text-lighter);
	font-style: normal;
	font-size: 0.85em;
	display: block;
}
.ex__preview :deep(input[type='checkbox']) {
	margin-right: 6px;
	pointer-events: none;
	vertical-align: middle;
	accent-color: var(--color-primary);
}
</style>
