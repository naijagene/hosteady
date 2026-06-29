import { useCallback, useState } from 'react'
import type { DocumentPickerResult, DocumentReference } from '@/api/types/documents'

export function useDocumentPicker(options?: { multiple?: boolean }) {
  const [open, setOpen] = useState(false)
  const [selection, setSelection] = useState<DocumentReference[]>([])

  const openPicker = useCallback(() => {
    setOpen(true)
  }, [])

  const closePicker = useCallback(() => {
    setOpen(false)
  }, [])

  const confirmSelection = useCallback((): DocumentPickerResult => {
    return {
      documents: selection,
      selection_mode: options?.multiple ? 'multiple' : 'single',
    }
  }, [options?.multiple, selection])

  return {
    open,
    selection,
    setSelection,
    openPicker,
    closePicker,
    confirmSelection,
  }
}
