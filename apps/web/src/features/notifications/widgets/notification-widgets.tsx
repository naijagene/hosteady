import type { DashboardWidgetComponentProps } from '@/features/dashboards/widgets/types'
import { NotificationCenter } from '../components/NotificationCenter'

export function NotificationCenterWidget({ widget }: DashboardWidgetComponentProps) {
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="notification-center-widget">
      <NotificationCenter title={widget.label ?? 'Notifications'} binding={{ mode: 'compact', per_page: 5, show_counts: true }} />
    </section>
  )
}

export function AnnouncementWidget({ widget }: DashboardWidgetComponentProps) {
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="announcement-widget">
      <NotificationCenter title={widget.label ?? 'Announcements'} binding={{ mode: 'announcements', per_page: 5 }} />
    </section>
  )
}

export function ReminderWidget({ widget }: DashboardWidgetComponentProps) {
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="reminder-widget">
      <NotificationCenter title={widget.label ?? 'Reminders'} binding={{ mode: 'reminders', per_page: 5 }} />
    </section>
  )
}

export function MentionWidget({ widget }: DashboardWidgetComponentProps) {
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="mention-widget">
      <NotificationCenter title={widget.label ?? 'Mentions'} binding={{ mode: 'mentions', per_page: 5 }} />
    </section>
  )
}
