import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ItemInput from '../../src/components/ItemInput.vue'

vi.mock('../../src/services/api.js', () => ({
	itemsApi: {
		suggest: vi.fn().mockResolvedValue([]),
	},
}))

import { itemsApi } from '../../src/services/api.js'

function mountInput(props = {}) {
	return mount(ItemInput, {
		props: { listId: 1, hasQuantities: false, categories: [], defaultCategoryId: null, ...props },
	})
}

describe('ItemInput', () => {
	beforeEach(() => vi.clearAllMocks())

	// ── Submit ────────────────────────────────────────────────────────────────

	it('emits add with trimmed title', async () => {
		const w = mountInput()
		await w.find('input[type="text"]').setValue('  Milk  ')
		await w.find('button.item-input__btn').trigger('click')

		expect(w.emitted('add')).toHaveLength(1)
		expect(w.emitted('add')[0][0].title).toBe('Milk')
	})

	it('does not emit when title is blank', async () => {
		const w = mountInput()
		await w.find('button.item-input__btn').trigger('click')
		expect(w.emitted('add')).toBeFalsy()
	})

	it('resets title and quantity after submit', async () => {
		const w = mountInput({ hasQuantities: true })
		await w.find('input[type="text"]').setValue('Bread')
		// increment quantity to 3
		const [, plusBtn] = w.findAll('button.item-input__step-btn')
		await plusBtn.trigger('click')
		await plusBtn.trigger('click')
		await w.find('button.item-input__btn').trigger('click')

		expect(w.find('input[type="text"]').element.value).toBe('')
		expect(w.find('.item-input__step-val').text()).toBe('1')
	})

	// ── Quantity stepper ──────────────────────────────────────────────────────

	it('emits quantity=null when hasQuantities is false', async () => {
		const w = mountInput({ hasQuantities: false })
		await w.find('input[type="text"]').setValue('Eggs')
		await w.find('button.item-input__btn').trigger('click')

		expect(w.emitted('add')[0][0].quantity).toBeNull()
	})

	it('emits correct quantity when hasQuantities is true', async () => {
		const w = mountInput({ hasQuantities: true })
		await w.find('input[type="text"]').setValue('Eggs')

		const [, plusBtn] = w.findAll('button.item-input__step-btn')
		await plusBtn.trigger('click')
		await plusBtn.trigger('click')
		await plusBtn.trigger('click')

		await w.find('button.item-input__btn').trigger('click')

		expect(w.emitted('add')[0][0].quantity).toBe(4)
	})

	it('does not decrement quantity below 1', async () => {
		const w = mountInput({ hasQuantities: true })
		const [minusBtn] = w.findAll('button.item-input__step-btn')
		await minusBtn.trigger('click')

		expect(w.find('.item-input__step-val').text()).toBe('1')
	})

	it('minus button is disabled when quantity is 1', async () => {
		const w = mountInput({ hasQuantities: true })
		const [minusBtn] = w.findAll('button.item-input__step-btn')
		expect(minusBtn.element.disabled).toBe(true)
	})

	// ── Suggestions ───────────────────────────────────────────────────────────

	it('does not fetch suggestions for input shorter than 2 chars', async () => {
		const w = mountInput()
		await w.find('input[type="text"]').setValue('M')
		expect(itemsApi.suggest).not.toHaveBeenCalled()
	})

	it('fetches suggestions after debounce for input ≥ 2 chars', async () => {
		vi.useFakeTimers()
		itemsApi.suggest.mockResolvedValue([{ id: 1, title: 'Milk', checked: false }])

		const w = mountInput()
		await w.find('input[type="text"]').setValue('Mi')
		vi.advanceTimersByTime(200)
		await vi.runAllTimersAsync()

		expect(itemsApi.suggest).toHaveBeenCalledWith(1, 'Mi')
		vi.useRealTimers()
	})

	it('does not fetch suggestions within debounce window', async () => {
		vi.useFakeTimers()
		const w = mountInput()
		const input = w.find('input[type="text"]')

		await input.setValue('Mi')
		vi.advanceTimersByTime(50)
		await input.setValue('Mil')
		vi.advanceTimersByTime(50)

		// Only 100 ms elapsed — debounce (150 ms) not fired yet
		expect(itemsApi.suggest).not.toHaveBeenCalled()
		vi.useRealTimers()
	})

	it('clears suggestions on Escape', async () => {
		vi.useFakeTimers()
		itemsApi.suggest.mockResolvedValue([{ id: 1, title: 'Milk', checked: false }])

		const w = mountInput()
		await w.find('input[type="text"]').setValue('Mi')
		vi.advanceTimersByTime(200)
		await vi.runAllTimersAsync()

		await w.trigger('keydown', { key: 'Escape' })
		expect(w.findAll('.item-input__suggestion')).toHaveLength(0)
		vi.useRealTimers()
	})

	// ── Keyboard navigation ───────────────────────────────────────────────────

	it('Enter with no focused suggestion submits the form', async () => {
		const w = mountInput()
		await w.find('input[type="text"]').setValue('Butter')
		await w.find('input[type="text"]').trigger('keydown', { key: 'Enter' })

		expect(w.emitted('add')).toHaveLength(1)
	})

	it('emits select-suggestion when selecting a checked item', async () => {
		vi.useFakeTimers()
		const suggestion = { id: 5, title: 'Butter', checked: true }
		itemsApi.suggest.mockResolvedValue([suggestion])

		const w = mountInput()
		await w.find('input[type="text"]').setValue('Bu')
		vi.advanceTimersByTime(200)
		await vi.runAllTimersAsync()

		await w.find('.item-input__suggestion').trigger('mousedown')
		expect(w.emitted('select-suggestion')).toHaveLength(1)
		expect(w.emitted('select-suggestion')[0][0]).toEqual(suggestion)
		vi.useRealTimers()
	})
})
