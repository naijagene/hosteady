import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { CardComponent } from '@/features/renderer/components/CardComponent'
import { MetricComponent } from '@/features/renderer/components/MetricComponent'
import { ChartPlaceholderComponent } from '@/features/renderer/components/ChartPlaceholderComponent'

describe('renderer theme-aware components', () => {
  it('uses theme token utility classes for card surfaces', () => {
    render(
      <CardComponent
        component={{
          public_id: '1',
          component_key: 'card',
          name: 'Card',
          component_type: 'card',
        }}
      />,
    )

    const card = screen.getByTestId('card-component')
    expect(card.className).toContain('bg-card')
    expect(card.className).toContain('border-border')
    expect(card.className).not.toMatch(/#[0-9a-f]{3,8}/i)
  })

  it('uses foreground tokens for metric values', () => {
    render(
      <MetricComponent
        component={{
          public_id: '1',
          component_key: 'metric',
          name: 'Active Users',
          component_type: 'metric',
          metadata: { value: '128' },
        }}
      />,
    )

    expect(screen.getByTestId('metric-component').className).toContain('bg-card')
    expect(screen.getByText('128').className).toContain('text-foreground')
  })

  it('uses neutral placeholder styling for charts', () => {
    render(
      <ChartPlaceholderComponent
        component={{
          public_id: '1',
          component_key: 'chart',
          name: 'Trend',
          component_type: 'chart',
        }}
      />,
    )

    expect(screen.getByTestId('chart-placeholder').className).toContain('border-border')
    expect(screen.getByTestId('chart-placeholder').className).toContain('text-muted-foreground')
  })
})
