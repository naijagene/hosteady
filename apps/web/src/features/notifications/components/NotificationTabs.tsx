import type { NotificationCenterTab, NotificationTabCounts } from '../types'

const tabs: Array<{ id: NotificationCenterTab; label: string }> = [
  { id: 'all', label: 'All' },
  { id: 'unread', label: 'Unread' },
  { id: 'announcements', label: 'Announcements' },
  { id: 'mentions', label: 'Mentions' },
  { id: 'reminders', label: 'Reminders' },
  { id: 'workflow', label: 'Workflow' },
  { id: 'documents', label: 'Documents' },
  { id: 'system', label: 'System' },
]

interface NotificationTabsProps {
  activeTab: NotificationCenterTab
  counts?: Partial<NotificationTabCounts>
  showCounts?: boolean
  onChange: (tab: NotificationCenterTab) => void
}

export function NotificationTabs({
  activeTab,
  counts,
  showCounts = true,
  onChange,
}: NotificationTabsProps) {
  return (
    <div className="flex flex-wrap gap-2" data-testid="notification-tabs" role="tablist" aria-label="Notification tabs">
      {tabs.map((tab) => {
        const count = counts?.[tab.id] ?? 0
        const selected = activeTab === tab.id

        return (
          <button
            key={tab.id}
            type="button"
            role="tab"
            aria-selected={selected}
            aria-controls={`notification-panel-${tab.id}`}
            className={`rounded-md px-3 py-1.5 text-xs font-medium ${
              selected ? 'bg-primary text-primary-foreground' : 'border border-border bg-background'
            }`}
            onClick={() => onChange(tab.id)}
          >
            {tab.label}
            {showCounts ? ` (${count})` : ''}
          </button>
        )
      })}
    </div>
  )
}
