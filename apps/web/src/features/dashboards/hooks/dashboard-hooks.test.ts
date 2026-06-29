import { describe, expect, it, vi } from 'vitest'
import { act, renderHook } from '@testing-library/react'
import type { DashboardAction } from '@/api/types/dashboards'
import { useDashboardActions } from '@/features/dashboards/hooks/useDashboardActions'
import { useDashboardFilters } from '@/features/dashboards/hooks/useDashboardFilters'

describe('dashboard hooks', () => {
  it('tracks filter values locally', () => {
    const { result } = renderHook(() =>
      useDashboardFilters([
        { filter_key: 'status', label: 'Status', filter_type: 'text', value: 'open' },
      ]),
    )

    act(() => {
      result.current.setFilterValue('status', 'closed')
    })

    expect(result.current.values.status).toBe('closed')
    expect(result.current.serializedFilters[0]?.value).toBe('closed')
  })

  it('clears filter values', () => {
    const { result } = renderHook(() =>
      useDashboardFilters([{ filter_key: 'q', label: 'Search', filter_type: 'text' }]),
    )

    act(() => {
      result.current.setFilterValue('q', 'abc')
      result.current.clearFilters()
    })

    expect(result.current.values.q).toBe('')
  })

  it('handles refresh and placeholder actions', () => {
    const onRefresh = vi.fn()
    const { result } = renderHook(() => useDashboardActions({ onRefresh }))

    act(() => {
      result.current.handleAction({
        action_key: 'refresh',
        label: 'Refresh',
        action_type: 'refresh',
      })
    })
    expect(onRefresh).toHaveBeenCalled()

    act(() => {
      result.current.handleAction({
        action_key: 'export',
        label: 'Export',
        action_type: 'export',
      } satisfies DashboardAction)
    })
    expect(result.current.message).toContain('Export')
  })
})
