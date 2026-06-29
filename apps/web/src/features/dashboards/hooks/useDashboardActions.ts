import { useCallback, useState } from 'react'
import type { DashboardAction } from '@/api/types/dashboards'
import { getActionPlaceholderMessage } from '../core/dashboard-actions'

export function useDashboardActions(options?: { onRefresh?: () => void }) {
  const [message, setMessage] = useState<string | null>(null)

  const handleAction = useCallback(
    (action: DashboardAction) => {
      const type = action.action_type.toLowerCase()

      if (type === 'refresh') {
        options?.onRefresh?.()
        setMessage(null)
        return
      }

      setMessage(getActionPlaceholderMessage(action))
    },
    [options],
  )

  return {
    message,
    handleAction,
    clearMessage: () => setMessage(null),
  }
}
