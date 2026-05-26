<template>
	<NcDialog
		:name="t('lists', 'Choose icon')"
		size="normal"
		close-on-click-outside
		@closing="$emit('close')">
		<div class="ip__body">
			<div class="ip__current">
				<span class="ip__preview">{{ draft || '—' }}</span>
				<input
					v-model="draft"
					class="ip__input"
					type="text"
					maxlength="8"
					:placeholder="t('lists', 'Or paste an emoji here')" />
				<button
					v-if="draft"
					class="ip__clear"
					:title="t('lists', 'Remove icon')"
					@click="draft = ''">
					✕
				</button>
			</div>

			<input
				ref="searchEl"
				v-model="search"
				class="ip__search"
				type="search"
				:placeholder="t('lists', 'Search — try: kitchen, fruit, voyage, sport…')" />

			<div v-if="filteredSections.length" class="ip__sections">
				<section v-for="section in filteredSections" :key="section.title">
					<h4 class="ip__section-title">{{ section.title }}</h4>
					<div class="ip__grid">
						<button
							v-for="entry in section.emojis"
							:key="entry.e"
							class="ip__grid-btn"
							:class="{ 'ip__grid-btn--active': draft === entry.e }"
							:title="entry.k[0]"
							@click="draft = entry.e">
							{{ entry.e }}
						</button>
					</div>
				</section>
			</div>
			<p v-else class="ip__empty">{{ t('lists', 'No emoji matches your search.') }}</p>
		</div>

		<template #actions>
			<NcButton type="secondary" @click="$emit('close')">
				{{ t('lists', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" @click="onSave">
				{{ t('lists', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'

// Keywords: lowercase, multiple per entry, FR + EN. Search is substring-match.
const SECTIONS = [
	{
		title: t('lists', 'Fruits & vegetables'),
		emojis: [
			{ e: '🥕', k: ['carrot', 'carotte', 'legume', 'vegetable'] },
			{ e: '🥔', k: ['potato', 'patate', 'pomme de terre'] },
			{ e: '🍅', k: ['tomato', 'tomate'] },
			{ e: '🥒', k: ['cucumber', 'concombre'] },
			{ e: '🥦', k: ['broccoli', 'brocoli'] },
			{ e: '🥬', k: ['lettuce', 'salade', 'salad'] },
			{ e: '🌽', k: ['corn', 'maïs'] },
			{ e: '🌶️', k: ['pepper', 'piment', 'spicy'] },
			{ e: '🍄', k: ['mushroom', 'champignon'] },
			{ e: '🧄', k: ['garlic', 'ail'] },
			{ e: '🧅', k: ['onion', 'oignon'] },
			{ e: '🥑', k: ['avocado', 'avocat'] },
			{ e: '🍎', k: ['apple', 'pomme', 'fruit'] },
			{ e: '🍌', k: ['banana', 'banane'] },
			{ e: '🍇', k: ['grapes', 'raisin'] },
			{ e: '🍓', k: ['strawberry', 'fraise'] },
			{ e: '🍊', k: ['orange'] },
			{ e: '🍋', k: ['lemon', 'citron'] },
			{ e: '🍉', k: ['watermelon', 'pastèque'] },
			{ e: '🍑', k: ['peach', 'pêche'] },
			{ e: '🍒', k: ['cherry', 'cerise'] },
			{ e: '🥥', k: ['coconut', 'noix de coco'] },
			{ e: '🍍', k: ['pineapple', 'ananas'] },
		],
	},
	{
		title: t('lists', 'Bakery, dairy & staples'),
		emojis: [
			{ e: '🍞', k: ['bread', 'pain', 'boulangerie', 'bakery'] },
			{ e: '🥖', k: ['baguette', 'pain', 'bread'] },
			{ e: '🥐', k: ['croissant', 'boulangerie'] },
			{ e: '🥯', k: ['bagel'] },
			{ e: '🥨', k: ['pretzel', 'bretzel'] },
			{ e: '🧀', k: ['cheese', 'fromage', 'dairy', 'laitier'] },
			{ e: '🥛', k: ['milk', 'lait', 'dairy'] },
			{ e: '🧈', k: ['butter', 'beurre'] },
			{ e: '🥚', k: ['egg', 'oeuf', 'œuf'] },
			{ e: '🍯', k: ['honey', 'miel'] },
			{ e: '🍚', k: ['rice', 'riz'] },
			{ e: '🍝', k: ['pasta', 'pates', 'pâtes', 'spaghetti'] },
			{ e: '🍜', k: ['noodles', 'ramen', 'soupe'] },
			{ e: '🧂', k: ['salt', 'sel', 'epices'] },
		],
	},
	{
		title: t('lists', 'Meat, fish & deli'),
		emojis: [
			{ e: '🥩', k: ['meat', 'viande', 'steak', 'boucherie', 'butcher'] },
			{ e: '🍗', k: ['poultry', 'volaille', 'chicken', 'poulet'] },
			{ e: '🍖', k: ['meat', 'viande', 'bone'] },
			{ e: '🥓', k: ['bacon', 'lard'] },
			{ e: '🌭', k: ['hot dog', 'saucisse', 'sausage'] },
			{ e: '🐟', k: ['fish', 'poisson', 'poissonnerie'] },
			{ e: '🍣', k: ['sushi', 'fish', 'poisson'] },
			{ e: '🍤', k: ['shrimp', 'crevette', 'fruits de mer'] },
		],
	},
	{
		title: t('lists', 'Snacks & sweets'),
		emojis: [
			{ e: '🍪', k: ['cookie', 'biscuit'] },
			{ e: '🍫', k: ['chocolate', 'chocolat'] },
			{ e: '🍬', k: ['candy', 'bonbon'] },
			{ e: '🍰', k: ['cake', 'gateau', 'gâteau'] },
			{ e: '🧁', k: ['cupcake', 'pâtisserie'] },
			{ e: '🍩', k: ['donut', 'beignet'] },
			{ e: '🍦', k: ['ice cream', 'glace'] },
			{ e: '🥜', k: ['nuts', 'cacahuète', 'cacahuete', 'peanut'] },
			{ e: '🍿', k: ['popcorn'] },
		],
	},
	{
		title: t('lists', 'Drinks'),
		emojis: [
			{ e: '☕', k: ['coffee', 'café', 'cafe'] },
			{ e: '🍵', k: ['tea', 'thé', 'the'] },
			{ e: '🥤', k: ['soda', 'drink', 'boisson'] },
			{ e: '🧋', k: ['bubble tea'] },
			{ e: '🧃', k: ['juice', 'jus'] },
			{ e: '💧', k: ['water', 'eau'] },
			{ e: '🍺', k: ['beer', 'bière', 'biere'] },
			{ e: '🍷', k: ['wine', 'vin'] },
			{ e: '🍾', k: ['champagne', 'bottle'] },
			{ e: '🍸', k: ['cocktail'] },
			{ e: '🧊', k: ['ice', 'glace', 'glaçon', 'frozen', 'surgelé'] },
		],
	},
	{
		title: t('lists', 'Household & cleaning'),
		emojis: [
			{ e: '🧴', k: ['lotion', 'savon', 'shampoo', 'shampooing', 'hygiene'] },
			{ e: '🧻', k: ['toilet paper', 'papier toilette', 'pq'] },
			{ e: '🧼', k: ['soap', 'savon', 'cleaning'] },
			{ e: '🧽', k: ['sponge', 'eponge', 'éponge'] },
			{ e: '🧹', k: ['broom', 'balai', 'cleaning'] },
			{ e: '🧺', k: ['basket', 'panier', 'laundry', 'linge'] },
			{ e: '🪥', k: ['toothbrush', 'brosse à dents'] },
			{ e: '🪒', k: ['razor', 'rasoir'] },
			{ e: '🧯', k: ['extinguisher', 'extincteur'] },
			{ e: '🕯️', k: ['candle', 'bougie'] },
			{ e: '💡', k: ['lightbulb', 'ampoule', 'idea', 'idée'] },
			{ e: '🛒', k: ['cart', 'shopping', 'caddie', 'courses'] },
			{ e: '👕', k: ['shirt', 'vetement', 'vêtement', 'clothes'] },
			{ e: '🧦', k: ['socks', 'chaussettes'] },
		],
	},
	{
		title: t('lists', 'Rooms'),
		emojis: [
			{ e: '🍳', k: ['kitchen', 'cuisine', 'cooking'] },
			{ e: '🛏️', k: ['bed', 'lit', 'bedroom', 'chambre'] },
			{ e: '🛋️', k: ['couch', 'canapé', 'living room', 'salon'] },
			{ e: '🛁', k: ['bath', 'baignoire', 'bathroom', 'salle de bain'] },
			{ e: '🚿', k: ['shower', 'douche', 'bathroom'] },
			{ e: '🚽', k: ['toilet', 'toilettes', 'wc'] },
			{ e: '🪑', k: ['chair', 'chaise', 'dining', 'salle à manger'] },
			{ e: '🚪', k: ['door', 'porte', 'entry', 'entrée'] },
			{ e: '🪟', k: ['window', 'fenêtre'] },
			{ e: '🧺', k: ['laundry', 'buanderie', 'linge'] },
			{ e: '🏠', k: ['house', 'maison', 'home'] },
			{ e: '🏡', k: ['home garden', 'maison jardin'] },
			{ e: '🌱', k: ['garden', 'jardin', 'plant', 'plante'] },
			{ e: '🚗', k: ['garage', 'car', 'voiture'] },
			{ e: '🖥️', k: ['office', 'bureau', 'computer', 'desktop'] },
			{ e: '📚', k: ['library', 'bibliothèque', 'study', 'bureau', 'office'] },
			{ e: '🧸', k: ['toys', 'jouets', 'kids room', 'chambre enfant'] },
			{ e: '🏋️', k: ['gym', 'salle de sport', 'workout'] },
			{ e: '🏊', k: ['pool', 'piscine'] },
			{ e: '🅿️', k: ['parking'] },
		],
	},
	{
		title: t('lists', 'Travel & suitcase'),
		emojis: [
			{ e: '✈️', k: ['plane', 'avion', 'flight', 'travel', 'voyage'] },
			{ e: '🧳', k: ['suitcase', 'valise', 'luggage', 'bagage'] },
			{ e: '🎒', k: ['backpack', 'sac à dos'] },
			{ e: '👜', k: ['bag', 'sac'] },
			{ e: '🛂', k: ['passport', 'passeport', 'border'] },
			{ e: '🪪', k: ['id', 'carte identité', 'papiers'] },
			{ e: '💳', k: ['card', 'carte', 'credit', 'crédit', 'bancaire'] },
			{ e: '💵', k: ['cash', 'argent', 'money'] },
			{ e: '🗺️', k: ['map', 'carte', 'directions'] },
			{ e: '📷', k: ['camera', 'appareil photo'] },
			{ e: '🕶️', k: ['sunglasses', 'lunettes de soleil'] },
			{ e: '👙', k: ['swimsuit', 'maillot', 'beach', 'plage'] },
			{ e: '🩴', k: ['flip flops', 'tongs'] },
			{ e: '🏖️', k: ['beach', 'plage'] },
			{ e: '⛰️', k: ['mountain', 'montagne', 'hiking', 'randonnée'] },
			{ e: '🏨', k: ['hotel'] },
			{ e: '🚂', k: ['train'] },
			{ e: '🚌', k: ['bus'] },
			{ e: '🚕', k: ['taxi'] },
			{ e: '⛵', k: ['boat', 'bateau', 'sailing'] },
			{ e: '🌍', k: ['world', 'monde', 'earth', 'planet'] },
			{ e: '☀️', k: ['sun', 'soleil', 'summer', 'été'] },
			{ e: '❄️', k: ['snow', 'neige', 'winter', 'hiver', 'ski'] },
		],
	},
	{
		title: t('lists', 'Activities & tasks'),
		emojis: [
			{ e: '💼', k: ['work', 'travail', 'job', 'business', 'briefcase'] },
			{ e: '📅', k: ['calendar', 'calendrier', 'date', 'schedule', 'agenda'] },
			{ e: '⏰', k: ['alarm', 'reveil', 'réveil', 'time', 'urgent'] },
			{ e: '🏃', k: ['run', 'courir', 'sport', 'jogging'] },
			{ e: '🚴', k: ['bike', 'vélo', 'velo', 'cycling'] },
			{ e: '🏊', k: ['swim', 'natation', 'nager'] },
			{ e: '🧘', k: ['yoga', 'meditation', 'méditation'] },
			{ e: '📖', k: ['read', 'lire', 'book', 'livre'] },
			{ e: '✍️', k: ['write', 'écrire', 'ecrire'] },
			{ e: '🎨', k: ['art', 'peinture', 'paint', 'hobby'] },
			{ e: '🎵', k: ['music', 'musique'] },
			{ e: '🎬', k: ['movie', 'film', 'cinema'] },
			{ e: '🎮', k: ['game', 'jeu', 'gaming'] },
			{ e: '⚽', k: ['sport', 'foot', 'football', 'soccer'] },
			{ e: '🎓', k: ['study', 'étudier', 'school', 'école', 'education'] },
			{ e: '🧹', k: ['chore', 'menage', 'ménage', 'cleaning'] },
			{ e: '🛍️', k: ['shopping', 'courses'] },
			{ e: '🚿', k: ['shower', 'douche'] },
			{ e: '🍽️', k: ['eat', 'manger', 'meal', 'repas'] },
			{ e: '💊', k: ['medicine', 'medicament', 'médicament', 'health', 'santé'] },
		],
	},
	{
		title: t('lists', 'Communication & actions'),
		emojis: [
			{ e: '📞', k: ['call', 'appeler', 'telephone', 'phone'] },
			{ e: '📱', k: ['mobile', 'phone', 'smartphone'] },
			{ e: '✉️', k: ['email', 'mail', 'message', 'courrier'] },
			{ e: '📧', k: ['email', 'mail'] },
			{ e: '💬', k: ['chat', 'message', 'sms'] },
			{ e: '📝', k: ['note', 'write', 'écrire'] },
			{ e: '📋', k: ['list', 'liste', 'clipboard'] },
			{ e: '✅', k: ['check', 'done', 'fait', 'ok', 'todo'] },
			{ e: '❗', k: ['important', 'urgent', 'attention'] },
			{ e: '❓', k: ['question'] },
			{ e: '🔔', k: ['bell', 'notification', 'reminder', 'rappel'] },
			{ e: '🔑', k: ['key', 'clé'] },
			{ e: '🔒', k: ['lock', 'cadenas', 'secure'] },
			{ e: '💾', k: ['save', 'backup', 'sauvegarde', 'disk'] },
			{ e: '📦', k: ['package', 'colis', 'box', 'shipping'] },
			{ e: '📬', k: ['mail box', 'boite aux lettres'] },
			{ e: '🏷️', k: ['tag', 'label', 'etiquette', 'étiquette'] },
		],
	},
	{
		title: t('lists', 'Tools & DIY'),
		emojis: [
			{ e: '🔧', k: ['wrench', 'clé', 'tools', 'outils', 'repair'] },
			{ e: '🔨', k: ['hammer', 'marteau'] },
			{ e: '🛠️', k: ['tools', 'outils', 'bricolage', 'diy'] },
			{ e: '🪛', k: ['screwdriver', 'tournevis'] },
			{ e: '🪚', k: ['saw', 'scie'] },
			{ e: '🪓', k: ['axe', 'hache'] },
			{ e: '⚙️', k: ['gear', 'settings', 'paramètres', 'config'] },
			{ e: '🔌', k: ['plug', 'prise', 'electric'] },
			{ e: '🔋', k: ['battery', 'pile', 'batterie'] },
			{ e: '🧰', k: ['toolbox', 'boite à outils'] },
			{ e: '🪜', k: ['ladder', 'echelle', 'échelle'] },
			{ e: '🧲', k: ['magnet', 'aimant'] },
		],
	},
	{
		title: t('lists', 'People'),
		emojis: [
			{ e: '👨', k: ['man', 'homme'] },
			{ e: '👩', k: ['woman', 'femme'] },
			{ e: '🧑', k: ['person', 'personne'] },
			{ e: '👶', k: ['baby', 'bébé', 'bebe'] },
			{ e: '🧒', k: ['child', 'enfant'] },
			{ e: '👨‍👩‍👧', k: ['family', 'famille'] },
			{ e: '👥', k: ['people', 'groupe', 'team', 'équipe'] },
			{ e: '👫', k: ['couple'] },
			{ e: '👴', k: ['grandpa', 'grand-père', 'old'] },
			{ e: '👵', k: ['grandma', 'grand-mère'] },
			{ e: '🧑‍🤝‍🧑', k: ['friends', 'amis'] },
			{ e: '👨‍⚕️', k: ['doctor', 'médecin'] },
			{ e: '👩‍🏫', k: ['teacher', 'prof', 'professeur'] },
		],
	},
	{
		title: t('lists', 'Animals & pets'),
		emojis: [
			{ e: '🐶', k: ['dog', 'chien'] },
			{ e: '🐱', k: ['cat', 'chat'] },
			{ e: '🐰', k: ['rabbit', 'lapin'] },
			{ e: '🐹', k: ['hamster'] },
			{ e: '🐦', k: ['bird', 'oiseau'] },
			{ e: '🐠', k: ['fish', 'poisson', 'aquarium'] },
			{ e: '🐴', k: ['horse', 'cheval'] },
			{ e: '🐔', k: ['chicken', 'poule'] },
			{ e: '🦮', k: ['guide dog'] },
		],
	},
	{
		title: t('lists', 'Symbols & misc'),
		emojis: [
			{ e: '⭐', k: ['star', 'étoile', 'favorite'] },
			{ e: '❤️', k: ['heart', 'love', 'amour', 'coeur'] },
			{ e: '🎁', k: ['gift', 'cadeau', 'present'] },
			{ e: '🎂', k: ['birthday', 'anniversaire'] },
			{ e: '🎉', k: ['party', 'fête', 'celebration'] },
			{ e: '💰', k: ['money', 'argent'] },
			{ e: '🪙', k: ['coin', 'pièce', 'piece'] },
			{ e: '🏥', k: ['hospital', 'hopital', 'hôpital'] },
			{ e: '🏫', k: ['school', 'école', 'ecole'] },
			{ e: '⛪', k: ['church', 'église'] },
			{ e: '🛐', k: ['religion', 'prayer'] },
			{ e: '🌳', k: ['tree', 'arbre', 'nature'] },
			{ e: '🌸', k: ['flower', 'fleur', 'spring', 'printemps'] },
			{ e: '🌧️', k: ['rain', 'pluie'] },
			{ e: '☁️', k: ['cloud', 'nuage'] },
			{ e: '🔥', k: ['fire', 'feu', 'hot'] },
		],
	},
]

function normalize(s) {
	return s.toLowerCase()
		.normalize('NFD')
		.replace(/[̀-ͯ]/g, '') // strip combining diacritics
}

export default {
	name: 'IconPickerDialog',

	components: { NcDialog, NcButton },

	props: {
		current: { type: String, default: '' },
	},

	emits: ['close', 'save'],

	data() {
		return {
			draft: this.current || '',
			search: '',
		}
	},

	computed: {
		filteredSections() {
			const q = normalize(this.search.trim())
			if (!q) return SECTIONS
			return SECTIONS
				.map((s) => ({
					title: s.title,
					emojis: s.emojis.filter((entry) =>
						entry.k.some((kw) => normalize(kw).includes(q)),
					),
				}))
				.filter((s) => s.emojis.length > 0)
		},
	},

	methods: {
		t,

		onSave() {
			this.$emit('save', (this.draft || '').trim())
		},
	},
}
</script>

<style scoped>
.ip__body {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 4px 0;
}
.ip__current {
	display: flex;
	align-items: center;
	gap: 10px;
}
.ip__preview {
	font-size: 1.8em;
	line-height: 1;
	width: 48px;
	height: 48px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	display: flex;
	align-items: center;
	justify-content: center;
	background: var(--color-background-dark);
	flex-shrink: 0;
}
.ip__input,
.ip__search {
	flex: 1;
	padding: 10px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 16px;
	box-sizing: border-box;
}
.ip__search {
	width: 100%;
	flex: none;
}
.ip__input:focus,
.ip__search:focus {
	outline: none;
	border-color: var(--color-primary);
}
.ip__clear {
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 8px 10px;
	cursor: pointer;
	color: var(--color-text-lighter);
	flex-shrink: 0;
}
.ip__clear:hover {
	color: var(--color-error);
}
.ip__sections {
	max-height: 50vh;
	overflow-y: auto;
	display: flex;
	flex-direction: column;
	gap: 12px;
}
.ip__section-title {
	margin: 0 0 6px;
	font-size: 0.85em;
	font-weight: 600;
	color: var(--color-text-lighter);
	text-transform: uppercase;
	letter-spacing: 0.05em;
}
.ip__grid {
	display: grid;
	grid-template-columns: repeat(10, 1fr);
	gap: 4px;
}
.ip__grid-btn {
	background: var(--color-background-dark);
	border: 1px solid transparent;
	border-radius: var(--border-radius);
	padding: 6px 0;
	cursor: pointer;
	font-size: 1.4em;
	line-height: 1;
	transition: background 0.12s, border-color 0.12s;
}
.ip__grid-btn:hover {
	background: var(--color-background-hover);
}
.ip__grid-btn--active {
	border-color: var(--color-primary);
	background: var(--color-primary-light, rgba(0, 130, 201, 0.15));
}
.ip__empty {
	color: var(--color-text-lighter);
	font-size: 0.9em;
	margin: 8px 0;
	text-align: center;
}
@media (max-width: 600px) {
	.ip__grid {
		grid-template-columns: repeat(8, 1fr);
	}
}
</style>
