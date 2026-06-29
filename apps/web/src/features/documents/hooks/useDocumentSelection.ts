import { useCallback, useState } from 'react'
import type { DocumentReference } from '@/api/types/documents'
import { isDocumentSelected, toggleDocumentSelection } from '../core/document-selection'

export function useDocumentSelection(multiple = false) {
  const [selected, setSelected] = useState<DocumentReference[]>([])

  const toggleSelection = useCallback(
    (document: DocumentReference) => {
      setSelected((current) => toggleDocumentSelection(current, document, multiple))
    },
    [multiple],
  )

  const clearSelection = useCallback(() => {
    setSelected([])
  }, [])

  return {
    selected,
    setSelected,
    toggleSelection,
    clearSelection,
    isSelected: (publicId: string) => isDocumentSelected(selected, publicId),
  }
}
