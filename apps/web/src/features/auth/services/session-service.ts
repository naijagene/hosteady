import {
  fetchCurrentUser,
  fetchOrganizations,
  login as loginRequest,
  logout as logoutRequest,
} from '@/api/endpoints/auth'
import { fetchTenantContext } from '@/api/endpoints/tenant'
import type { LoginRequest, OrganizationSummary } from '@/api/types/auth'
import { resetSession } from '@/features/auth/core/session-reset'
import { hydrateRuntimeBundle } from '@/features/runtime/services/hydrate-runtime'
import { useAuthStore } from '@/stores/auth-store'

function resolveDefaultOrganization(
  organizations: OrganizationSummary[],
  preferredPublicId?: string | null,
): OrganizationSummary | null {
  if (organizations.length === 0) {
    return null
  }

  if (preferredPublicId) {
    const preferred = organizations.find(
      (organization) => organization.public_id === preferredPublicId,
    )

    if (preferred) {
      return preferred
    }
  }

  return organizations[0] ?? null
}

export async function performLogin(payload: LoginRequest): Promise<void> {
  const store = useAuthStore.getState()
  store.setPhase('authenticating')
  store.setLoading(true)
  store.setErrorMessage(null)

  try {
    const response = await loginRequest(payload)

    store.setAuthSession({
      accessToken: response.token,
      refreshToken: null,
      expiresAt: response.expires_at,
      user: response.user,
      rememberMe: payload.remember ?? true,
    })

    await bootstrapTenantSession()
  } finally {
    store.setLoading(false)
  }
}

export async function restoreAuthenticatedSession(): Promise<void> {
  const store = useAuthStore.getState()

  if (!store.accessToken) {
    return
  }

  const user = await fetchCurrentUser()
  store.setUser(user)

  await bootstrapTenantSession()
}

export async function bootstrapTenantSession(): Promise<void> {
  const store = useAuthStore.getState()
  store.setPhase('bootstrapping')
  store.setErrorMessage(null)

  const organizations = await fetchOrganizations()
  store.setOrganizations(organizations)

  const preferredOrganizationId =
    store.organizationPublicId ??
    store.organization?.public_id ??
    null

  const organization = resolveDefaultOrganization(
    organizations,
    preferredOrganizationId,
  )

  if (!organization) {
    throw new Error('No active organization memberships were found.')
  }

  store.setOrganization(organization)
  store.setMembership(organization.membership)

  if (store.workspacePublicId && !store.workspace) {
    store.setWorkspace({
      public_id: store.workspacePublicId,
      name: '',
      slug: '',
      is_default: false,
      status: 'active',
    })
  }

  const tenantContext = await fetchTenantContext()

  store.setUser(tenantContext.user)
  store.setOrganization(tenantContext.organization)
  store.setMembership(tenantContext.membership)
  store.setWorkspace(tenantContext.workspace)
  store.setWorkspaces([tenantContext.workspace])
  store.setPermissions(tenantContext.permissions)

  await hydrateApplicationRuntime()
}

export async function hydrateApplicationRuntime(): Promise<void> {
  const store = useAuthStore.getState()

  if (!store.organization?.public_id) {
    throw new Error('Organization scope is required before runtime hydration.')
  }

  store.setPhase('hydrating')

  const runtime = await hydrateRuntimeBundle()

  store.setHydratedRuntime(runtime)
  store.setPermissions(runtime.permissions)
  store.setRoles(runtime.roles)
  if (runtime.user) {
    store.setUser(runtime.user)
  }
  store.setOrganization(runtime.organization)
  store.setMembership(runtime.membership)
  store.setWorkspace(runtime.workspace)
  store.setApplication(runtime.application)
  store.setPhase('ready')
  store.setErrorMessage(null)
}

export async function switchWorkspace(workspacePublicId: string): Promise<void> {
  const store = useAuthStore.getState()
  const target = store.workspaces.find(
    (workspace) => workspace.public_id === workspacePublicId,
  )

  if (!target) {
    throw new Error('Workspace is not available for the current organization.')
  }

  store.setSwitchingWorkspace(true)

  try {
    store.setWorkspace(target)
    await hydrateApplicationRuntime()
  } finally {
    store.setSwitchingWorkspace(false)
  }
}

export async function switchOrganization(
  organizationPublicId: string,
): Promise<void> {
  const store = useAuthStore.getState()
  const organization = store.organizations.find(
    (entry) => entry.public_id === organizationPublicId,
  )

  if (!organization) {
    throw new Error('Organization is not available for the current user.')
  }

  store.setOrganization(organization)
  store.setMembership(organization.membership)
  store.setWorkspace(null)
  store.setHydratedRuntime(null)

  await bootstrapTenantSession()
}

export async function performLogout(): Promise<void> {
  try {
    if (useAuthStore.getState().accessToken) {
      await logoutRequest()
    }
  } catch {
    // Logout should remain idempotent even if the token is already invalid.
  } finally {
    await resetSession()
  }
}
