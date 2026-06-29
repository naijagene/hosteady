import type { CellRendererProps } from './types'

export function StatusCell({ value }: CellRendererProps) {
  return (
    <span className="rounded-md border border-border px-2 py-0.5 text-xs text-muted-foreground">
      {String(value ?? '')}
    </span>
  )
}
