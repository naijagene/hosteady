import type { ActivitySeverity } from '@/api/types/activity'

export function getSeverityLabel(severity?: ActivitySeverity): string {
  switch ((severity ?? 'info').toLowerCase()) {
    case 'critical':
    case 'high':
      return 'Critical'
    case 'warning':
    case 'medium':
      return 'Warning'
    case 'low':
      return 'Low'
    default:
      return 'Info'
  }
}

export function getSeverityTone(severity?: ActivitySeverity): 'default' | 'warning' | 'critical' {
  switch ((severity ?? 'info').toLowerCase()) {
    case 'critical':
    case 'high':
      return 'critical'
    case 'warning':
    case 'medium':
      return 'warning'
    default:
      return 'default'
  }
}
