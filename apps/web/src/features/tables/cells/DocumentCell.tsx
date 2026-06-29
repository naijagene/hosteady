import type { CellRendererProps } from './types'

export function DocumentCell({ value }: CellRendererProps) {
  return <span className="text-muted-foreground">{value ? String(value) : '—'}</span>
}
