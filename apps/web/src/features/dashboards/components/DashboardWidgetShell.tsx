import type { ReactNode } from 'react'

interface DashboardWidgetShellProps {
  title: string
  description?: string | null
  collapsed?: boolean
  children: ReactNode
}

export function DashboardWidgetShell({
  title,
  description,
  collapsed = false,
  children,
}: DashboardWidgetShellProps) {
  return (
    <section
      className="h-full rounded-lg border border-border bg-card p-4"
      data-testid="dashboard-widget-shell"
      aria-label={title}
    >
      {collapsed ? (
        <p className="text-xs text-muted-foreground">{title} (collapsed)</p>
      ) : (
        <>
          {description ? (
            <p className="mb-2 text-xs text-muted-foreground">{description}</p>
          ) : null}
          {children}
        </>
      )}
    </section>
  )
}
