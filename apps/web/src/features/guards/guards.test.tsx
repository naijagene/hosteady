import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { AuthGuard } from '@/features/guards/AuthGuard'
import { GuestGuard } from '@/features/guards/GuestGuard'
import { PermissionGuard } from '@/features/guards/PermissionGuard'
import { WorkspaceGuard } from '@/features/guards/WorkspaceGuard'
import { sanitizeRedirectTarget } from '@/features/auth/core/redirect-sanitize'
import { useAuthStore } from '@/stores/auth-store'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>(
    '@tanstack/react-router',
  )

  return {
    ...actual,
    Navigate: ({ to, search }: { to: string; search?: { redirect?: string } }) => (
      <div>
        navigate:{to}
        {search?.redirect ? `:redirect=${search.redirect}` : ''}
      </div>
    ),
    useRouterState: () => ({ location: { pathname: '/login' } }),
    useNavigate: () => vi.fn(),
  }
})

describe('AuthGuard', () => {
  beforeEach(() => useAuthStore.getState().clearAuth())

  it('redirects guests to login without redirect loop', () => {
    render(
      <AuthGuard>
        <div>Protected</div>
      </AuthGuard>,
    )

    expect(screen.getByText('navigate:/login')).toBeInTheDocument()
    expect(screen.queryByText(':redirect=/login')).not.toBeInTheDocument()
  })

  it('shows recovery UI when bootstrap failed', () => {
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
    useAuthStore.getState().setErrorMessage('Session restore failed')

    render(
      <AuthGuard>
        <div>Protected</div>
      </AuthGuard>,
    )

    expect(screen.getByText('Unable to initialize HEOS')).toBeInTheDocument()
    expect(screen.getByText('Session restore failed')).toBeInTheDocument()
  })

  it('renders children for authenticated users', () => {
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
    useAuthStore.getState().setPhase('ready')

    render(
      <AuthGuard>
        <div>Protected</div>
      </AuthGuard>,
    )

    expect(screen.getByText('Protected')).toBeInTheDocument()
  })
})

describe('GuestGuard', () => {
  beforeEach(() => useAuthStore.getState().clearAuth())

  it('renders login content for guests', () => {
    render(
      <GuestGuard>
        <div>Login form</div>
      </GuestGuard>,
    )

    expect(screen.getByText('Login form')).toBeInTheDocument()
  })

  it('redirects authenticated users away from guest routes', () => {
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
    useAuthStore.getState().setPhase('ready')

    render(
      <GuestGuard>
        <div>Login form</div>
      </GuestGuard>,
    )

    expect(screen.getByText('navigate:/')).toBeInTheDocument()
  })
})

describe('PermissionGuard', () => {
  beforeEach(() => useAuthStore.getState().clearAuth())

  it('blocks missing permissions', () => {
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
    useAuthStore.getState().setPermissions(['audit.read'])

    render(
      <PermissionGuard permission="forms.read">
        <div>Restricted</div>
      </PermissionGuard>,
    )

    expect(screen.getByText('navigate:/forbidden')).toBeInTheDocument()
  })

  it('allows administration permissions required by admin routes', () => {
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
    useAuthStore.getState().setPermissions([
      'platform.read',
      'permissions.read',
      'runtime.read',
      'applications.read',
      'roles.read',
    ])

    render(
      <PermissionGuard permission="platform.read">
        <div>Admin console</div>
      </PermissionGuard>,
    )

    expect(screen.getByText('Admin console')).toBeInTheDocument()
  })

  it('allows granted permissions', () => {
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
    useAuthStore.getState().setPermissions(['audit.read'])

    render(
      <PermissionGuard permission="audit.read">
        <div>Restricted</div>
      </PermissionGuard>,
    )

    expect(screen.getByText('Restricted')).toBeInTheDocument()
  })
})

describe('WorkspaceGuard', () => {
  beforeEach(() => useAuthStore.getState().clearAuth())

  it('shows loading while workspace is resolving', () => {
    useAuthStore.getState().setPhase('bootstrapping')

    render(
      <WorkspaceGuard>
        <div>Workspace app</div>
      </WorkspaceGuard>,
    )

    expect(screen.getByText('Loading workspace…')).toBeInTheDocument()
  })

  it('shows recovery instead of redirecting to loading trap', () => {
    useAuthStore.getState().setPhase('ready')

    render(
      <WorkspaceGuard>
        <div>Workspace app</div>
      </WorkspaceGuard>,
    )

    expect(screen.getByText('Unable to initialize HEOS')).toBeInTheDocument()
    expect(screen.queryByText('navigate:/loading')).not.toBeInTheDocument()
  })
})

describe('redirect sanitization', () => {
  it('does not create redirect=/login loops', () => {
    expect(sanitizeRedirectTarget('/login')).toBeUndefined()
    expect(sanitizeRedirectTarget('/forbidden')).toBeUndefined()
  })
})

describe('auth guard helpers', () => {
  beforeEach(() => useAuthStore.getState().clearAuth())

  it('detects missing tenant scope', () => {
    expect(useAuthStore.getState().hasTenantScope()).toBe(false)
  })
})
