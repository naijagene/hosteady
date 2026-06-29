import { useMemo, useState } from 'react'
import { switchWorkspace } from '@/features/auth/services/session-service'
import { useAuthStore } from '@/stores/auth-store'
import { Spinner } from '@/components/loading/Spinner'

export function WorkspaceSwitcher() {
  const workspaces = useAuthStore((state) => state.workspaces)
  const workspace = useAuthStore((state) => state.workspace)
  const organization = useAuthStore((state) => state.organization)
  const switchingWorkspace = useAuthStore((state) => state.switchingWorkspace)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  const label = useMemo(() => {
    if (workspace?.name) {
      return workspace.name
    }

    return organization?.name ?? 'Workspace'
  }, [organization?.name, workspace?.name])

  if (workspaces.length <= 1) {
    return (
      <div className="text-sm font-medium text-primary-foreground/90">{label}</div>
    )
  }

  return (
    <div className="flex items-center gap-2">
      <label className="sr-only" htmlFor="workspace-switcher">
        Workspace
      </label>
      <select
        id="workspace-switcher"
        className="rounded-md border border-primary-foreground/20 bg-primary px-2 py-1 text-sm text-primary-foreground"
        value={workspace?.public_id ?? ''}
        disabled={switchingWorkspace}
        onChange={async (event) => {
          setErrorMessage(null)

          try {
            await switchWorkspace(event.target.value)
          } catch (error) {
            setErrorMessage(
              error instanceof Error ? error.message : 'Unable to switch workspace.',
            )
          }
        }}
      >
        {workspaces.map((entry) => (
          <option key={entry.public_id} value={entry.public_id}>
            {entry.name || entry.public_id}
          </option>
        ))}
      </select>
      {switchingWorkspace ? <Spinner className="h-4 w-4 border-primary-foreground" /> : null}
      {errorMessage ? (
        <span className="text-xs text-primary-foreground/80">{errorMessage}</span>
      ) : null}
    </div>
  )
}
