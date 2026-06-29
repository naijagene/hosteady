import type { WorkflowInboxTab } from '../types'

const tabs: Array<{ id: WorkflowInboxTab; label: string }> = [
  { id: 'assigned', label: 'Assigned to me' },
  { id: 'approvals', label: 'Pending approvals' },
  { id: 'running', label: 'Running workflows' },
  { id: 'completed', label: 'Completed' },
  { id: 'failed', label: 'Failed' },
  { id: 'all', label: 'All' },
]

interface WorkflowInboxTabsProps {
  activeTab: WorkflowInboxTab
  counts?: Partial<Record<WorkflowInboxTab, number>>
  showCounts?: boolean
  onChange: (tab: WorkflowInboxTab) => void
}

export function WorkflowInboxTabs({
  activeTab,
  counts,
  showCounts = true,
  onChange,
}: WorkflowInboxTabsProps) {
  return (
    <div className="flex flex-wrap gap-2" data-testid="workflow-inbox-tabs" role="tablist" aria-label="Workflow inbox tabs">
      {tabs.map((tab) => {
        const count = counts?.[tab.id] ?? 0
        const selected = activeTab === tab.id

        return (
          <button
            key={tab.id}
            type="button"
            role="tab"
            aria-selected={selected}
            aria-controls={`workflow-inbox-panel-${tab.id}`}
            className={`rounded-md px-3 py-1.5 text-xs font-medium ${
              selected ? 'bg-primary text-primary-foreground' : 'border border-border bg-background'
            }`}
            onClick={() => onChange(tab.id)}
          >
            {tab.label}
            {showCounts ? ` (${count})` : ''}
          </button>
        )
      })}
    </div>
  )
}
