interface AdminDefinitionListProps {
  items: Array<{ label: string; value: string }>
}

export function AdminDefinitionList({ items }: AdminDefinitionListProps) {
  return (
    <dl className="grid gap-3 sm:grid-cols-2" data-testid="admin-definition-list">
      {items.map((item) => (
        <div key={item.label}>
          <dt className="text-xs font-medium text-foreground">{item.label}</dt>
          <dd className="mt-1 text-xs text-muted-foreground">{item.value}</dd>
        </div>
      ))}
    </dl>
  )
}
