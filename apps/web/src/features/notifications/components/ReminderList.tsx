import type { Reminder } from '@/api/types/notifications'
import { NotificationList } from './NotificationList'

interface ReminderListProps {
  reminders: Reminder[]
  onOpen?: (reminder: Reminder) => void
  emptyMessage?: string
}

export function ReminderList({ reminders, onOpen, emptyMessage }: ReminderListProps) {
  return (
    <NotificationList notifications={reminders} onOpen={onOpen} emptyMessage={emptyMessage ?? 'No reminders scheduled.'} />
  )
}
