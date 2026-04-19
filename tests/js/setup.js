import { config } from '@vue/test-utils'

// Suppress Vue compat warnings in test output
config.global.config.warnHandler = () => {}
