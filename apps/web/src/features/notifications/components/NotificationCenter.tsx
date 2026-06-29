import { useMemo, useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import type { Notification, NotificationBindingContext } from '@/api/types/notifications'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { canDeleteNotifications, canManageNotifications, canReadNotifications } from '../core/notification-permissions'
import { resolveNotificationLink } from '../core/notification-actions'
import { useNotificationActions } from '../hooks/useNotificationActions'
import { useNotificationCenter } from '../hooks/useNotificationCenter'
import { NotificationDrawer } from './NotificationDrawer'
import { NotificationErrorState } from './NotificationErrorState'
import { NotificationFilters } from './NotificationFilters'
import { NotificationList } from './NotificationList'
import { NotificationLoadingState } from './NotificationLoadingState'
import { NotificationSearch } from './NotificationSearch'
import { NotificationTabs } from './NotificationTabs'
import { NotificationToolbar } from './NotificationToolbar'

interface NotificationCenterProps {
  binding?: NotificationBindingContext
  title?: string
}

export function NotificationCenter({ binding, title = 'Notification Center' }: NotificationCenterProps) {
  const runtime = useHydratedRuntime()
  const permissions = runtime?.permissions ?? []
  const navigate = useNavigate()
  const center = useNotificationCenter(binding)
  const actions = useNotificationActions()
  const [selected, setSelected] = useState<Notification | null>(null)
  const [drawerOpen, setDrawerOpen] = useState(false)

  const compact = binding?.mode === 'compact' || binding?.mode === 'dropdown'
  const actionsEnabled = binding?.actions_enabled !== false && canManageNotifications(permissions)
  const deleteEnabled = binding?.delete_enabled === true && canDeleteNotifications(permissions)
  const canRead = canReadNotifications(permissions)

  const panelItems = useMemo(() => center.items, [center.items])

  if (!canRead) {
    return <NotificationErrorState message="You do not have permission to view notifications." />
  }

  const handleOpen = (notification: Notification) => {
    setSelected(notification)
    setDrawerOpen(true)
    if (!notification.read_at && actionsEnabled) {
      void actions.markRead(notification.public_id)
    }
  }

  const handleOpenLink = (notification: Notification) => {
    void navigate({ to: resolveNotificationLink(notification) ?? '/notifications' })
  }

  return (
    <section className="space-y-4" data-testid="notification-center">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-lg font-semibold text-foreground">{title}</h2>
      </div>

      {!compact ? (
        <NotificationTabs
          activeTab={center.activeTab}
          counts={center.counts}
          showCounts={binding?.show_counts !== false}
          onChange={center.setActiveTab}
        />
      ) : null}

      <NotificationSearch value={center.search} onChange={center.setSearch} />
      <NotificationFilters
        statusFilter={center.statusFilter}
        priorityFilter={center.priorityFilter}
        onStatusChange={center.setStatusFilter}
        onPriorityChange={center.setPriorityFilter}
      />

      <NotificationToolbar
        onRefresh={() => center.refresh()}
        onMarkAllRead={async () => {
          await actions.markAllRead()
        }}
        actionsEnabled={actionsEnabled}
        isMarkingAllRead={actions.isMarkingAllRead}
        markAllReadError={actions.markAllReadError}
      />

      {center.isLoading ? <NotificationLoadingState /> : null}
      {!center.isLoading && center.error ? <NotificationErrorState message={center.error.message} /> : null}

      {!center.isLoading && !center.error ? (
        <div id={`notification-panel-${center.activeTab}`} role="tabpanel">
          <NotificationList
            notifications={panelItems}
            onOpen={compact ? handleOpenLink : handleOpen}
            onMarkRead={(notification) => actions.markRead(notification.public_id)}
            onMarkUnread={(notification) => actions.markUnread(notification.public_id)}
            onDelete={(notification) => actions.deleteNotification(notification.public_id)}
            actionsEnabled={actionsEnabled}
            deleteEnabled={deleteEnabled}
            emptyMessage={binding?.empty_state_message}
          />
        </div>
      ) : null}

      <NotificationDrawer
        notification={selected}
        open={drawerOpen}
        actionsEnabled={actionsEnabled}
        deleteEnabled={deleteEnabled}
        onClose={() => {
          setDrawerOpen(false)
          setSelected(null)
        }}
        onMarkRead={selected ? async () => { await actions.markRead(selected.public_id) } : undefined}
        onMarkUnread={selected ? async () => { await actions.markUnread(selected.public_id) } : undefined}
        onDelete={selected ? async () => { await actions.deleteNotification(selected.public_id) } : undefined}
      />
    </section>
  )
}
