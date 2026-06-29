import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import {
  normalizeWorkflowDefinition,
  normalizeWorkflowInstance,
  type WorkflowDefinition,
  type WorkflowInstance,
} from '../types/workflows'
import { asArray } from '../types/metadata-common'

export async function fetchWorkflowDefinitions(): Promise<WorkflowDefinition[]> {
  const response = await apiClient.get<
    WorkflowDefinition[] | { data: WorkflowDefinition[] } | { data: unknown[] }
  >('tenant/workflows/definitions')

  return asArray(unwrapData(response.data)).map(normalizeWorkflowDefinition)
}

export async function fetchWorkflowInstances(): Promise<WorkflowInstance[]> {
  const response = await apiClient.get<
    WorkflowInstance[] | { data: WorkflowInstance[] } | { data: unknown[] }
  >('tenant/workflow-instances')

  return asArray(unwrapData(response.data)).map(normalizeWorkflowInstance)
}
