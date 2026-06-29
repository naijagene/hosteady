import type { CellRendererProps } from './types'

export function BadgeCell({ value }: CellRendererProps) {
  return (
    <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-foreground">
      {String(value ?? '')}
    </span>
  )
}
