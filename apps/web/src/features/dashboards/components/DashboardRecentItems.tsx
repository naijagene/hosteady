interface DashboardRecentItemsProps {
  title: string
  items: Array<{ label?: string; route?: string }>
}

export function DashboardRecentItems({ title, items }: DashboardRecentItemsProps) {
  return (
    <div className="space-y-3" data-testid="dashboard-recent-items" aria-label={`${title} recent items`}>
      <h4 className="text-sm font-medium text-foreground">{title}</h4>
      {items.length === 0 ? (
        <p className="text-xs text-muted-foreground">No recent items.</p>
      ) : (
        <ul className="space-y-1 text-xs text-foreground">
          {items.map((item, index) => (
            <li key={index}>{item.label ?? item.route ?? 'Recent item'}</li>
          ))}
        </ul>
      )}
    </div>
  )
}
