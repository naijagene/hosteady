export type NotificationCenterTab =
  | 'all'
  | 'unread'
  | 'announcements'
  | 'mentions'
  | 'reminders'
  | 'workflow'
  | 'documents'
  | 'system'

export interface NotificationTabCounts {
  all: number
  unread: number
  announcements: number
  mentions: number
  reminders: number
  workflow: number
  documents: number
  system: number
}
