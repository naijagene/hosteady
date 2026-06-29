export function normalizeWorkflowStatus(status?: string | null): string {
  return (status ?? 'unknown').toLowerCase()
}

export function getWorkflowStatusLabel(status?: string | null): string {
  const normalized = normalizeWorkflowStatus(status)
  return normalized.replace(/_/g, ' ')
}

export function getWorkflowStatusTone(status?: string | null): 'default' | 'success' | 'warning' | 'danger' {
  const normalized = normalizeWorkflowStatus(status)

  if (['completed', 'approved', 'success'].includes(normalized)) {
    return 'success'
  }

  if (['failed', 'rejected', 'cancelled', 'error'].includes(normalized)) {
    return 'danger'
  }

  if (['waiting', 'pending', 'assigned', 'opened', 'in_progress', 'running'].includes(normalized)) {
    return 'warning'
  }

  return 'default'
}
