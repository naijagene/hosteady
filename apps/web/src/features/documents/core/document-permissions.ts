export function hasDocumentPermission(permissions: string[], required?: string | null): boolean {
  if (!required) {
    return true
  }

  return permissions.includes(required)
}

export function canReadDocuments(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('documents.read')
}

export function canUploadDocuments(permissions: string[]): boolean {
  return permissions.includes('documents.upload')
}

export function canManageDocuments(permissions: string[]): boolean {
  return permissions.includes('documents.manage')
}

export function canDeleteDocuments(permissions: string[]): boolean {
  return permissions.includes('documents.delete') || permissions.includes('documents.manage')
}

export function canDownloadDocuments(permissions: string[]): boolean {
  return permissions.includes('documents.download') || canReadDocuments(permissions)
}
