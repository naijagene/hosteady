export function resolveDocumentIcon(mimeType?: string | null, documentType?: string | null): string {
  const type = (mimeType ?? documentType ?? '').toLowerCase()

  if (type.includes('pdf')) {
    return 'PDF'
  }

  if (type.includes('image')) {
    return 'IMG'
  }

  if (type.includes('sheet') || type.includes('excel') || type.includes('csv')) {
    return 'XLS'
  }

  if (type.includes('word') || type.includes('document')) {
    return 'DOC'
  }

  if (type.includes('zip') || type.includes('archive')) {
    return 'ZIP'
  }

  if (type.includes('text')) {
    return 'TXT'
  }

  return 'FILE'
}
