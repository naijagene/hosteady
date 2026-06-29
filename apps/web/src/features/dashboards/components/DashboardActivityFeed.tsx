interface DashboardActivityFeedProps {
  title: string
  items: Array<{ title?: string; description?: string; timestamp?: string }>
}

export function DashboardActivityFeed({ title, items }: DashboardActivityFeedProps) {
  return (
    <div className="space-y-3" data-testid="dashboard-activity-feed" aria-label={`${title} activity feed`}>
      <h4 className="text-sm font-medium text-foreground">{title}</h4>
      {items.length === 0 ? (
        <p className="text-xs text-muted-foreground">No recent activity.</p>
      ) : (
        <ul className="space-y-2">
          {items.map((item, index) => (
            <li key={index} className="rounded-md border border-border px-3 py-2 text-xs">
              <p className="font-medium text-foreground">{item.title ?? 'Activity item'}</p>
              {item.description ? (
                <p className="text-muted-foreground">{item.description}</p>
              ) : null}
              {item.timestamp ? (
                <p className="text-muted-foreground">{item.timestamp}</p>
              ) : null}
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
