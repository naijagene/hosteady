import type { CellRendererProps } from './types'

export function DateCell({ value }: CellRendererProps) {
  return <span>{value ? String(value) : ''}</span>
}
