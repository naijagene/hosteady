import { asArray, asNumber, asRecord, asString, type MetadataRecord } from './metadata-common'

export type SearchResultType =
  | 'application'
  | 'page'
  | 'navigation'
  | 'document'
  | 'report'
  | 'dashboard'
  | 'workflow'
  | 'task'
  | 'approval'
  | 'notification'
  | 'record'
  | 'command'
  | 'shortcut'
  | 'favorite'
  | 'recent'
  | 'setting'
  | 'user'
  | 'workspace'
  | 'custom'

export type SearchResultSource = 'backend' | 'runtime' | 'personalization' | 'command' | 'local'

export interface SearchFilter {
  key: string
  value: string
}

export interface SearchFacet {
  key: string
  label: string
  count?: number
}

export interface SearchSuggestion {
  label: string
  query?: string
  type?: SearchResultType | string
  metadata?: MetadataRecord
}

export interface SearchRecentItem {
  query: string
  occurred_at?: string | null
  metadata?: MetadataRecord
}

export interface CommandAction {
  action_type: 'navigate' | 'execute_command' | 'open_dialog' | 'copy_reference' | 'open_external' | 'unsupported'
  route?: string | null
  command_key?: string | null
  metadata?: MetadataRecord
}

export interface SearchCommand {
  command_key: string
  title: string
  description?: string | null
  category?: string | null
  permission?: string | null
  action: CommandAction
  keywords?: string[]
}

export interface SearchResult {
  id: string
  title: string
  description?: string | null
  type: SearchResultType | string
  icon?: string | null
  route?: string | null
  source: SearchResultSource
  rank?: number
  confidence?: number
  permission?: string | null
  metadata?: MetadataRecord
  action?: CommandAction
}

export interface SearchQueryPayload {
  query?: string
  types?: Array<SearchResultType | string>
  filters?: SearchFilter[]
  limit?: number
  metadata?: MetadataRecord
}

export interface SearchQueryResult {
  query: string
  items: SearchResult[]
  total: number
  source: SearchResultSource
  facets?: SearchFacet[]
  suggestions?: SearchSuggestion[]
}

export interface CommandPaletteState {
  open: boolean
  query: string
  activeIndex: number
}

export interface UniversalFinderContext {
  include_backend?: boolean
  include_runtime?: boolean
  include_personalization?: boolean
  include_commands?: boolean
  limit?: number
}

export interface SearchBindingContext {
  mode?: 'compact' | 'full' | 'widget'
  placeholder?: string
  show_recent?: boolean
  show_favorites?: boolean
  show_shortcuts?: boolean
  per_page?: number
}

function normalizeAction(raw: unknown): CommandAction {
  const data = asRecord(raw)
  const actionType = asString(data.action_type ?? data.actionType ?? data.type, 'navigate')
  const allowed = ['navigate', 'execute_command', 'open_dialog', 'copy_reference', 'open_external', 'unsupported']
  return {
    action_type: (allowed.includes(actionType) ? actionType : 'unsupported') as CommandAction['action_type'],
    route: typeof (data.route ?? data.href) === 'string' ? ((data.route ?? data.href) as string) : null,
    command_key: asString(data.command_key ?? data.commandKey),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeSearchResultType(value?: string | null): SearchResultType | string {
  const normalized = asString(value, 'custom').toLowerCase()
  const known: SearchResultType[] = [
    'application',
    'page',
    'navigation',
    'document',
    'report',
    'dashboard',
    'workflow',
    'task',
    'approval',
    'notification',
    'record',
    'command',
    'shortcut',
    'favorite',
    'recent',
    'setting',
    'user',
    'workspace',
    'custom',
  ]
  return known.includes(normalized as SearchResultType) ? (normalized as SearchResultType) : normalized
}

export function normalizeSearchResult(raw: unknown, source: SearchResultSource = 'backend'): SearchResult {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  const entityType = asString(data.entity_type ?? data.entityType ?? data.type ?? metadata.type, 'custom')
  const publicId = asString(data.public_id ?? data.publicId ?? data.id ?? data.entity_public_id ?? data.entityPublicId)
  return {
    id: publicId || `${entityType}-${asString(data.display_name ?? data.title ?? data.label)}`,
    title: asString(data.display_name ?? data.displayName ?? data.title ?? data.label, 'Result'),
    description: typeof (data.description ?? data.summary ?? data.keywords ?? data.body) === 'string'
      ? ((data.description ?? data.summary ?? data.keywords ?? data.body) as string)
      : null,
    type: normalizeSearchResultType(entityType),
    icon: asString(data.icon ?? metadata.icon) || null,
    route: typeof (data.route ?? metadata.route ?? metadata.href) === 'string'
      ? ((data.route ?? metadata.route ?? metadata.href) as string)
      : null,
    source: (asString(data.source) as SearchResultSource) || source,
    rank: typeof data.rank === 'number' ? data.rank : undefined,
    confidence: typeof (data.confidence ?? data.score) === 'number' ? ((data.confidence ?? data.score) as number) : undefined,
    permission: typeof (data.permission ?? metadata.permission ?? metadata.required_permission) === 'string'
      ? ((data.permission ?? metadata.permission ?? metadata.required_permission) as string)
      : null,
    metadata,
    action: data.action ? normalizeAction(data.action) : undefined,
  }
}

export function normalizeSearchSuggestion(raw: unknown): SearchSuggestion {
  const data = asRecord(raw)
  return {
    label: asString(data.label ?? data.query ?? data.title),
    query: asString(data.query ?? data.label),
    type: normalizeSearchResultType(asString(data.type ?? data.entity_type)),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeSearchRecentItem(raw: unknown): SearchRecentItem {
  const data = asRecord(raw)
  return {
    query: asString(data.query ?? data.q ?? data.label),
    occurred_at: typeof (data.occurred_at ?? data.occurredAt ?? data.created_at) === 'string'
      ? ((data.occurred_at ?? data.occurredAt ?? data.created_at) as string)
      : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeSearchQueryResult(raw: unknown, fallbackQuery = ''): SearchQueryResult {
  const data = asRecord(raw)
  const items = asArray(data.items ?? data.results ?? data.data).map((item) => normalizeSearchResult(item, 'backend'))
  return {
    query: asString(data.query ?? data.q, fallbackQuery),
    items,
    total: asNumber(data.total, items.length),
    source: 'backend',
    facets: asArray(data.facets).map((facet) => {
      const entry = asRecord(facet)
      return {
        key: asString(entry.key),
        label: asString(entry.label ?? entry.key),
        count: typeof entry.count === 'number' ? entry.count : undefined,
      }
    }),
    suggestions: asArray(data.suggestions).map(normalizeSearchSuggestion),
  }
}

export function normalizeSearchBindingContext(raw: MetadataRecord | undefined): SearchBindingContext {
  const config = asRecord(raw)
  const mode = asString(config.mode)
  return {
    mode: mode === 'compact' || mode === 'widget' ? mode : 'full',
    placeholder: asString(config.placeholder),
    show_recent: config.show_recent !== false && config.showRecent !== false,
    show_favorites: config.show_favorites !== false && config.showFavorites !== false,
    show_shortcuts: config.show_shortcuts !== false && config.showShortcuts !== false,
    per_page: asNumber(config.per_page ?? config.perPage, 20) || 20,
  }
}

export function buildSearchQueryRequest(payload: SearchQueryPayload = {}): Record<string, unknown> {
  return {
    q: payload.query ?? '',
    query: payload.query ?? '',
    types: payload.types,
    filters: payload.filters,
    limit: payload.limit ?? 20,
    metadata: payload.metadata ?? { source: 'web' },
  }
}
