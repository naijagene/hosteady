import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { FormBindingRenderer } from '@/features/renderer/bindings/FormBindingRenderer'
import { RendererContextProvider } from '@/features/renderer/core/RendererContext'
import * as formsApi from '@/api/endpoints/forms'
import type { UiComponent } from '@/api/types/ui'

const formComponent: UiComponent = {
  public_id: '1',
  component_key: 'profile-form',
  name: 'Profile',
  component_type: 'form',
  binding_type: 'form',
  binding_config: {
    module_key: 'platform',
    resource_key: 'profile',
    submit_enabled: true,
    success_message: 'Saved profile',
  },
}

function renderBinding() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={client}>
      <RendererContextProvider moduleKey="platform" pageKey="profile-page">
        <FormBindingRenderer component={formComponent} />
      </RendererContextProvider>
    </QueryClientProvider>,
  )
}

describe('FormBindingRenderer integration', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('renders dynamic form renderer', async () => {
    vi.spyOn(formsApi, 'fetchFormDefinition').mockResolvedValue({
      module_key: 'platform',
      form_key: 'profile',
      name: 'Profile Form',
      fields: [{ field_key: 'name', label: 'Name', field_type: 'text', required: true }],
      sections: [{ section_key: 'main', label: 'Main', fields: ['name'] }],
    })

    renderBinding()

    await waitFor(() => {
      expect(screen.getByTestId('form-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByTestId('dynamic-form-renderer')).toBeInTheDocument()
    expect(screen.getByLabelText(/Name/)).toBeInTheDocument()
  })

  it('shows loading state', () => {
    vi.spyOn(formsApi, 'fetchFormDefinition').mockImplementation(
      () => new Promise(() => undefined),
    )
    renderBinding()
    expect(screen.getByTestId('form-loading-state')).toBeInTheDocument()
  })
})
