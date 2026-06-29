import { asArray, asRecord, asString, type MetadataRecord } from './metadata-common'

export interface DocumentReference {
  public_id: string
  title: string
  description?: string | null
  status?: string
  visibility?: string
  category?: string
  module_key?: string | null
  document_type?: string
  updated_at?: string | null
  created_at?: string | null
  metadata?: MetadataRecord
}

export interface DocumentItem extends DocumentReference {
  filename?: string | null
  mime_type?: string | null
  size_bytes?: number | null
  owner?: string | null
  uploader?: string | null
  tags?: DocumentTag[]
  attachment_count?: number
  version_count?: number
  current_version_number?: number
  current_version_public_id?: string | null
  platform_file_public_id?: string | null
}

export interface DocumentVersion {
  public_id: string
  document_public_id: string
  version_number: number
  platform_file_public_id?: string | null
  status?: string
  label?: string | null
  created_at?: string | null
  metadata?: MetadataRecord
}

export interface DocumentAttachment {
  public_id: string
  document_public_id: string
  subject_type: string
  subject_public_id: string
  subject_module_key?: string | null
  subject_entity_key?: string | null
  status?: string
  created_at?: string | null
  metadata?: MetadataRecord
}

export interface DocumentFolder {
  folder_key: string
  label: string
  parent_key?: string | null
  metadata?: MetadataRecord
}

export interface DocumentTag {
  tag_key: string
  label: string
  color?: string | null
  metadata?: MetadataRecord
}

export interface DocumentMetadata {
  source?: string
  page?: string
  binding?: string
  [key: string]: unknown
}

export interface DocumentFilter {
  filter_key: string
  label: string
  filter_type: string
  value?: unknown
  operator?: string
  metadata?: MetadataRecord
}

export interface DocumentSort {
  sort_key: string
  direction: 'asc' | 'desc'
}

export interface DocumentQueryPayload {
  page?: number
  per_page?: number
  search?: string
  filters?: DocumentFilter[]
  sorts?: DocumentSort[]
  metadata?: DocumentMetadata
}

export interface DocumentQueryResult {
  items: DocumentItem[]
  page: number
  per_page: number
  total: number
  has_more: boolean
}

export interface DocumentUploadPayload {
  file: File
  title?: string
  description?: string
  visibility?: string
  category?: string
  module_key?: string
  tags?: string[]
  metadata?: MetadataRecord
}

export interface DocumentUploadResult {
  document: DocumentItem
  status?: string
  message?: string | null
}

export interface DocumentBindingContext {
  mode?: 'list' | 'grid' | 'compact' | 'picker'
  query_enabled?: boolean
  search_enabled?: boolean
  upload_enabled?: boolean
  selection_enabled?: boolean
  detail_enabled?: boolean
  per_page?: number
  filters?: DocumentFilter[]
  sorts?: DocumentSort[]
  empty_state_message?: string
  source?: string
  page?: string
  binding?: string
}

export interface DocumentPickerResult {
  documents: DocumentReference[]
  selection_mode: 'single' | 'multiple'
}

export function normalizeDocumentReference(raw: unknown): DocumentReference {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    title: asString(data.title ?? data.name ?? data.filename, 'Document'),
    description: typeof data.description === 'string' ? data.description : null,
    status: asString(data.status, 'active'),
    visibility: asString(data.visibility),
    category: asString(data.category),
    module_key:
      typeof (data.module_key ?? data.moduleKey) === 'string'
        ? ((data.module_key ?? data.moduleKey) as string)
        : null,
    document_type: asString(
      data.document_type ?? data.documentType ?? data.mime_type ?? data.mimeType ?? data.category,
    ),
    updated_at:
      typeof (data.updated_at ?? data.updatedAt) === 'string'
        ? ((data.updated_at ?? data.updatedAt) as string)
        : null,
    created_at:
      typeof (data.created_at ?? data.createdAt) === 'string'
        ? ((data.created_at ?? data.createdAt) as string)
        : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDocumentTag(raw: unknown): DocumentTag {
  const data = asRecord(raw)
  return {
    tag_key: asString(data.tag_key ?? data.tagKey ?? data.key ?? data.label),
    label: asString(data.label ?? data.name, 'Tag'),
    color: typeof data.color === 'string' ? data.color : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDocumentItem(raw: unknown): DocumentItem {
  const reference = normalizeDocumentReference(raw)
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)

  return {
    ...reference,
    filename:
      typeof (data.filename ?? data.original_filename ?? data.originalFilename ?? metadata.filename) ===
      'string'
        ? ((data.filename ?? data.original_filename ?? data.originalFilename ?? metadata.filename) as string)
        : reference.title,
    mime_type:
      typeof (data.mime_type ?? data.mimeType ?? metadata.mime_type ?? metadata.mimeType) === 'string'
        ? ((data.mime_type ?? data.mimeType ?? metadata.mime_type ?? metadata.mimeType) as string)
        : null,
    size_bytes:
      typeof (data.size_bytes ?? data.sizeBytes ?? metadata.size_bytes ?? metadata.sizeBytes) === 'number'
        ? ((data.size_bytes ?? data.sizeBytes ?? metadata.size_bytes ?? metadata.sizeBytes) as number)
        : null,
    owner:
      typeof (data.owner ?? metadata.owner ?? metadata.uploaded_by ?? metadata.uploadedBy) === 'string'
        ? ((data.owner ?? metadata.owner ?? metadata.uploaded_by ?? metadata.uploadedBy) as string)
        : null,
    uploader:
      typeof (data.uploader ?? metadata.uploader) === 'string'
        ? ((data.uploader ?? metadata.uploader) as string)
        : null,
    tags: asArray(data.tags ?? metadata.tags).map(normalizeDocumentTag),
    attachment_count:
      typeof (data.attachment_count ?? data.attachmentCount ?? metadata.attachment_count) === 'number'
        ? ((data.attachment_count ?? data.attachmentCount ?? metadata.attachment_count) as number)
        : undefined,
    version_count:
      typeof (data.version_count ?? data.versionCount ?? data.current_version_number ?? data.currentVersionNumber) ===
      'number'
        ? ((data.version_count ??
            data.versionCount ??
            data.current_version_number ??
            data.currentVersionNumber) as number)
        : undefined,
    current_version_number:
      typeof (data.current_version_number ?? data.currentVersionNumber) === 'number'
        ? ((data.current_version_number ?? data.currentVersionNumber) as number)
        : undefined,
    current_version_public_id:
      typeof (data.current_version_public_id ?? data.currentVersionPublicId) === 'string'
        ? ((data.current_version_public_id ?? data.currentVersionPublicId) as string)
        : null,
    platform_file_public_id:
      typeof (data.platform_file_public_id ?? data.platformFilePublicId) === 'string'
        ? ((data.platform_file_public_id ?? data.platformFilePublicId) as string)
        : null,
  }
}

export function normalizeDocumentVersion(raw: unknown): DocumentVersion {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    document_public_id: asString(data.document_public_id ?? data.documentPublicId),
    version_number:
      typeof (data.version_number ?? data.versionNumber) === 'number'
        ? ((data.version_number ?? data.versionNumber) as number)
        : 1,
    platform_file_public_id:
      typeof (data.platform_file_public_id ?? data.platformFilePublicId) === 'string'
        ? ((data.platform_file_public_id ?? data.platformFilePublicId) as string)
        : null,
    status: asString(data.status, 'active'),
    label: typeof data.label === 'string' ? data.label : null,
    created_at:
      typeof (data.created_at ?? data.createdAt) === 'string'
        ? ((data.created_at ?? data.createdAt) as string)
        : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDocumentAttachment(raw: unknown): DocumentAttachment {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    document_public_id: asString(data.document_public_id ?? data.documentPublicId),
    subject_type: asString(data.subject_type ?? data.subjectType),
    subject_public_id: asString(data.subject_public_id ?? data.subjectPublicId),
    subject_module_key:
      typeof (data.subject_module_key ?? data.subjectModuleKey) === 'string'
        ? ((data.subject_module_key ?? data.subjectModuleKey) as string)
        : null,
    subject_entity_key:
      typeof (data.subject_entity_key ?? data.subjectEntityKey) === 'string'
        ? ((data.subject_entity_key ?? data.subjectEntityKey) as string)
        : null,
    status: asString(data.status, 'active'),
    created_at:
      typeof (data.created_at ?? data.createdAt) === 'string'
        ? ((data.created_at ?? data.createdAt) as string)
        : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDocumentFilter(raw: unknown): DocumentFilter {
  const data = asRecord(raw)
  return {
    filter_key: asString(data.filter_key ?? data.filterKey ?? data.key),
    label: asString(data.label ?? data.name, 'Filter'),
    filter_type: asString(data.filter_type ?? data.filterType ?? data.type, 'text'),
    value: data.value,
    operator: asString(data.operator, 'equals'),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDocumentSort(raw: unknown): DocumentSort {
  const data = asRecord(raw)
  const direction = asString(data.direction ?? data.order, 'asc').toLowerCase()
  return {
    sort_key: asString(data.sort_key ?? data.sortKey ?? data.key, 'updated_at'),
    direction: direction === 'desc' ? 'desc' : 'asc',
  }
}

export function normalizeDocumentQueryPayload(raw: unknown): DocumentQueryPayload {
  const data = asRecord(raw)
  return {
    page: typeof data.page === 'number' ? data.page : 1,
    per_page: typeof (data.per_page ?? data.perPage) === 'number' ? ((data.per_page ?? data.perPage) as number) : 25,
    search: typeof data.search === 'string' ? data.search : '',
    filters: asArray(data.filters).map(normalizeDocumentFilter),
    sorts: asArray(data.sorts).map(normalizeDocumentSort),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDocumentQueryResult(
  raw: unknown,
  payload: DocumentQueryPayload,
): DocumentQueryResult {
  const data = asRecord(raw)
  const itemsRaw = asArray(data.items ?? data.data ?? data.documents ?? raw)
  const items = itemsRaw.map(normalizeDocumentItem)
  const total =
    typeof (data.total ?? data.count) === 'number' ? ((data.total ?? data.count) as number) : items.length
  const page = payload.page ?? 1
  const perPage = payload.per_page ?? 25

  return {
    items,
    page,
    per_page: perPage,
    total,
    has_more: page * perPage < total,
  }
}

export function normalizeDocumentUploadResult(raw: unknown): DocumentUploadResult {
  const data = asRecord(raw)
  const documentRaw = asRecord(data.document ?? data.data ?? data)
  return {
    document: normalizeDocumentItem(documentRaw),
    status: asString(data.status, 'completed'),
    message: typeof data.message === 'string' ? data.message : null,
  }
}

export function normalizeDocumentBindingContext(
  raw: MetadataRecord | undefined,
): DocumentBindingContext {
  const config = asRecord(raw)
  return {
    mode: (['list', 'grid', 'compact', 'picker'].includes(asString(config.mode)) ? asString(config.mode) : 'list') as
      | 'list'
      | 'grid'
      | 'compact'
      | 'picker',
    query_enabled: config.query_enabled !== false && config.queryEnabled !== false,
    search_enabled: config.search_enabled !== false && config.searchEnabled !== false,
    upload_enabled: config.upload_enabled !== false && config.uploadEnabled !== false,
    selection_enabled: config.selection_enabled === true || config.selectionEnabled === true,
    detail_enabled: config.detail_enabled !== false && config.detailEnabled !== false,
    per_page:
      typeof (config.per_page ?? config.perPage) === 'number'
        ? ((config.per_page ?? config.perPage) as number)
        : 25,
    filters: asArray(config.filters).map(normalizeDocumentFilter),
    sorts: asArray(config.sorts).map(normalizeDocumentSort),
    empty_state_message: asString(config.empty_state_message ?? config.emptyStateMessage),
    source: asString(config.source, 'web') || 'web',
    page: asString(config.page),
    binding: asString(config.binding),
  }
}

export function buildDocumentQueryRequest(payload: DocumentQueryPayload): Record<string, unknown> {
  return {
    page: payload.page ?? 1,
    per_page: payload.per_page ?? 25,
    limit: payload.per_page ?? 25,
    search: payload.search ?? '',
    filters: payload.filters ?? [],
    sorts: payload.sorts ?? [],
    metadata: payload.metadata ?? { source: 'web' },
  }
}

export function buildDocumentUploadFormData(payload: DocumentUploadPayload): FormData {
  const formData = new FormData()
  formData.append('file', payload.file)

  if (payload.title) {
    formData.append('title', payload.title)
  }

  if (payload.description) {
    formData.append('description', payload.description)
  }

  if (payload.visibility) {
    formData.append('visibility', payload.visibility)
  }

  if (payload.category) {
    formData.append('category', payload.category)
  }

  if (payload.module_key) {
    formData.append('module_key', payload.module_key)
  }

  if (payload.metadata) {
    formData.append('metadata', JSON.stringify(payload.metadata))
  }

  if (payload.tags?.length) {
    formData.append('tags', JSON.stringify(payload.tags))
  }

  return formData
}
