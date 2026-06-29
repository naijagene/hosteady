import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { DashboardChartCard } from '@/features/dashboards/components/DashboardChartCard'
import { DashboardEmptyState } from '@/features/dashboards/components/DashboardEmptyState'
import { DashboardErrorState } from '@/features/dashboards/components/DashboardErrorState'
import { DashboardFilterBar } from '@/features/dashboards/components/DashboardFilterBar'
import { DashboardLoadingState } from '@/features/dashboards/components/DashboardLoadingState'
import { DashboardMetricCard } from '@/features/dashboards/components/DashboardMetricCard'
import { DashboardToolbar } from '@/features/dashboards/components/DashboardToolbar'
import { ActivityWidget } from '@/features/dashboards/widgets/ActivityWidget'
import { ChartWidget } from '@/features/dashboards/widgets/ChartWidget'
import { FavoritesWidget } from '@/features/dashboards/widgets/FavoritesWidget'
import { MetricWidget } from '@/features/dashboards/widgets/MetricWidget'
import { NotificationWidget } from '@/features/dashboards/widgets/NotificationWidget'
import { PlaceholderWidget } from '@/features/dashboards/widgets/PlaceholderWidget'
import { QuickActionsWidget } from '@/features/dashboards/widgets/QuickActionsWidget'
import { RecentItemsWidget } from '@/features/dashboards/widgets/RecentItemsWidget'

const metricWidget = {
  widget_key: 'total',
  label: 'Total records',
  widget_type: 'metric',
  metric: { key: 'total', label: 'Total records', format: 'number' },
  data: { widget_key: 'total', value: 42 },
  widgetType: 'metric',
}

describe('dashboard UI components', () => {
  it('renders loading and error states accessibly', () => {
    render(<DashboardLoadingState />)
    expect(screen.getByRole('status')).toHaveTextContent('Loading dashboard')

    render(<DashboardErrorState message="Dashboard failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Dashboard failed')
  })

  it('renders empty state', () => {
    render(<DashboardEmptyState message="Nothing configured" />)
    expect(screen.getByTestId('dashboard-empty-state')).toHaveTextContent('Nothing configured')
  })

  it('renders metric card with trend and status', () => {
    render(
      <DashboardMetricCard
        title="Total"
        value="42"
        trend="+5%"
        status="Healthy"
      />,
    )
    expect(screen.getByLabelText('Total metric')).toBeInTheDocument()
    expect(screen.getByText('42')).toBeInTheDocument()
    expect(screen.getByText('Trend: +5%')).toBeInTheDocument()
  })

  it('renders chart placeholder', () => {
    render(
      <DashboardChartCard
        title="Trend"
        chartType="bar"
        labels={['Jan', 'Feb']}
        datasets={[{ key: 'series', data: [1, 2] }]}
      />,
    )
    expect(screen.getByLabelText('Trend chart')).toBeInTheDocument()
    expect(screen.getByText('Chart type: bar')).toBeInTheDocument()
  })

  it('renders unsupported chart fallback', () => {
    render(<DashboardChartCard title="Trend" chartType="unknown-chart" />)
    expect(screen.getByText(/Unsupported chart type fallback/)).toBeInTheDocument()
  })

  it('renders filter bar controls', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()
    render(
      <DashboardFilterBar
        filters={[
          { filter_key: 'status', label: 'Status', filter_type: 'text' },
          { filter_key: 'active', label: 'Active only', filter_type: 'boolean' },
        ]}
        values={{ status: '', active: false }}
        onChange={onChange}
        onClear={vi.fn()}
      />,
    )

    await user.type(screen.getByLabelText('Status'), 'open')
    expect(onChange).toHaveBeenCalled()
  })

  it('renders toolbar actions', async () => {
    const user = userEvent.setup()
    const onAction = vi.fn()
    render(
      <DashboardToolbar
        title="Overview"
        description="Main dashboard"
        actions={[{ action_key: 'refresh', label: 'Refresh', action_type: 'refresh' }]}
        onAction={onAction}
      />,
    )

    await user.click(screen.getByRole('button', { name: 'Refresh' }))
    expect(onAction).toHaveBeenCalled()
  })
})

describe('dashboard widget components', () => {
  it('renders metric widget', () => {
    render(<MetricWidget widget={metricWidget as never} widgetType="metric" />)
    expect(screen.getByText('42')).toBeInTheDocument()
  })

  it('renders chart widget placeholder', () => {
    render(
      <ChartWidget
        widget={
          {
            widget_key: 'chart',
            label: 'Trend',
            widget_type: 'chart',
            chart_type: 'line',
            data: { widget_key: 'chart', chart: { type: 'line', labels: ['A'], datasets: [] } },
          } as never
        }
        widgetType="chart"
      />,
    )
    expect(screen.getByLabelText('Trend chart')).toBeInTheDocument()
  })

  it('renders activity and notification widgets', () => {
    render(
      <ActivityWidget
        widget={
          {
            widget_key: 'activity',
            label: 'Activity',
            widget_type: 'activity',
            data: { widget_key: 'activity', rows: [{ title: 'Updated record' }] },
          } as never
        }
        widgetType="activity"
      />,
    )
    expect(screen.getByText('Updated record')).toBeInTheDocument()

    render(
      <NotificationWidget
        widget={
          {
            widget_key: 'notifications',
            label: 'Notifications',
            widget_type: 'notification',
            data: { widget_key: 'notifications', rows: [{ title: 'Alert', message: 'New item' }] },
          } as never
        }
        widgetType="notification"
      />,
    )
    expect(screen.getByText('Alert')).toBeInTheDocument()
  })

  it('renders favorites, recent, and quick actions widgets', () => {
    render(
      <FavoritesWidget
        widget={
          {
            widget_key: 'favorites',
            label: 'Favorites',
            widget_type: 'favorites',
            data: { widget_key: 'favorites', rows: [{ label: 'Home page' }] },
          } as never
        }
        widgetType="favorites"
      />,
    )
    expect(screen.getByText('Home page')).toBeInTheDocument()

    render(
      <RecentItemsWidget
        widget={
          {
            widget_key: 'recent',
            label: 'Recent',
            widget_type: 'recent_items',
            data: { widget_key: 'recent', rows: [{ label: 'Users table' }] },
          } as never
        }
        widgetType="recent_items"
      />,
    )
    expect(screen.getByText('Users table')).toBeInTheDocument()

    render(
      <QuickActionsWidget
        widget={
          {
            widget_key: 'quick',
            label: 'Quick actions',
            widget_type: 'quick_actions',
            data: { widget_key: 'quick', metadata: { actions: [{ label: 'Create record' }] } },
          } as never
        }
        widgetType="quick_actions"
      />,
    )
    expect(screen.getByRole('button', { name: 'Create record' })).toBeInTheDocument()
  })

  it('renders placeholder widget fallback', () => {
    render(
      <PlaceholderWidget
        widget={{ widget_key: 'x', label: 'Custom', widget_type: 'custom' } as never}
        widgetType="custom-widget"
      />,
    )
    expect(screen.getByText(/Unsupported widget type: custom-widget/)).toBeInTheDocument()
  })
})
