import { useMemo, useState } from 'react'
import type { ActivityEntry } from '@/api/types/activity'
import { labelActivitySource } from '@/api/endpoints/activity'
import { useActivityActions } from '../hooks/useActivityActions'
import { useActivityFeed } from '../hooks/useActivityFeed'
import { useActivityFilters } from '../hooks/useActivityFilters'
import { useAuditLog } from '../hooks/useAuditLog'
import { useActivityTimeline } from '../hooks/useActivityTimeline'
import { ActivityCenterTabs } from './ActivityCenterTabs'
import { ActivityDetailDrawer } from './ActivityDetailDrawer'
import { ActivityFeed } from './ActivityFeed'
import { ActivityFilterPanel } from './ActivityFilterPanel'
import { ActivityTimelineView } from './ActivityTimeline'
import { ActivityToolbar } from './ActivityToolbar'
import { AuditViewer } from './AuditViewer'

const tabs = [
  { key: 'recent', label: 'Recent activity' },
  { key: 'audit', label: 'Audit log' },
  { key: 'history', label: 'System history' },
  { key: 'mine', label: 'My activity' },
  { key: 'workflow', label: 'Workflow' },
  { key: 'documents', label: 'Documents' },
  { key: 'records', label: 'Records' },
  { key: 'security', label: 'Security' },
]

interface ActivityCenterProps {
  title?: string
}

export function ActivityCenter({ title = 'Activity Center' }: ActivityCenterProps) {
  const { query, activeTab, setSearch, setTab, setQuery } = useActivityFilters()
  const feed = useActivityFeed(query)
  const audit = useAuditLog(query)
  const timeline = useActivityTimeline(feed.items, 'full')
  const { openEntry } = useActivityActions()
  const [selected, setSelected] = useState<ActivityEntry | null>(null)

  const sourceLabel = useMemo(() => labelActivitySource(feed.source), [feed.source])

  return (
    <div className="space-y-4" data-testid="activity-center">
      <div>
        <h1 className="text-2xl font-semibold text-foreground">{title}</h1>
        <p className="mt-1 text-sm text-muted-foreground">Enterprise activity, audit, and system history.</p>
      </div>

      <ActivityCenterTabs tabs={tabs} activeTab={activeTab} onChange={setTab} />
      <ActivityToolbar search={query.search ?? ''} onSearchChange={setSearch} onRefresh={feed.refresh} source={sourceLabel} />
      <ActivityFilterPanel query={query} onChange={(patch) => setQuery((current) => ({ ...current, ...patch }))} />

      {activeTab === 'audit' ? (
        <AuditViewer items={audit.items} isLoading={audit.isLoading} error={audit.error?.message ?? null} />
      ) : activeTab === 'history' ? (
        <ActivityTimelineView groups={timeline} onSelect={(entry) => setSelected(entry)} />
      ) : (
        <ActivityFeed
          items={feed.items}
          isLoading={feed.isLoading}
          error={feed.error?.message ?? null}
          onSelect={(entry) => setSelected(entry)}
        />
      )}

      <ActivityDetailDrawer
        entry={selected}
        open={Boolean(selected)}
        onClose={() => setSelected(null)}
        onOpenResource={(entry) => void openEntry(entry)}
      />
    </div>
  )
}
