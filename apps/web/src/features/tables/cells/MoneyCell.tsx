import type { CellRendererProps } from './types'

export function MoneyCell({ value }: CellRendererProps) {
  return (
    <span className="tabular-nums">
      {value === null || value === undefined ? '' : String(value)}
    </span>
  )
}
