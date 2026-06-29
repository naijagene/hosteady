export function getNotificationPriorityLabel(priority?: string | null): string {
  return (priority ?? 'normal').replace(/_/g, ' ')
}

export function getNotificationPriorityTone(priority?: string | null): 'default' | 'warning' | 'danger' {
  const normalized = (priority ?? 'normal').toLowerCase()
  if (['urgent', 'critical', 'high'].includes(normalized)) {
    return 'danger'
  }
  if (normalized === 'low') {
    return 'default'
  }
  return 'warning'
}
