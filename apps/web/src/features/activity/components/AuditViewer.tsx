import type { AuditEntry } from '@/api/types/activity'
import { useState } from 'react'
import { ActivityEmptyState } from './ActivityEmptyState'
import { ActivityErrorState } from './ActivityErrorState'
import { ActivityLoadingState } from './ActivityLoadingState'
import { AuditEntryCard } from './AuditEntryCard'

interface AuditViewerProps {
  items: AuditEntry[]
  isLoading?: boolean
  error?: string | null
}

export function AuditViewer({ items, isLoading = false, error = null }: AuditViewerProps) {
  const [expandedId, setExpandedId] = useState<string | null>(null)

  if (isLoading) return <ActivityLoadingState />
  if (error) return <ActivityErrorState message={error} />
  if (items.length === 0) return <ActivityEmptyState title="No audit entries" message="Audit history is empty or unavailable." />

  return (
    <div className="space-y-3" data-testid="audit-viewer">
      {items.map((entry) => (
        <AuditEntryCard
          key={entry.public_id}
          entry={entry}
          expanded={expandedId === entry.public_id}
          onToggle={() => setExpandedId((current) => (current === entry.public_id ? null : entry.public_id))}
        />
      ))}
    </div>
  )
}
