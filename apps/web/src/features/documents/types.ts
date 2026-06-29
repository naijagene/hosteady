import type { DocumentBindingContext } from '@/api/types/documents'

export interface DocumentManagerViewState {
  viewMode: 'list' | 'grid'
  search: string
  page: number
  selectedDocumentId: string | null
  showUpload: boolean
  showDeleteConfirm: string | null
}

export interface ResolvedDocumentManagerConfig extends DocumentBindingContext {
  viewMode: 'list' | 'grid'
  compact: boolean
  pickerMode: boolean
}
