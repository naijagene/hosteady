import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import type { FormDefinition } from '@/api/types/forms'
import { DynamicFormRenderer } from '@/features/forms/components/DynamicFormRenderer'
import * as formsApi from '@/api/endpoints/forms'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import { ApiError } from '@/api/errors'

const definition: FormDefinition = {
  module_key: 'platform',
  form_key: 'profile',
  name: 'Profile Form',
  description: 'Update profile',
  sections: [{ section_key: 'main', label: 'Main', fields: ['name', 'email'] }],
  fields: [
    {
      field_key: 'name',
      label: 'Name',
      field_type: 'text',
      required: true,
      section_key: 'main',
    },
    {
      field_key: 'email',
      label: 'Email',
      field_type: 'email',
      section_key: 'main',
      validation_rules: [{ field: 'email', rule: 'email' }],
    },
  ],
}

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: null,
  navigationMenus: [],
  permissions: ['profile.write'],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 0,
  warnings: [],
  source: 'runtime',
}

function renderForm(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  useAuthStore.getState().setHydratedRuntime(runtime)

  return render(
    <QueryClientProvider client={client}>
      <HydratedRuntimeProvider>{ui}</HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('DynamicFormRenderer', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('renders form fields', () => {
    renderForm(<DynamicFormRenderer definition={definition} />)
    expect(screen.getByTestId('dynamic-form-renderer')).toBeInTheDocument()
    expect(screen.getByLabelText(/Name/)).toBeInTheDocument()
    expect(screen.getByLabelText(/Email/)).toBeInTheDocument()
  })

  it('shows loading state via binding renderer separately', () => {
    renderForm(<DynamicFormRenderer definition={definition} mode="readonly" />)
    expect(screen.getByText('This form is read-only.')).toBeInTheDocument()
  })

  it('validates required fields on submit', async () => {
    renderForm(<DynamicFormRenderer definition={definition} />)
    await userEvent.click(screen.getByRole('button', { name: 'Submit' }))
    await waitFor(() => {
      expect(screen.getByTestId('form-error-summary')).toBeInTheDocument()
    })
    expect(screen.getByText('Name is required.')).toBeInTheDocument()
  })

  it('submits successfully', async () => {
    vi.spyOn(formsApi, 'submitForm').mockResolvedValue({
      module_key: 'platform',
      form_key: 'profile',
      success: true,
      entity_public_id: 'entity-1',
    })

    renderForm(<DynamicFormRenderer definition={definition} />)
    await userEvent.type(screen.getByLabelText(/Name/), 'Ada')
    await userEvent.type(screen.getByLabelText(/Email/), 'ada@example.com')
    await userEvent.click(screen.getByRole('button', { name: 'Submit' }))

    await waitFor(() => {
      expect(screen.getByTestId('form-success-state')).toBeInTheDocument()
    })
    expect(screen.getByText(/Reference: entity-1/)).toBeInTheDocument()
  })

  it('maps backend validation errors', async () => {
    vi.spyOn(formsApi, 'submitForm').mockRejectedValue(
      new ApiError('Validation failed', {
        kind: 'validation',
        status: 422,
        body: {
          message: 'Validation failed',
          errors: { email: ['The email field is invalid.'] },
        },
      }),
    )

    renderForm(<DynamicFormRenderer definition={definition} />)
    await userEvent.type(screen.getByLabelText(/Name/), 'Ada')
    await userEvent.type(screen.getByLabelText(/Email/), 'ada@example.com')
    await userEvent.click(screen.getByRole('button', { name: 'Submit' }))

    await waitFor(() => {
      expect(screen.getByTestId('form-error-summary')).toBeInTheDocument()
    })
    expect(screen.getByText(/email: The email field is invalid/)).toBeInTheDocument()
  })

  it('shows unexpected submission error', async () => {
    vi.spyOn(formsApi, 'submitForm').mockRejectedValue(new Error('Server exploded'))

    renderForm(<DynamicFormRenderer definition={definition} />)
    await userEvent.type(screen.getByLabelText(/Name/), 'Ada')
    await userEvent.type(screen.getByLabelText(/Email/), 'ada@example.com')
    await userEvent.click(screen.getByRole('button', { name: 'Submit' }))

    await waitFor(() => {
      expect(screen.getByText('Server exploded')).toBeInTheDocument()
    })
  })

  it('disables submit while submitting', async () => {
    vi.spyOn(formsApi, 'submitForm').mockImplementation(
      () =>
        new Promise((resolve) => {
          setTimeout(
            () =>
              resolve({
                module_key: 'platform',
                form_key: 'profile',
                success: true,
              }),
            100,
          )
        }),
    )

    renderForm(<DynamicFormRenderer definition={definition} />)
    await userEvent.type(screen.getByLabelText(/Name/), 'Ada')
    await userEvent.type(screen.getByLabelText(/Email/), 'ada@example.com')
    await userEvent.click(screen.getByRole('button', { name: 'Submit' }))
    expect(screen.getByRole('button', { name: 'Submitting…' })).toBeDisabled()
  })
})
