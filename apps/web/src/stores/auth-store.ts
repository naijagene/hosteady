import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type {
  ApplicationSummary,
  AuthUser,
  MembershipSummary,
  OrganizationSummary,
  WorkspaceSummary,
} from '@/api/types/auth'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import { BootstrapError } from '@/features/auth/core/bootstrap-error'

export type AuthBootstrapPhase =
  | 'idle'
  | 'restoring'
  | 'authenticating'
  | 'bootstrapping'
  | 'hydrating'
  | 'ready'
  | 'error'

interface AuthState {
  accessToken: string | null
  refreshToken: string | null
  expiresAt: string | null
  rememberMe: boolean
  user: AuthUser | null
  membership: MembershipSummary | null
  organization: OrganizationSummary | null
  workspace: WorkspaceSummary | null
  application: ApplicationSummary | null
  organizations: OrganizationSummary[]
  workspaces: WorkspaceSummary[]
  permissions: string[]
  roles: string[]
  hydratedRuntime: HydratedRuntimeBundle | null
  phase: AuthBootstrapPhase
  loading: boolean
  errorMessage: string | null
  switchingWorkspace: boolean
  organizationPublicId: string | null
  workspacePublicId: string | null
  setAuthSession: (session: {
    accessToken: string
    refreshToken?: string | null
    expiresAt: string
    user: AuthUser
    rememberMe?: boolean
  }) => void
  setUser: (user: AuthUser) => void
  setOrganizations: (organizations: OrganizationSummary[]) => void
  setOrganization: (organization: OrganizationSummary | null) => void
  setWorkspace: (workspace: WorkspaceSummary | null) => void
  setWorkspaces: (workspaces: WorkspaceSummary[]) => void
  setApplication: (application: ApplicationSummary | null) => void
  setMembership: (membership: MembershipSummary | null) => void
  setPermissions: (permissions: string[]) => void
  setRoles: (roles: string[]) => void
  setHydratedRuntime: (runtime: HydratedRuntimeBundle | null) => void
  setPhase: (phase: AuthBootstrapPhase) => void
  setLoading: (loading: boolean) => void
  setErrorMessage: (errorMessage: string | null) => void
  setSwitchingWorkspace: (switchingWorkspace: boolean) => void
  isAuthenticated: () => boolean
  isSessionExpired: () => boolean
  hasTenantScope: () => boolean
  hasPermission: (permission: string) => boolean
  restore: () => Promise<void>
  retryBootstrap: () => Promise<void>
  logout: () => Promise<void>
  clearAuth: () => void
}

const initialState = {
  accessToken: null,
  refreshToken: null,
  expiresAt: null,
  rememberMe: true,
  user: null,
  membership: null,
  organization: null,
  workspace: null,
  application: null,
  organizations: [] as OrganizationSummary[],
  workspaces: [] as WorkspaceSummary[],
  permissions: [] as string[],
  roles: [] as string[],
  hydratedRuntime: null as HydratedRuntimeBundle | null,
  phase: 'idle' as AuthBootstrapPhase,
  loading: false,
  errorMessage: null as string | null,
  switchingWorkspace: false,
  organizationPublicId: null as string | null,
  workspacePublicId: null as string | null,
}

let restorePromise: Promise<void> | null = null
let logoutPromise: Promise<void> | null = null

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      ...initialState,
      setAuthSession: ({
        accessToken,
        refreshToken = null,
        expiresAt,
        user,
        rememberMe = true,
      }) =>
        set({
          accessToken,
          refreshToken,
          expiresAt,
          user,
          rememberMe,
          errorMessage: null,
        }),
      setUser: (user) => set({ user }),
      setOrganizations: (organizations) => set({ organizations }),
      setOrganization: (organization) =>
        set({
          organization,
          organizationPublicId: organization?.public_id ?? null,
        }),
      setWorkspace: (workspace) =>
        set({
          workspace,
          workspacePublicId: workspace?.public_id ?? null,
        }),
      setWorkspaces: (workspaces) => set({ workspaces }),
      setApplication: (application) => set({ application }),
      setMembership: (membership) => set({ membership }),
      setPermissions: (permissions) => set({ permissions }),
      setRoles: (roles) => set({ roles }),
      setHydratedRuntime: (hydratedRuntime) => set({ hydratedRuntime }),
      setPhase: (phase) => set({ phase }),
      setLoading: (loading) => set({ loading }),
      setErrorMessage: (errorMessage) => set({ errorMessage }),
      setSwitchingWorkspace: (switchingWorkspace) => set({ switchingWorkspace }),
      isAuthenticated: () => {
        const state = get()
        return Boolean(state.accessToken) && !state.isSessionExpired()
      },
      isSessionExpired: () => {
        const { expiresAt } = get()
        if (!expiresAt) {
          return false
        }

        return Date.parse(expiresAt) <= Date.now()
      },
      hasTenantScope: () =>
        Boolean(get().organization?.public_id && get().workspace?.public_id),
      hasPermission: (permission) => get().permissions.includes(permission),
      restore: async () => {
        if (restorePromise) {
          return restorePromise
        }

        restorePromise = (async () => {
          const state = get()

          if (!state.accessToken || state.isSessionExpired()) {
            get().clearAuth()
            return
          }

          set({ phase: 'restoring', loading: true, errorMessage: null })

          try {
            const { restoreAuthenticatedSession } = await import(
              '@/features/auth/services/session-service'
            )
            await restoreAuthenticatedSession()
          } catch (error) {
            const bootstrapError = BootstrapError.fromUnknown(error)

            if (bootstrapError.kind === 'unauthorized') {
              get().clearAuth()
              return
            }

            set({
              phase: 'error',
              loading: false,
              errorMessage: bootstrapError.message,
            })
          } finally {
            set({ loading: false })
            restorePromise = null
          }
        })()

        return restorePromise
      },
      retryBootstrap: async () => {
        restorePromise = null
        const state = get()

        if (!state.accessToken || state.isSessionExpired()) {
          get().clearAuth()
          return
        }

        set({
          phase: 'idle',
          loading: false,
          errorMessage: null,
          hydratedRuntime: null,
        })

        await get().restore()
      },
      logout: async () => {
        if (logoutPromise) {
          return logoutPromise
        }

        logoutPromise = (async () => {
          try {
            const { performLogout } = await import(
              '@/features/auth/services/session-service'
            )
            await performLogout()
          } finally {
            logoutPromise = null
          }
        })()

        return logoutPromise
      },
      clearAuth: () => {
        restorePromise = null
        set({
          ...initialState,
          phase: 'idle',
        })
      },
    }),
    {
      name: 'heos-auth',
      partialize: (state) => ({
        accessToken: state.accessToken,
        refreshToken: state.refreshToken,
        expiresAt: state.expiresAt,
        rememberMe: state.rememberMe,
        organizationPublicId: state.organizationPublicId,
        workspacePublicId: state.workspacePublicId,
      }),
    },
  ),
)

export function selectAuthToken(state: AuthState): string | null {
  return state.accessToken
}
