import type { Announcement } from '@/api/types/notifications'
import { NotificationList } from './NotificationList'

interface AnnouncementListProps {
  announcements: Announcement[]
  onOpen?: (announcement: Announcement) => void
  emptyMessage?: string
}

export function AnnouncementList({ announcements, onOpen, emptyMessage }: AnnouncementListProps) {
  return (
    <NotificationList
      notifications={announcements}
      onOpen={onOpen}
      emptyMessage={emptyMessage ?? 'No announcements available.'}
    />
  )
}
