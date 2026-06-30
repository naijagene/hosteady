import { useCallback } from 'react'
import { useNavigate } from '@tanstack/react-router'
import type { ActivityEntry } from '@/api/types/activity'
import { resolveActivityAction, resolveActivityRoute } from '../core/activity-actions'

export function useActivityActions() {
  const navigate = useNavigate()

  const openEntry = useCallback(
    async (entry: ActivityEntry) => {
      const action = resolveActivityAction(entry)
      const route = action.route ?? resolveActivityRoute(entry)
      if (route) {
        await navigate({ to: route })
      }
    },
    [navigate],
  )

  return { openEntry, resolveActivityRoute, resolveActivityAction }
}
