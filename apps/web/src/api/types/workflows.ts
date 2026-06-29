import { asRecord, asString, type MetadataRecord } from './metadata-common'

export interface WorkflowDefinition {
  public_id: string
  name: string
  definition_key?: string
  status?: string
  metadata?: MetadataRecord
}

export interface WorkflowInstance {
  public_id: string
  definition_public_id?: string
  status?: string
  started_at?: string | null
  metadata?: MetadataRecord
}

export function normalizeWorkflowDefinition(raw: unknown): WorkflowDefinition {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    name: asString(data.name, 'Workflow'),
    definition_key: asString(data.definition_key ?? data.definitionKey),
    status: asString(data.status),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeWorkflowInstance(raw: unknown): WorkflowInstance {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    definition_public_id: asString(
      data.definition_public_id ?? data.definitionPublicId,
    ),
    status: asString(data.status),
    started_at:
      typeof (data.started_at ?? data.startedAt) === 'string'
        ? ((data.started_at ?? data.startedAt) as string)
        : null,
    metadata: asRecord(data.metadata),
  }
}
