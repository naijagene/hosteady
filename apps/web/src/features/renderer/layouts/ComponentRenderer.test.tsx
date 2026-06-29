import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import type { ReactNode } from 'react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { RendererContextProvider } from '@/features/renderer/core/RendererContext'
import { ComponentRenderer } from '@/features/renderer/layouts/ComponentRenderer'
import { register, clearRegistryForTests } from '@/features/renderer/core/ComponentRegistry'
import { StaticTextComponent } from '@/features/renderer/components/StaticTextComponent'

function renderWithProviders(
  ui: ReactNode,
  options?: { permissions?: string[]; devMode?: boolean },
) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  return render(
    <QueryClientProvider client={client}>
      <RendererContextProvider
        permissions={options?.permissions ?? ['platform.read']}
        devMode={options?.devMode ?? true}
        moduleKey="platform"
      >
        {ui}
      </RendererContextProvider>
    </QueryClientProvider>,
  )
}

describe('ComponentRenderer', () => {
  it('renders registered component types', () => {
    register('static_text', StaticTextComponent)

    renderWithProviders(
      <ComponentRenderer
        component={{
          public_id: '1',
          component_key: 'text',
          name: 'Text',
          component_type: 'static_text',
          metadata: { text: 'Registered' },
        }}
      />,
    )

    expect(screen.getByTestId('static-text-component')).toHaveTextContent('Registered')
  })

  it('renders unknown fallback for unsupported types', () => {
    clearRegistryForTests()

    renderWithProviders(
      <ComponentRenderer
        component={{
          public_id: '1',
          component_key: 'x',
          name: 'Unknown',
          component_type: 'unsupported_type',
        }}
      />,
    )

    expect(screen.getByTestId('unknown-component')).toBeInTheDocument()
  })

  it('hides restricted components in production mode', () => {
    renderWithProviders(
      <ComponentRenderer
        component={{
          public_id: '1',
          component_key: 'secure',
          name: 'Secure',
          component_type: 'static_text',
          permission: 'admin.only',
        }}
      />,
      { permissions: [], devMode: false },
    )

    expect(screen.queryByTestId('static-text-component')).not.toBeInTheDocument()
  })

  it('shows restricted placeholder in dev mode', () => {
    renderWithProviders(
      <ComponentRenderer
        component={{
          public_id: '1',
          component_key: 'secure',
          name: 'Secure',
          component_type: 'static_text',
          permission: 'admin.only',
        }}
      />,
      { permissions: [], devMode: true },
    )

    expect(screen.getByTestId('restricted-component')).toHaveTextContent('Secure')
  })

  it('uses error boundary for failing components', () => {
    function BrokenComponent() {
      throw new Error('boom')
    }

    register('broken', BrokenComponent as unknown as typeof StaticTextComponent)

    renderWithProviders(
      <ComponentRenderer
        component={{
          public_id: '1',
          component_key: 'broken',
          name: 'Broken',
          component_type: 'broken',
        }}
      />,
    )

    expect(screen.getByTestId('component-error-boundary')).toBeInTheDocument()
  })

  it('returns null for missing component', () => {
    const { container } = renderWithProviders(<ComponentRenderer component={null} />)
    expect(container).toBeEmptyDOMElement()
  })
})
