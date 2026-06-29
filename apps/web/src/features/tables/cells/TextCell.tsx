import type { CellRendererProps } from './types'

export function TextCell({ value }: CellRendererProps) {
  return <span>{value === null || value === undefined ? '' : String(value)}</span>
}
