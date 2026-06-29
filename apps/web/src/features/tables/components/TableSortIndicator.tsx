import type { SortDirection } from '../core/table-sorts'

export function TableSortIndicator({ direction }: { direction: SortDirection }) {
  if (direction === 'asc') {
    return <span aria-hidden>↑</span>
  }

  if (direction === 'desc') {
    return <span aria-hidden>↓</span>
  }

  return <span aria-hidden className="opacity-40">↕</span>
}
