import type { CellRendererProps } from './types'

export function BooleanCell({ value }: CellRendererProps) {
  return (
    <span>
      {value === true || value === 'true'
        ? 'Yes'
        : value === false || value === 'false'
          ? 'No'
          : ''}
    </span>
  )
}
