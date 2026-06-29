import { useEffect, useMemo, useState } from 'react'
import { deleteDocument } from '@/api/endpoints/documents'
import type { DocumentBindingContext, DocumentItem, DocumentReference } from '@/api/types/documents'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { getDeleteConfirmationMessage } from '../core/document-actions'
import {
  canDeleteDocuments,
  canDownloadDocuments,
  canReadDocuments,
  canUploadDocuments,
} from '../core/document-permissions'
import { useDocumentSelection } from '../hooks/useDocumentSelection'
import { useDocumentsQuery } from '../hooks/useDocumentsQuery'
import { DocumentDetailDrawer } from './DocumentDetailDrawer'
import { DocumentEmptyState } from './DocumentEmptyState'
import { DocumentErrorState } from './DocumentErrorState'
import { DocumentFilterPanel } from './DocumentFilterPanel'
import { DocumentGrid } from './DocumentGrid'
import { DocumentList } from './DocumentList'
import { DocumentLoadingState } from './DocumentLoadingState'
import { DocumentToolbar } from './DocumentToolbar'
import { DocumentUploadPanel } from './DocumentUploadPanel'

interface DocumentManagerProps {
  binding?: DocumentBindingContext
  title?: string
  onSelectionChange?: (selection: DocumentReference[]) => void
  initialDocumentId?: string | null
}

export function DocumentManager({
  binding,
  title = 'Documents',
  onSelectionChange,
  initialDocumentId = null,
}: DocumentManagerProps) {
  const runtime = useHydratedRuntime()
  const permissions = runtime?.permissions ?? []
  const canRead = canReadDocuments(permissions)
  const canUpload = canUploadDocuments(permissions) && binding?.upload_enabled !== false
  const canDelete = canDeleteDocuments(permissions)
  const canDownload = canDownloadDocuments(permissions)

  const initialViewMode = binding?.mode === 'grid' ? 'grid' : 'list'
  const compact = binding?.mode === 'compact' || binding?.mode === 'picker'

  const [viewMode, setViewMode] = useState<'list' | 'grid'>(initialViewMode)
  const [search, setSearch] = useState('')
  const [selectedDocument, setSelectedDocument] = useState<DocumentItem | null>(null)
  const [drawerOpen, setDrawerOpen] = useState(Boolean(initialDocumentId))
  const [showUpload, setShowUpload] = useState(false)
  const [deleteConfirmId, setDeleteConfirmId] = useState<string | null>(null)
  const [actionMessage, setActionMessage] = useState<string | null>(null)

  const { query, updateQueryPayload, result, error, refresh } = useDocumentsQuery(binding)
  const selection = useDocumentSelection(binding?.selection_enabled === true)

  const documents = useMemo(() => result?.items ?? [], [result?.items])
  const selectedIds = selection.selected.map((item) => item.public_id)

  const activeDocument = useMemo(() => {
    if (selectedDocument) {
      return selectedDocument
    }

    if (initialDocumentId) {
      return documents.find((document) => document.public_id === initialDocumentId) ?? null
    }

    return null
  }, [documents, initialDocumentId, selectedDocument])

  useEffect(() => {
    onSelectionChange?.(selection.selected)
  }, [onSelectionChange, selection.selected])

  if (!canRead) {
    return (
      <DocumentErrorState message="You do not have permission to view documents." />
    )
  }

  if (query.isLoading) {
    return <DocumentLoadingState />
  }

  if (error) {
    return <DocumentErrorState message={error.message} />
  }

  const handleOpen = (document: DocumentItem) => {
    if (binding?.detail_enabled === false) {
      return
    }

    setSelectedDocument(document)
    setDrawerOpen(true)
  }

  const handleToggleSelect = (document: DocumentItem) => {
    selection.toggleSelection(document)
  }

  const handleDelete = async () => {
    const target = documents.find((document) => document.public_id === deleteConfirmId)
    if (!target) {
      setDeleteConfirmId(null)
      return
    }

    if (!window.confirm(getDeleteConfirmationMessage(target.title))) {
      return
    }

    try {
      await deleteDocument(target.public_id)
      setActionMessage(`Deleted ${target.title}`)
      setDeleteConfirmId(null)
      refresh()
    } catch {
      setActionMessage('Unable to delete document.')
    }
  }

  return (
    <section className="overflow-hidden rounded-lg border border-border bg-card" data-testid="document-manager" aria-label={title}>
      <DocumentToolbar
        title={title}
        viewMode={viewMode}
        search={search}
        searchEnabled={binding?.search_enabled !== false}
        compact={compact}
        onSearchChange={(value) => {
          setSearch(value)
          updateQueryPayload({ search: value, page: 1 })
        }}
        onViewModeChange={setViewMode}
        canUpload={canUpload}
        canDelete={canDelete && Boolean(deleteConfirmId)}
        canDownload={canDownload && Boolean(activeDocument)}
        onUpload={() => setShowUpload((current) => !current)}
        onRefresh={refresh}
        onDelete={handleDelete}
        onDownload={() => setActionMessage('Download link unavailable. Preview rendering is not enabled.')}
        actionMessage={actionMessage}
      />

      <div className="space-y-4 p-4">
        <DocumentFilterPanel
          filters={binding?.filters ?? []}
          onFilterChange={(filterKey, value) =>
            updateQueryPayload({
              filters: (binding?.filters ?? []).map((filter) =>
                filter.filter_key === filterKey ? { ...filter, value } : filter,
              ),
              page: 1,
            })
          }
        />

        {showUpload && canUpload ? (
          <DocumentUploadPanel enabled onSuccess={() => { setShowUpload(false); refresh() }} />
        ) : null}

        {documents.length === 0 ? (
          <DocumentEmptyState message={binding?.empty_state_message} />
        ) : viewMode === 'grid' ? (
          <DocumentGrid
            documents={documents}
            selectionEnabled={binding?.selection_enabled === true}
            selectedIds={selectedIds}
            onOpen={handleOpen}
            onToggleSelect={handleToggleSelect}
          />
        ) : (
          <DocumentList
            documents={documents}
            selectionEnabled={binding?.selection_enabled === true}
            selectedIds={selectedIds}
            onOpen={handleOpen}
            onToggleSelect={(document) => {
              handleToggleSelect(document)
              setDeleteConfirmId(document.public_id)
            }}
          />
        )}

        {result ? (
          <div className="flex items-center justify-between text-xs text-muted-foreground">
            <span>
              Showing {documents.length} of {result.total}
            </span>
            <div className="flex gap-2">
              <button
                type="button"
                className="rounded-md border border-border px-2 py-1 disabled:opacity-50"
                aria-label="Previous page"
                disabled={(result.page ?? 1) <= 1}
                onClick={() => updateQueryPayload({ page: Math.max((result.page ?? 1) - 1, 1) })}
              >
                Previous
              </button>
              <button
                type="button"
                className="rounded-md border border-border px-2 py-1 disabled:opacity-50"
                aria-label="Next page"
                disabled={!result.has_more}
                onClick={() => updateQueryPayload({ page: (result.page ?? 1) + 1 })}
              >
                Next
              </button>
            </div>
          </div>
        ) : null}
      </div>

      <DocumentDetailDrawer
        document={activeDocument}
        open={drawerOpen && binding?.detail_enabled !== false}
        onClose={() => setDrawerOpen(false)}
        canDownload={canDownload}
      />
    </section>
  )
}
