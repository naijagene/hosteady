import { useMemo } from 'react'
import type { ActivityEntry } from '@/api/types/activity'
import { buildTimeline } from '../core/activity-timeline'

export function useActivityTimeline(entries: ActivityEntry[], mode: 'compact' | 'full' = 'full') {
  return useMemo(() => buildTimeline(entries, mode), [entries, mode])
}
