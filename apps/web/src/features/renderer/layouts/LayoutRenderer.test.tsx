import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { LayoutRenderer } from '@/features/renderer/layouts/LayoutRenderer'
import type { UiLayout, UiRegion } from '@/api/types/ui'

const region = (key: string, sort: number): UiRegion => ({
  region_key: key,
  region_type: 'content',
  label: key,
  sort_order: sort,
  components: [],
})

describe('LayoutRenderer', () => {
  it('renders fallback when layout missing', () => {
    render(<LayoutRenderer layout={null} />)
    expect(screen.getByTestId('layout-fallback')).toBeInTheDocument()
  })

  it('renders empty layout message', () => {
    const layout: UiLayout = {
      layout_key: 'empty',
      name: 'Empty',
      layout_type: 'single_column',
      regions: [],
    }

    render(<LayoutRenderer layout={layout} />)
    expect(screen.getByTestId('layout-empty')).toBeInTheDocument()
  })

  const layoutTypes: UiLayout['layout_type'][] = [
    'single_column',
    'two_column',
    'three_column',
    'sidebar',
    'header_content',
    'dashboard_grid',
    'tabbed',
    'wizard',
    'split_pane',
    'custom',
  ]

  layoutTypes.forEach((layoutType) => {
    it(`renders ${layoutType} layout`, () => {
      render(
        <LayoutRenderer
          layout={{
            layout_key: layoutType,
            name: layoutType,
            layout_type: layoutType,
          }}
          regions={[region('main', 0)]}
        />,
      )

      expect(screen.getByTestId('layout-renderer')).toHaveAttribute(
        'data-layout-type',
        layoutType,
      )
    })
  })

  it('sorts regions by sort_order', () => {
    render(
      <LayoutRenderer
        layout={{ layout_key: 'sorted', name: 'Sorted', layout_type: 'two_column' }}
        regions={[region('second', 2), region('first', 1)]}
      />,
    )

    const rendered = screen.getAllByTestId(/region-/)
    expect(rendered[0]).toHaveAttribute('data-region-key', 'first')
    expect(rendered[1]).toHaveAttribute('data-region-key', 'second')
  })
})
