interface DashboardNotificationWidgetProps {
  title: string
  items: Array<{ title?: string; message?: string }>
}

export function DashboardNotificationWidget({
  title,
  items,
}: DashboardNotificationWidgetProps) {
  return (
    <div
      className="space-y-3"
      data-testid="dashboard-notification-widget"
      aria-label={`${title} notifications`}
    >
      <h4 className="text-sm font-medium text-foreground">{title}</h4>
      {items.length === 0 ? (
        <p className="text-xs text-muted-foreground">No notifications.</p>
      ) : (
        <ul className="space-y-2">
          {items.map((item, index) => (
            <li key={index} className="rounded-md border border-border px-3 py-2 text-xs">
              <p className="font-medium text-foreground">{item.title ?? 'Notification'}</p>
              {item.message ? <p className="text-muted-foreground">{item.message}</p> : null}
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
