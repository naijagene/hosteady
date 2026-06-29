import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface SessionState {
  organizationPublicId: string | null
  workspacePublicId: string | null
  applicationPublicId: string | null
  setOrganizationPublicId: (organizationPublicId: string | null) => void
  setWorkspacePublicId: (workspacePublicId: string | null) => void
  setApplicationPublicId: (applicationPublicId: string | null) => void
  setTenantScope: (scope: {
    organizationPublicId?: string | null
    workspacePublicId?: string | null
    applicationPublicId?: string | null
  }) => void
  clearTenantScope: () => void
}

export const useSessionStore = create<SessionState>()(
  persist(
    (set) => ({
      organizationPublicId: null,
      workspacePublicId: null,
      applicationPublicId: null,
      setOrganizationPublicId: (organizationPublicId) =>
        set({ organizationPublicId }),
      setWorkspacePublicId: (workspacePublicId) => set({ workspacePublicId }),
      setApplicationPublicId: (applicationPublicId) =>
        set({ applicationPublicId }),
      setTenantScope: (scope) =>
        set((state) => ({
          organizationPublicId:
            scope.organizationPublicId ?? state.organizationPublicId,
          workspacePublicId:
            scope.workspacePublicId ?? state.workspacePublicId,
          applicationPublicId:
            scope.applicationPublicId ?? state.applicationPublicId,
        })),
      clearTenantScope: () =>
        set({
          organizationPublicId: null,
          workspacePublicId: null,
          applicationPublicId: null,
        }),
    }),
    {
      name: 'heos-session',
    },
  ),
)
