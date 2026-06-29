import { Bell } from '@/components/icons'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'

export function NotificationBell() {
  const runtime = useHydratedRuntime()
  const unreadCount = runtime?.unreadNotificationCount ?? 0
  const reference = runtime?.personalizationRuntime?.notification_preferences_reference

  return (
    <button
      type="button"
      className="relative inline-flex h-9 w-9 items-center justify-center rounded-md border border-primary-foreground/20 text-primary-foreground hover:bg-primary-foreground/10"
      aria-label={`Notifications${unreadCount > 0 ? `, ${unreadCount} unread` : ''}`}
      title={
        typeof reference?.panel_position === 'string'
          ? `Panel: ${reference.panel_position}`
          : 'Notifications'
      }
    >
      <Bell className="h-4 w-4" aria-hidden />
      {unreadCount > 0 ? (
        <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-semibold text-white">
          {unreadCount > 99 ? '99+' : unreadCount}
        </span>
      ) : null}
    </button>
  )
}
