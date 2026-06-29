import type { CellRendererProps } from './types'

export function LinkCell({ value }: CellRendererProps) {
  const href = String(value ?? '')
  if (!href) {
    return <span />
  }

  return (
    <a href={href} className="text-primary underline">
      {href}
    </a>
  )
}
