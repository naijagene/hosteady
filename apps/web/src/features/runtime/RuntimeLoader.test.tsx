import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { RuntimeLoader } from '@/features/runtime/RuntimeLoader'
import { useAuthStore } from '@/stores/auth-store'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>(
    '@tanstack/react-router',
  )

  return {
    ...actual,
    useNavigate: () => vi.fn(),
  }
})

describe('RuntimeLoader', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
    vi.useRealTimers()
  })

  it('renders children for unauthenticated routes', () => {
    render(
      <RuntimeLoader>
        <div>Public content</div>
      </RuntimeLoader>,
    )

    expect(screen.getByText('Public content')).toBeInTheDocument()
  })

  it('shows recovery UI after bootstrap failure', () => {
    useAuthStore.getState().setAuthSession({
      accessToken: 'token',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
    })
    useAuthStore.getState().setPhase('error')
    useAuthStore.getState().setErrorMessage('Backend unavailable')

    render(
      <RuntimeLoader>
        <div>App content</div>
      </RuntimeLoader>,
    )

    expect(screen.getByText('Unable to initialize HEOS')).toBeInTheDocument()
    expect(screen.getByText('Backend unavailable')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Retry' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Reset Session' })).toBeInTheDocument()
  })

  it('does not stay on loading overlay when bootstrap enters error phase', () => {
    useAuthStore.getState().setAuthSession({
      accessToken: 'token',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
    })
    useAuthStore.getState().setPhase('bootstrapping')
    useAuthStore.getState().setLoading(true)

    const { rerender } = render(
      <RuntimeLoader>
        <div>App content</div>
      </RuntimeLoader>,
    )

    expect(screen.getByText('Preparing HEOS workspace…')).toBeInTheDocument()

    useAuthStore.getState().setPhase('error')
    useAuthStore.getState().setLoading(false)
    useAuthStore.getState().setErrorMessage('Failed to hydrate runtime')

    rerender(
      <RuntimeLoader>
        <div>App content</div>
      </RuntimeLoader>,
    )

    expect(screen.queryByText('Preparing HEOS workspace…')).not.toBeInTheDocument()
    expect(screen.getByText('Unable to initialize HEOS')).toBeInTheDocument()
  })
})
