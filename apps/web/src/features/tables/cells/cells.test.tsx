import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import type { NormalizedTableColumn } from '@/features/tables/types'
import {
  BooleanCell,
  BadgeCell,
  DateCell,
  DocumentCell,
  LinkCell,
  MoneyCell,
  NumberCell,
  StatusCell,
  TextCell,
  UnsupportedCell,
  renderTableCell,
  resolveCellComponent,
} from '@/features/tables/cells'

const column = (type: string): NormalizedTableColumn => ({
  column_key: 'value',
  label: 'Value',
  column_type: type,
  visibleInView: true,
})

const row = { values: { value: 'test' } }

describe('table cell registry', () => {
  it('resolves known cell types', () => {
    expect(resolveCellComponent('text')).toBe(TextCell)
    expect(resolveCellComponent('money')).toBe(MoneyCell)
    expect(resolveCellComponent('unknown-type')).toBe(UnsupportedCell)
  })

  it.each([
    ['text', TextCell, 'hello'],
    ['number', NumberCell, 42],
    ['integer', NumberCell, 7],
    ['decimal', NumberCell, 1.5],
    ['money', MoneyCell, '10.00'],
    ['date', DateCell, '2024-01-01'],
    ['datetime', DateCell, '2024-01-01T10:00:00Z'],
    ['time', DateCell, '10:00'],
    ['boolean', BooleanCell, true],
    ['badge', BadgeCell, 'Active'],
    ['status', StatusCell, 'Pending'],
    ['email', LinkCell, 'test@example.com'],
    ['url', LinkCell, 'https://example.com'],
    ['document', DocumentCell, 'file.pdf'],
  ])('renders %s cell', (type, Component, value) => {
    render(<Component column={column(type)} row={row} value={value} />)
    if (type === 'boolean') {
      expect(screen.getByText('Yes')).toBeInTheDocument()
    } else if (type === 'url' || type === 'email') {
      expect(screen.getByRole('link')).toBeInTheDocument()
    } else {
      expect(screen.getByText(String(value))).toBeInTheDocument()
    }
  })

  it('renders unsupported cell fallback', () => {
    render(<UnsupportedCell column={column('custom-widget')} row={row} value="x" />)
    expect(screen.getByTestId('unsupported-cell')).toHaveTextContent('custom-widget')
  })

  it('renders empty text values safely', () => {
    const { container } = render(<TextCell column={column('text')} row={row} value={null} />)
    expect(container.querySelector('span')?.textContent).toBe('')
  })

  it('renders boolean false as No', () => {
    render(<BooleanCell column={column('boolean')} row={row} value={false} />)
    expect(screen.getByText('No')).toBeInTheDocument()
  })

  it('uses renderTableCell helper', () => {
    const element = renderTableCell({
      column: column('badge'),
      row,
      value: 'New',
    })
    render(<>{element}</>)
    expect(screen.getByText('New')).toBeInTheDocument()
  })
})
