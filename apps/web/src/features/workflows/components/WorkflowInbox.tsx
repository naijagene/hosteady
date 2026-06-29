import { useMemo, useState } from 'react'
import type { Approval, HumanTask } from '@/api/types/workflows'
import type { WorkflowBindingContext } from '@/api/types/workflows'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { canReadApprovals, canReadTasks, canReadWorkflows } from '../core/workflow-permissions'
import { useWorkflowInbox } from '../hooks/useWorkflowInbox'
import { WorkflowApprovalDialog } from './WorkflowApprovalDialog'
import { WorkflowApprovalList } from './WorkflowApprovalList'
import { WorkflowErrorState } from './WorkflowErrorState'
import { WorkflowInboxTabs } from './WorkflowInboxTabs'
import { WorkflowInstanceList } from './WorkflowInstanceList'
import { WorkflowLoadingState } from './WorkflowLoadingState'
import { WorkflowTaskDetailDrawer } from './WorkflowTaskDetailDrawer'
import { WorkflowTaskList } from './WorkflowTaskList'

interface WorkflowInboxProps {
  binding?: WorkflowBindingContext
  title?: string
}

export function WorkflowInbox({ binding, title = 'Workflow Inbox' }: WorkflowInboxProps) {
  const runtime = useHydratedRuntime()
  const permissions = runtime?.permissions ?? []
  const canReadTaskItems = canReadTasks(permissions)
  const canReadApprovalItems = canReadApprovals(permissions)
  const canReadInstanceItems = canReadWorkflows(permissions)

  const inbox = useWorkflowInbox(binding)
  const [selectedTask, setSelectedTask] = useState<HumanTask | null>(null)
  const [selectedApproval, setSelectedApproval] = useState<Approval | null>(null)
  const [taskDrawerOpen, setTaskDrawerOpen] = useState(false)
  const [approvalDialogOpen, setApprovalDialogOpen] = useState(false)

  const compact = binding?.mode === 'compact'
  const showCounts = binding?.show_counts !== false

  const panelContent = useMemo(() => {
    switch (inbox.activeTab) {
      case 'approvals':
        return (
          <WorkflowApprovalList
            approvals={inbox.approvals}
            onOpen={(approval) => {
              setSelectedApproval(approval)
              setApprovalDialogOpen(true)
            }}
            emptyMessage={binding?.empty_state_message}
          />
        )
      case 'running':
      case 'failed':
        return (
          <WorkflowInstanceList
            instances={inbox.instances}
            emptyMessage={binding?.empty_state_message}
          />
        )
      case 'completed':
        if (inbox.instances.length > 0) {
          return (
            <WorkflowInstanceList
              instances={inbox.instances}
              emptyMessage={binding?.empty_state_message}
            />
          )
        }
        return (
          <WorkflowTaskList
            tasks={inbox.tasks}
            onOpen={(task) => {
              setSelectedTask(task)
              setTaskDrawerOpen(true)
            }}
            emptyMessage={binding?.empty_state_message}
          />
        )
      default:
        return (
          <WorkflowTaskList
            tasks={inbox.tasks}
            onOpen={(task) => {
              setSelectedTask(task)
              setTaskDrawerOpen(true)
            }}
            emptyMessage={binding?.empty_state_message}
          />
        )
    }
  }, [binding?.empty_state_message, inbox.activeTab, inbox.approvals, inbox.instances, inbox.tasks])

  if (!canReadTaskItems && !canReadApprovalItems && !canReadInstanceItems) {
    return <WorkflowErrorState message="You do not have permission to view workflow inbox items." />
  }

  return (
    <section className="space-y-4" data-testid="workflow-inbox">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-lg font-semibold text-foreground">{title}</h2>
        <button
          type="button"
          className="rounded-md border border-border px-3 py-1.5 text-xs font-medium"
          onClick={() => inbox.refresh()}
          aria-label="Refresh workflow inbox"
        >
          Refresh
        </button>
      </div>

      {!compact ? (
        <WorkflowInboxTabs
          activeTab={inbox.activeTab}
          counts={inbox.counts}
          showCounts={showCounts}
          onChange={inbox.setActiveTab}
        />
      ) : null}

      <div className="grid gap-3 md:grid-cols-3">
        <label className="md:col-span-2">
          <span className="sr-only">Search workflow inbox</span>
          <input
            type="search"
            className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
            placeholder="Search tasks, approvals, or instances"
            value={inbox.search}
            onChange={(event) => inbox.setSearch(event.target.value)}
            aria-label="Search workflow inbox"
          />
        </label>
        <select
          className="rounded-md border border-border bg-background px-3 py-2 text-sm"
          value={inbox.statusFilter}
          onChange={(event) => inbox.setStatusFilter(event.target.value)}
          aria-label="Filter by status"
        >
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="assigned">Assigned</option>
          <option value="running">Running</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
        </select>
        <select
          className="rounded-md border border-border bg-background px-3 py-2 text-sm"
          value={inbox.taskTypeFilter}
          onChange={(event) => inbox.setTaskTypeFilter(event.target.value)}
          aria-label="Filter by task type"
        >
          <option value="">All task types</option>
          <option value="task">Task</option>
          <option value="approval">Approval</option>
        </select>
      </div>

      {inbox.isLoading ? <WorkflowLoadingState /> : null}
      {!inbox.isLoading && inbox.error ? <WorkflowErrorState message={inbox.error.message} /> : null}
      {!inbox.isLoading && !inbox.error ? (
        <div id={`workflow-inbox-panel-${inbox.activeTab}`} role="tabpanel">
          {panelContent}
        </div>
      ) : null}

      <WorkflowTaskDetailDrawer
        task={selectedTask}
        open={taskDrawerOpen}
        permissions={permissions}
        actionsEnabled={binding?.actions_enabled !== false}
        commentsEnabled={binding?.comments_enabled !== false}
        onClose={() => {
          setTaskDrawerOpen(false)
          setSelectedTask(null)
        }}
      />

      <WorkflowApprovalDialog
        approval={selectedApproval}
        open={approvalDialogOpen}
        permissions={permissions}
        onClose={() => {
          setApprovalDialogOpen(false)
          setSelectedApproval(null)
        }}
      />
    </section>
  )
}
