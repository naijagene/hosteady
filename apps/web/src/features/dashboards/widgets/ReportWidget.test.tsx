import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { ReportWidget } from '@/features/dashboards/widgets/ReportWidget'

vi.mock('@tanstack/react-router', () => ({
  Link: ({
    children,
    to,
    params,
    ...props
  }: {
    children: React.ReactNode
    to: string
    params?: Record<string, string>
  }) => {
    const href = params
      ? to.replace('$moduleKey', params.moduleKey).replace('$reportKey', params.reportKey)
      : to

    return (
      <a href={href} {...props}>
        {children}
      </a>
    )
  },
}))

describe('ReportWidget', () => {
  it('renders summary metric and open report link', () => {
    render(
      <ReportWidget
        widget={{
          widget_key: 'summary',
          label: 'Summary Report',
          widget_type: 'report',
          metadata: { module_key: 'platform', report_key: 'summary' },
          metric: { key: 'total', label: 'Total', format: 'number' },
          data: { widget_key: 'summary', value: 12 },
        }}
        widgetType="report"
      />,
    )

    expect(screen.getByText('Summary Report')).toBeInTheDocument()
    expect(screen.getByText('12')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Open report Summary Report' })).toHaveAttribute(
      'href',
      '/reports/platform/summary',
    )
  })

  it('shows placeholder when route metadata is missing', () => {
    render(
      <ReportWidget
        widget={{
          widget_key: 'summary',
          label: 'Summary Report',
          widget_type: 'report',
        }}
        widgetType="report"
      />,
    )

    expect(screen.getByText('Report route metadata unavailable.')).toBeInTheDocument()
  })
})
