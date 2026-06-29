export function getDocumentActionPlaceholder(action: string): string {
  switch (action.toLowerCase()) {
    case 'replace_version':
      return 'Replace version is not implemented yet.'
    case 'attach_record':
      return 'Attach to record is not implemented yet.'
    case 'preview':
      return 'Preview is not available in this milestone.'
    default:
      return `${action} is not supported yet.`
  }
}

export function getDeleteConfirmationMessage(title: string): string {
  return `Delete ${title}? This action cannot be undone.`
}
