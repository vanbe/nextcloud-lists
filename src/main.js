import { createApp, configureCompat } from '@vue/compat'
import { createPinia } from 'pinia'
import App from './App.vue'

// Enable Vue 2 compat mode so @nextcloud/vue components work
configureCompat({ MODE: 2 })

const app = createApp(App)
app.use(createPinia())
app.mount('#lists-root')
