import type { UiComponent } from '@/api/types/ui'

interface DocumentListPlaceholderProps {
  component: UiComponent
}

export function DocumentListPlaceholder({ component }: DocumentListPlaceholderProps) {
  return (
    <div
      className="rounded-lg border border-border bg-card p-4"
      data-testid="document-list-placeholder"
    >
      <h4 className="text-sm font-medium text-foreground">{component.name}</h4>
      <p className="mt-2 text-xs text-muted-foreground">
        Document list binding placeholder
      </p>
    </div>
  )
}
