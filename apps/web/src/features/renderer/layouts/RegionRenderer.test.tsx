import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { RendererContextProvider } from '@/features/renderer/core/RendererContext'
import { RegionRenderer } from '@/features/renderer/layouts/RegionRenderer'
import type { UiRenderPayload } from '@/api/types/ui'
import '@/features/renderer/register-default-components'

const payload: UiRenderPayload = {
  page: { page_key: 'home', name: 'Home' },
  layout: { layout_key: 'default', name: 'Default', layout_type: 'single_column' },
  regions: [],
  components: [
    {
      public_id: 'cmp-2',
      component_key: 'second',
      name: 'Second',
      component_type: 'static_text',
      region_key: 'main',
      sort_order: 2,
      metadata: { text: 'Second' },
    },
    {
      public_id: 'cmp-1',
      component_key: 'first',
      name: 'First',
      component_type: 'static_text',
      region_key: 'main',
      sort_order: 1,
      metadata: { text: 'First' },
    },
  ],
  actions: [],
  conditions: [],
  breakpoints: [],
  theme: {},
  personalization: {},
  permissions: [],
  runtime_context: {},
}

describe('RegionRenderer', () => {
  it('renders ordered components', () => {
    render(
      <RendererContextProvider payload={payload} devMode>
        <RegionRenderer
          region={{
            region_key: 'main',
            region_type: 'content',
            label: 'Main',
            sort_order: 0,
            components: [],
          }}
        />
      </RendererContextProvider>,
    )

    expect(screen.getByText('First')).toBeInTheDocument()
    expect(screen.getByText('Second')).toBeInTheDocument()
  })

  it('shows empty region placeholder in dev mode', () => {
    render(
      <RendererContextProvider payload={payload} devMode>
        <RegionRenderer
          region={{
            region_key: 'aside',
            region_type: 'aside',
            label: 'Aside',
            sort_order: 0,
            components: [],
          }}
        />
      </RendererContextProvider>,
    )

    expect(screen.getByTestId('empty-region-placeholder')).toBeInTheDocument()
  })

  it('hides empty region placeholder outside dev mode', () => {
    render(
      <RendererContextProvider payload={payload} devMode={false}>
        <RegionRenderer
          region={{
            region_key: 'aside',
            region_type: 'aside',
            label: 'Aside',
            sort_order: 0,
            components: [],
          }}
        />
      </RendererContextProvider>,
    )

    expect(screen.queryByTestId('empty-region-placeholder')).not.toBeInTheDocument()
  })
})
