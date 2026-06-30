import { describe, expect, it } from 'vitest'
import { renderHook, act } from '@testing-library/react'
import { usePermissionBrowser } from './usePermissionBrowser'

describe('usePermissionBrowser', () => {
  it('returns all permissions when search is empty', () => {
    const { result } = renderHook(() => usePermissionBrowser(['platform.read', 'documents.read']))
    expect(result.current.items).toHaveLength(2)
    expect(Object.keys(result.current.groups).length).toBeGreaterThan(0)
  })

  it('filters permissions by search term', () => {
    const { result } = renderHook(() => usePermissionBrowser(['platform.read', 'documents.read', 'reports.read']))
    act(() => {
      result.current.setSearch('doc')
    })
    expect(result.current.items).toHaveLength(1)
    expect(result.current.items[0].permission).toBe('documents.read')
  })

  it('groups filtered permissions by category', () => {
    const { result } = renderHook(() => usePermissionBrowser(['platform.read', 'settings.read']))
    act(() => {
      result.current.setSearch('platform')
    })
    expect(result.current.groups.platform).toHaveLength(1)
  })

  it('returns empty groups when search matches nothing', () => {
    const { result } = renderHook(() => usePermissionBrowser(['platform.read']))
    act(() => {
      result.current.setSearch('missing-permission')
    })
    expect(result.current.items).toHaveLength(0)
    expect(Object.keys(result.current.groups)).toHaveLength(0)
  })
})
