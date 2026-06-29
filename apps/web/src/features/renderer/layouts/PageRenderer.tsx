import type { UiRenderPayload } from '@/api/types/ui'
import { ErrorBoundary } from '@/components/errors/ErrorBoundary'
import { RendererContextProvider } from '../core/RendererContext'
import { isUiRenderPayloadEmpty } from '@/api/types/ui'
import { hasPermission, mergePermissions } from '../core/renderer-utils'
import { LayoutRenderer } from './LayoutRenderer'

interface PageRendererProps {
  payload: UiRenderPayload | null | undefined
  loading?: boolean
  error?: Error | null
  runtimePermissions?: string[]
  moduleKey?: string
  pageKey?: string
}

export function PageRenderer({
  payload,
  loading = false,
  error = null,
  runtimePermissions = [],
  moduleKey,
  pageKey,
}: PageRendererProps) {
  if (loading) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="page-renderer-loading">
        Loading page metadata…
      </div>
    )
  }

  if (error) {
    return (
      <div
        className="rounded-md border border-border bg-card p-4 text-sm text-muted-foreground"
        data-testid="page-renderer-error"
      >
        Unable to render page: {error.message}
      </div>
    )
  }

  if (!payload || isUiRenderPayloadEmpty(payload)) {
    return (
      <div
        className="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
        data-testid="page-renderer-empty"
      >
        Page metadata is empty or unavailable.
      </div>
    )
  }

  const permissions = mergePermissions(runtimePermissions, payload.permissions)
  const pageAllowed = hasPermission(permissions, payload.page.permission)

  if (!pageAllowed) {
    return (
      <div
        className="rounded-md border border-border bg-card p-6 text-sm text-muted-foreground"
        data-testid="page-renderer-restricted"
      >
        You do not have permission to view this page.
      </div>
    )
  }

  return (
    <RendererContextProvider
      permissions={permissions}
      moduleKey={moduleKey ?? payload.page.module_key}
      pageKey={pageKey ?? payload.page.page_key}
      payload={payload}
    >
      <article className="space-y-6" data-testid="page-renderer">
        <header className="space-y-1 border-b border-border pb-4">
          <h1 className="text-xl font-semibold text-foreground">{payload.page.name}</h1>
          {payload.page.description ? (
            <p className="text-sm text-muted-foreground">{payload.page.description}</p>
          ) : null}
        </header>

        {payload.actions.length > 0 ? (
          <div className="flex flex-wrap gap-2" data-testid="page-actions">
            {payload.actions.map((action) => (
              <span
                key={action.action_key}
                className="rounded-md border border-border px-3 py-1 text-xs text-muted-foreground"
              >
                {action.label}
              </span>
            ))}
          </div>
        ) : null}

        {payload.conditions.length > 0 ? (
          <div className="hidden" data-testid="page-conditions" aria-hidden>
            {payload.conditions.map((condition) => (
              <span key={condition.condition_key}>{condition.condition_key}</span>
            ))}
          </div>
        ) : null}

        <ErrorBoundary>
          <LayoutRenderer layout={payload.layout} regions={payload.regions} />
        </ErrorBoundary>
      </article>
    </RendererContextProvider>
  )
}
