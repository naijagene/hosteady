import type { ReactNode } from 'react'

interface AlphaHealthCardProps {
  title: string
  children: ReactNode
  testId?: string
}

export function AlphaHealthCard({ title, children, testId }: AlphaHealthCardProps) {
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid={testId}>
      <h2 className="text-sm font-medium text-foreground">{title}</h2>
      <div className="mt-3">{children}</div>
    </section>
  )
}
