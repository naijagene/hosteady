interface DashboardFavoritesProps {
  title: string
  items: Array<{ label?: string; route?: string }>
}

export function DashboardFavorites({ title, items }: DashboardFavoritesProps) {
  return (
    <div className="space-y-3" data-testid="dashboard-favorites" aria-label={`${title} favorites`}>
      <h4 className="text-sm font-medium text-foreground">{title}</h4>
      {items.length === 0 ? (
        <p className="text-xs text-muted-foreground">No favorites yet.</p>
      ) : (
        <ul className="space-y-1 text-xs text-foreground">
          {items.map((item, index) => (
            <li key={index}>{item.label ?? item.route ?? 'Favorite'}</li>
          ))}
        </ul>
      )}
    </div>
  )
}
