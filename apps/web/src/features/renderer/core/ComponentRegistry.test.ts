import { describe, expect, it, beforeEach } from 'vitest'
import {
  clearRegistryForTests,
  getRegistrySize,
  has,
  register,
  resolve,
} from '@/features/renderer/core/ComponentRegistry'
import { UnknownComponent } from '@/features/renderer/components/UnknownComponent'
import { StaticTextComponent } from '@/features/renderer/components/StaticTextComponent'

describe('ComponentRegistry', () => {
  beforeEach(() => {
    clearRegistryForTests()
  })

  it('registers and resolves a component type', () => {
    register('static_text', StaticTextComponent)

    expect(has('static_text')).toBe(true)
    expect(resolve('static_text')).toBe(StaticTextComponent)
  })

  it('normalizes type keys to lowercase', () => {
    register('Card', StaticTextComponent)

    expect(has('card')).toBe(true)
    expect(resolve('CARD')).toBe(StaticTextComponent)
  })

  it('returns undefined for unknown types', () => {
    expect(resolve('missing')).toBeUndefined()
    expect(has('missing')).toBe(false)
  })

  it('ignores empty type registration', () => {
    register('', StaticTextComponent)

    expect(getRegistrySize()).toBe(0)
  })

  it('allows extensible registration', () => {
    register('custom_widget', UnknownComponent)
    register('custom_widget', StaticTextComponent)

    expect(resolve('custom_widget')).toBe(StaticTextComponent)
  })

  it('tracks registry size', () => {
    register('a', StaticTextComponent)
    register('b', UnknownComponent)

    expect(getRegistrySize()).toBe(2)
  })

  it('clears registry for tests', () => {
    register('temp', StaticTextComponent)
    clearRegistryForTests()

    expect(getRegistrySize()).toBe(0)
  })

  it('resolved components accept UiComponent props shape', () => {
    register('static_text', StaticTextComponent)
    const Component = resolve('static_text')

    expect(Component).toBeDefined()
  })
})
