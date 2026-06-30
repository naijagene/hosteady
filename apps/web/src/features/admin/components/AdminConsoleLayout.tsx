import type { ReactNode } from 'react'
import { AdminNav } from './AdminNav'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'

interface AdminConsoleLayoutProps {
  title: string
  description?: string
  children: ReactNode
}

export function AdminConsoleLayout({ title, description, children }: AdminConsoleLayoutProps) {
  const runtime = useHydratedRuntime()

  return (
    <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 lg:flex-row" data-testid="admin-console-layout">
      <aside className="lg:w-56">
        <div className="rounded-lg border border-border bg-card p-4">
          <h1 className="text-base font-semibold text-foreground">Administration</h1>
          <p className="mt-1 text-xs text-muted-foreground">Platform settings and diagnostics</p>
          <div className="mt-4">
            <AdminNav permissions={runtime?.permissions ?? []} />
          </div>
        </div>
      </aside>
      <main className="min-w-0 flex-1 space-y-4">
        <div>
          <h2 className="text-2xl font-semibold text-foreground">{title}</h2>
          {description ? <p className="mt-1 text-sm text-muted-foreground">{description}</p> : null}
        </div>
        {children}
      </main>
    </div>
  )
}
