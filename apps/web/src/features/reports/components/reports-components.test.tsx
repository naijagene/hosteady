import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { ReportChart, ReportMetric, ReportParameter, ReportSection } from '@/api/types/reports'
import { ReportChartPlaceholder } from '@/features/reports/components/ReportChartPlaceholder'
import { ReportEmptyState } from '@/features/reports/components/ReportEmptyState'
import { ReportErrorState } from '@/features/reports/components/ReportErrorState'
import { ReportExportMenu } from '@/features/reports/components/ReportExportMenu'
import { ReportHeader } from '@/features/reports/components/ReportHeader'
import { ReportLoadingState } from '@/features/reports/components/ReportLoadingState'
import { ReportMetricCard } from '@/features/reports/components/ReportMetricCard'
import { ReportParameterPanel } from '@/features/reports/components/ReportParameterPanel'
import { ReportRunStatus } from '@/features/reports/components/ReportRunStatus'
import { ReportSectionRenderer } from '@/features/reports/components/ReportSectionRenderer'
import { ReportSummaryCards } from '@/features/reports/components/ReportSummaryCards'
import { ReportTableSection } from '@/features/reports/components/ReportTableSection'
import { ReportToolbar } from '@/features/reports/components/ReportToolbar'

const metrics: ReportMetric[] = [
  { metric_key: 'total', label: 'Total', value: 42, trend: 'up', comparison: 'vs last week' },
]

const parameters: ReportParameter[] = [
  { parameter_key: 'status', label: 'Status', parameter_type: 'text', required: true },
  { parameter_key: 'active', label: 'Active', parameter_type: 'boolean' },
  { parameter_key: 'hidden', label: 'Hidden', parameter_type: 'hidden', default_value: 'x' },
]

describe('report state components', () => {
  it('renders loading state with aria-busy', () => {
    render(<ReportLoadingState />)
    expect(screen.getByTestId('report-loading-state')).toHaveAttribute('aria-busy', 'true')
  })

  it('renders error state with alert role', () => {
    render(<ReportErrorState message="Failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Failed')
  })

  it('renders empty state message', () => {
    render(<ReportEmptyState message="Nothing here" />)
    expect(screen.getByText('Nothing here')).toBeInTheDocument()
  })
})

describe('report header and toolbar', () => {
  it('renders report header', () => {
    render(<ReportHeader title="Summary" description="Platform summary" />)
    expect(screen.getByRole('heading', { name: 'Summary' })).toBeInTheDocument()
  })

  it('renders toolbar actions and export menu', async () => {
    const user = userEvent.setup()
    const onAction = vi.fn()
    const onExport = vi.fn()

    render(
      <ReportToolbar
        actions={[{ action_key: 'refresh', label: 'Refresh', action_type: 'refresh' }]}
        onAction={onAction}
        onExport={onExport}
      />,
    )

    await user.click(screen.getByRole('button', { name: 'Refresh' }))
    expect(onAction).toHaveBeenCalled()

    await user.click(screen.getByLabelText('Export report'))
    await user.click(screen.getByRole('button', { name: 'Export as CSV' }))
    expect(onExport).toHaveBeenCalledWith('csv')
  })
})

describe('report parameter panel', () => {
  it('renders parameter labels and apply/reset buttons', async () => {
    const user = userEvent.setup()
    const onApply = vi.fn()
    const onReset = vi.fn()

    render(
      <ReportParameterPanel
        parameters={parameters}
        values={{ status: 'open', active: true, hidden: 'x' }}
        onChange={vi.fn()}
        onApply={onApply}
        onReset={onReset}
      />,
    )

    expect(screen.getByLabelText('Status')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Apply report parameters' }))
    await user.click(screen.getByRole('button', { name: 'Reset report parameters' }))
    expect(onApply).toHaveBeenCalled()
    expect(onReset).toHaveBeenCalled()
  })

  it('shows validation warnings', () => {
    render(
      <ReportParameterPanel
        parameters={parameters}
        values={{ status: '', active: false, hidden: 'x' }}
        warnings={['Status is required']}
        onChange={vi.fn()}
        onApply={vi.fn()}
        onReset={vi.fn()}
      />,
    )

    expect(screen.getByRole('alert')).toHaveTextContent('Status is required')
  })
})

describe('report metrics and summary cards', () => {
  it('renders metric card with trend and comparison', () => {
    render(
      <ReportMetricCard
        title="Total"
        value="42"
        trend="up"
        comparison="vs last week"
        status="healthy"
      />,
    )

    expect(screen.getByLabelText('Total metric')).toBeInTheDocument()
    expect(screen.getByText('Trend: up')).toBeInTheDocument()
  })

  it('renders summary cards grid', () => {
    render(<ReportSummaryCards metrics={metrics} title="Summary" />)
    expect(screen.getByLabelText('Summary')).toBeInTheDocument()
    expect(screen.getByText('42')).toBeInTheDocument()
  })
})

describe('report table section', () => {
  it('renders table with caption and rows', () => {
    render(
      <ReportTableSection
        title="Results"
        columns={[{ column_key: 'name', label: 'Name', column_type: 'text' }]}
        rows={[{ name: 'Alpha' }, { name: 'Beta' }]}
        maxVisibleRows={1}
      />,
    )

    expect(screen.getByText('Alpha')).toBeInTheDocument()
    expect(screen.getByText('Showing 1 of 2 rows.')).toBeInTheDocument()
  })

  it('renders empty table state', () => {
    render(
      <ReportTableSection
        title="Results"
        columns={[{ column_key: 'name', label: 'Name', column_type: 'text' }]}
        rows={[]}
      />,
    )

    expect(screen.getByText('No rows available for this report section.')).toBeInTheDocument()
  })
})

describe('report chart placeholder', () => {
  const chart: ReportChart = {
    chart_key: 'trend',
    label: 'Trend',
    chart_type: 'line',
    labels: ['A', 'B'],
    datasets: [{ data: [1, 2] }],
  }

  it('renders supported chart placeholder', () => {
    render(<ReportChartPlaceholder chart={chart} />)
    expect(screen.getByLabelText('Trend chart placeholder')).toBeInTheDocument()
    expect(screen.getByText('2 labels · 2 points')).toBeInTheDocument()
  })

  it('renders unsupported chart fallback', () => {
    render(<ReportChartPlaceholder chart={{ ...chart, chart_type: '3d' }} />)
    expect(screen.getByText('Unsupported chart type')).toBeInTheDocument()
  })
})

describe('report section renderer', () => {
  it('renders summary section', () => {
    const section: ReportSection = {
      section_key: 'summary',
      label: 'Summary',
      section_type: 'summary',
      metrics,
    }

    render(<ReportSectionRenderer section={section} />)
    expect(screen.getByTestId('report-section-summary')).toBeInTheDocument()
  })

  it('renders text section', () => {
    render(
      <ReportSectionRenderer
        section={{
          section_key: 'notes',
          label: 'Notes',
          section_type: 'text',
          content: 'Report notes',
        }}
      />,
    )

    expect(screen.getByText('Report notes')).toBeInTheDocument()
  })

  it('renders custom section placeholder for unknown type', () => {
    render(
      <ReportSectionRenderer
        section={{
          section_key: 'custom',
          label: 'Custom',
          section_type: 'unknown',
        }}
      />,
    )

    expect(screen.getByTestId('report-custom-section')).toHaveTextContent('Custom')
  })
})

describe('report export menu and run status', () => {
  it('renders export menu labels', () => {
    render(<ReportExportMenu onExport={vi.fn()} message="Export placeholder" />)
    expect(screen.getByLabelText('Export report')).toBeInTheDocument()
    expect(screen.getByText('Export placeholder')).toBeInTheDocument()
  })

  it('renders run status states', () => {
    const { rerender } = render(<ReportRunStatus isRunning />)
    expect(screen.getByText('Running report…')).toHaveAttribute('aria-busy', 'true')

    rerender(<ReportRunStatus error="Run failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Run failed')

    rerender(<ReportRunStatus result={{ status: 'completed', duration_ms: 120 }} />)
    expect(screen.getByText(/completed/)).toBeInTheDocument()
  })
})
