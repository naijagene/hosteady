import { createElement, type ComponentType } from 'react'
import { BadgeCell } from './BadgeCell'
import { BooleanCell } from './BooleanCell'
import { DateCell } from './DateCell'
import { DocumentCell } from './DocumentCell'
import { LinkCell } from './LinkCell'
import { MoneyCell } from './MoneyCell'
import { NumberCell } from './NumberCell'
import { StatusCell } from './StatusCell'
import { TextCell } from './TextCell'
import type { CellRendererProps } from './types'
import { UnsupportedCell } from './UnsupportedCell'

type CellComponent = ComponentType<CellRendererProps>

const cellRegistry: Record<string, CellComponent> = {
  text: TextCell,
  string: TextCell,
  number: NumberCell,
  integer: NumberCell,
  decimal: NumberCell,
  money: MoneyCell,
  date: DateCell,
  datetime: DateCell,
  time: DateCell,
  boolean: BooleanCell,
  badge: BadgeCell,
  status: StatusCell,
  email: LinkCell,
  phone: TextCell,
  url: LinkCell,
  document: DocumentCell,
  action: TextCell,
}

export function resolveCellComponent(columnType: string): CellComponent {
  return cellRegistry[columnType.toLowerCase()] ?? UnsupportedCell
}

export function renderTableCell(props: CellRendererProps) {
  return createElement(resolveCellComponent(props.column.column_type), props)
}
