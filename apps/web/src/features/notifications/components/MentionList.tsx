import type { Mention } from '@/api/types/notifications'
import { NotificationList } from './NotificationList'

interface MentionListProps {
  mentions: Mention[]
  onOpen?: (mention: Mention) => void
  emptyMessage?: string
}

export function MentionList({ mentions, onOpen, emptyMessage }: MentionListProps) {
  return (
    <NotificationList notifications={mentions} onOpen={onOpen} emptyMessage={emptyMessage ?? 'No mentions yet.'} />
  )
}
