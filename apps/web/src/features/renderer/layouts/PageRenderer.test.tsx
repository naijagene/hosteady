import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { PageRenderer } from '@/features/renderer/layouts/PageRenderer'
import type { UiRenderPayload } from '@/api/types/ui'

import '@/features/renderer/register-default-components'

const payload: UiRenderPayload = {
  page: {
    page_key: 'home',
    name: 'Home Page',
    description: 'Welcome',
    module_key: 'platform',
  },
  layout: {
    layout_key: 'default',
    name: 'Default',
    layout_type: 'single_column',
    regions: [
      {
        region_key: 'main',
        region_type: 'content',
        label: 'Main',
        sort_order: 0,
        components: [],
      },
    ],
  },
  regions: [
    {
      region_key: 'main',
      region_type: 'content',
      label: 'Main',
      sort_order: 0,
      components: [
        {
          public_id: 'cmp-1',
          component_key: 'welcome',
          name: 'Welcome text',
          component_type: 'static_text',
          metadata: { text: 'Hello' },
        },
      ],
    },
  ],
  components: [
    {
      public_id: 'cmp-1',
      component_key: 'welcome',
      name: 'Welcome text',
      component_type: 'static_text',
      region_key: 'main',
      metadata: { text: 'Hello' },
    },
  ],
  actions: [{ action_key: 'refresh', label: 'Refresh' }],
  conditions: [{ condition_key: 'visible', field: 'status', operator: 'eq', value: 'active' }],
  breakpoints: [],
  theme: {},
  personalization: {},
  permissions: [],
  runtime_context: {},
}

describe('PageRenderer', () => {
  it('renders loading state', () => {
    render(<PageRenderer payload={null} loading />)
    expect(screen.getByTestId('page-renderer-loading')).toBeInTheDocument()
  })

  it('renders error state', () => {
    render(<PageRenderer payload={null} error={new Error('Failed')} />)
    expect(screen.getByTestId('page-renderer-error')).toHaveTextContent('Failed')
  })

  it('renders empty state', () => {
    render(
      <PageRenderer
        payload={{
          ...payload,
          page: { ...payload.page, page_key: '' },
          components: [],
          regions: [],
        }}
      />,
    )
    expect(screen.getByTestId('page-renderer-empty')).toBeInTheDocument()
  })

  it('renders page metadata', () => {
    render(<PageRenderer payload={payload} runtimePermissions={['platform.read']} />)

    expect(screen.getByTestId('page-renderer')).toBeInTheDocument()
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Home Page')
    expect(screen.getByTestId('page-actions')).toBeInTheDocument()
  })

  it('handles malformed metadata without crashing', () => {
    render(
      <PageRenderer
        payload={{
          ...(payload as UiRenderPayload),
          layout: { layout_key: '', name: '', layout_type: 'custom' },
          regions: [
            {
              region_key: '',
              region_type: 'unknown',
              label: '',
              sort_order: Number.NaN,
              components: ['missing-id'],
            },
          ],
        }}
      />,
    )

    expect(screen.getByTestId('page-renderer')).toBeInTheDocument()
  })

  it('hides page when permission missing', () => {
    render(
      <PageRenderer
        payload={{
          ...payload,
          page: { ...payload.page, permission: 'admin.only' },
        }}
        runtimePermissions={[]}
      />,
    )

    expect(screen.getByTestId('page-renderer-restricted')).toBeInTheDocument()
  })

  it('renders when page permission satisfied', () => {
    render(
      <PageRenderer
        payload={{
          ...payload,
          page: { ...payload.page, permission: 'platform.read' },
        }}
        runtimePermissions={['platform.read']}
      />,
    )

    expect(screen.getByTestId('page-renderer')).toBeInTheDocument()
  })
})
