import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { AuthGuard } from '@/features/guards/AuthGuard'
import { GuestGuard } from '@/features/guards/GuestGuard'
import { PermissionGuard } from '@/features/guards/PermissionGuard'
import { WorkspaceGuard } from '@/features/guards/WorkspaceGuard'
import { useAuthStore } from '@/stores/auth-store'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>(
    '@tanstack/react-router',
  )

  return {
    ...actual,
    Navigate: ({ to }: { to: string }) => <div>navigate:{to}</div>,
    useRouterState: () => ({ location: { pathname: '/protected' } }),
  }
})

describe('AuthGuard', () => {
  beforeEach(() => useAuthStore.getState().clearAuth())

  it('redirects guests to login', () => {
    render(
      <AuthGuard>
        <div>Protected</div>
      </AuthGuard>,
    )

    expect(screen.getByText('navigate:/login')).toBeInTheDocument()
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

  it('redirects when workspace scope is missing', () => {
    useAuthStore.getState().setPhase('ready')

    render(
      <WorkspaceGuard>
        <div>Workspace app</div>
      </WorkspaceGuard>,
    )

    expect(screen.getByText('navigate:/loading')).toBeInTheDocument()
  })
})

describe('auth guard helpers', () => {
  beforeEach(() => useAuthStore.getState().clearAuth())

  it('detects missing tenant scope', () => {
    expect(useAuthStore.getState().hasTenantScope()).toBe(false)
  })
})
