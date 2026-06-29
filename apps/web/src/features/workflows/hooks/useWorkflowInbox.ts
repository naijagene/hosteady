import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  fetchApprovals,
  fetchHumanTaskInbox,
  fetchHumanTasks,
  fetchWorkflowInstances,
} from '@/api/endpoints/workflows'
import type { WorkflowBindingContext } from '@/api/types/workflows'
import { buildInboxCounts, createInitialWorkflowQuery, filterWorkflowItems, resolveInboxQuery } from '../core/workflow-query'
import { toWorkflowQueryError } from '../core/workflow-errors'
import type { WorkflowInboxTab } from '../types'

export function useWorkflowInbox(binding?: WorkflowBindingContext) {
  const [activeTab, setActiveTab] = useState<WorkflowInboxTab>(
    binding?.mode === 'approvals'
      ? 'approvals'
      : binding?.mode === 'instances'
        ? 'running'
        : (binding?.inbox_type as WorkflowInboxTab) ?? 'assigned',
  )
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState(binding?.status_filter ?? '')
  const [taskTypeFilter, setTaskTypeFilter] = useState(binding?.task_type_filter ?? '')

  const tabQuery = resolveInboxQuery(activeTab)
  const perPage = binding?.per_page ?? 25

  const tasksQuery = useQuery({
    queryKey: ['workflow-inbox-tasks', activeTab, tabQuery.inboxType, perPage],
    queryFn: () =>
      tabQuery.inboxType
        ? fetchHumanTaskInbox(tabQuery.inboxType, perPage)
        : fetchHumanTasks(
            createInitialWorkflowQuery({
              per_page: perPage,
              status: tabQuery.taskStatus,
            }),
          ),
    enabled: ['assigned', 'completed', 'all'].includes(activeTab),
  })

  const approvalsQuery = useQuery({
    queryKey: ['workflow-inbox-approvals', activeTab, perPage],
    queryFn: () =>
      fetchApprovals(
        createInitialWorkflowQuery({
          per_page: perPage,
          status: tabQuery.approvalStatus ?? (statusFilter || undefined),
        }),
      ),
    enabled: activeTab === 'approvals' || binding?.show_counts === true,
  })

  const instancesQuery = useQuery({
    queryKey: ['workflow-inbox-instances', activeTab, perPage],
    queryFn: () =>
      fetchWorkflowInstances(
        createInitialWorkflowQuery({
          per_page: perPage,
          status: tabQuery.instanceStatus,
        }),
      ),
    enabled: ['running', 'completed', 'failed'].includes(activeTab) || binding?.show_counts === true,
  })

  const filteredTasks = useMemo(
    () => filterWorkflowItems(tasksQuery.data ?? [], search, statusFilter, taskTypeFilter),
    [search, statusFilter, taskTypeFilter, tasksQuery.data],
  )

  const filteredApprovals = useMemo(
    () => filterWorkflowItems(approvalsQuery.data ?? [], search, statusFilter),
    [approvalsQuery.data, search, statusFilter],
  )

  const filteredInstances = useMemo(
    () => filterWorkflowItems(instancesQuery.data ?? [], search, statusFilter),
    [instancesQuery.data, search, statusFilter],
  )

  const counts = useMemo(
    () =>
      buildInboxCounts(
        tasksQuery.data ?? [],
        approvalsQuery.data ?? [],
        instancesQuery.data ?? [],
      ),
    [approvalsQuery.data, instancesQuery.data, tasksQuery.data],
  )

  const isLoading =
    (['assigned', 'completed', 'all'].includes(activeTab) && tasksQuery.isLoading) ||
    (activeTab === 'approvals' && approvalsQuery.isLoading) ||
    (['running', 'completed', 'failed'].includes(activeTab) && instancesQuery.isLoading)

  const error =
    tasksQuery.error ?? approvalsQuery.error ?? instancesQuery.error
      ? toWorkflowQueryError(tasksQuery.error ?? approvalsQuery.error ?? instancesQuery.error)
      : null

  const refresh = async () => {
    await Promise.all([tasksQuery.refetch(), approvalsQuery.refetch(), instancesQuery.refetch()])
  }

  return {
    activeTab,
    setActiveTab,
    search,
    setSearch,
    statusFilter,
    setStatusFilter,
    taskTypeFilter,
    setTaskTypeFilter,
    tasks: filteredTasks,
    approvals: filteredApprovals,
    instances: filteredInstances,
    counts,
    isLoading,
    error,
    refresh,
  }
}
