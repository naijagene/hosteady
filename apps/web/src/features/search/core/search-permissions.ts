export function canSearch(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('search.read')
}

export function canViewSearchResult(permissions: string[], required?: string | null): boolean {
  if (!required) {
    return true
  }

  if (permissions.length === 0) {
    return true
  }

  return permissions.includes(required)
}

export function filterResultsByPermission<T extends { permission?: string | null }>(
  items: T[],
  permissions: string[],
): T[] {
  return items.filter((item) => canViewSearchResult(permissions, item.permission))
}
