import { describe, expect, it, vi } from 'vitest'
import { renderHook, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useUiPageRender } from '@/features/renderer/hooks/useUiPageRender'
import * as uiApi from '@/api/endpoints/ui'

function createWrapper() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
  )
}

describe('useUiPageRender', () => {
  it('loads page render payload', async () => {
    vi.spyOn(uiApi, 'fetchUiPageRender').mockResolvedValue({
      page: { page_key: 'home', name: 'Home', module_key: 'platform' },
      layout: { layout_key: 'default', name: 'Default', layout_type: 'single_column' },
      regions: [],
      components: [],
      actions: [],
      conditions: [],
      breakpoints: [],
      theme: {},
      personalization: {},
      permissions: [],
      runtime_context: {},
    })

    const { result } = renderHook(() => useUiPageRender('platform', 'home'), {
      wrapper: createWrapper(),
    })

    await waitFor(() => expect(result.current.isSuccess).toBe(true))
    expect(result.current.data?.page.name).toBe('Home')
  })

  it('does not fetch when params missing', () => {
    const spy = vi.spyOn(uiApi, 'fetchUiPageRender')

    renderHook(() => useUiPageRender('', ''), { wrapper: createWrapper() })

    expect(spy).not.toHaveBeenCalled()
  })
})
