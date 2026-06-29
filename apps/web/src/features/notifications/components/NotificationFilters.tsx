interface NotificationFiltersProps {
  statusFilter: string
  priorityFilter: string
  onStatusChange: (value: string) => void
  onPriorityChange: (value: string) => void
}

export function NotificationFilters({
  statusFilter,
  priorityFilter,
  onStatusChange,
  onPriorityChange,
}: NotificationFiltersProps) {
  return (
    <div className="grid gap-2 sm:grid-cols-2" data-testid="notification-filters">
      <select
        className="rounded-md border border-border bg-background px-3 py-2 text-sm"
        value={statusFilter}
        onChange={(event) => onStatusChange(event.target.value)}
        aria-label="Filter by status"
      >
        <option value="">All statuses</option>
        <option value="pending">Pending</option>
        <option value="delivered">Delivered</option>
        <option value="read">Read</option>
      </select>
      <select
        className="rounded-md border border-border bg-background px-3 py-2 text-sm"
        value={priorityFilter}
        onChange={(event) => onPriorityChange(event.target.value)}
        aria-label="Filter by priority"
      >
        <option value="">All priorities</option>
        <option value="low">Low</option>
        <option value="normal">Normal</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>
  )
}
