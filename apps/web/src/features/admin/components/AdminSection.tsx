import type { ReactNode } from 'react'

interface AdminSectionProps {
  title: string
  description?: string
  children: ReactNode
}

export function AdminSection({ title, description, children }: AdminSectionProps) {
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid={`admin-section-${title.toLowerCase().replace(/\s+/g, '-')}`}>
      <div className="mb-3">
        <h2 className="text-sm font-semibold text-foreground">{title}</h2>
        {description ? <p className="mt-1 text-xs text-muted-foreground">{description}</p> : null}
      </div>
      {children}
    </section>
  )
}
