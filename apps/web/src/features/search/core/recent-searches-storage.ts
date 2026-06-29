const STORAGE_KEY = 'heos.recent-searches'

export function readRecentSearches(): string[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return []
    }
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? parsed.filter((item) => typeof item === 'string') : []
  } catch {
    return []
  }
}

export function writeRecentSearch(query: string, limit = 8): string[] {
  const trimmed = query.trim()
  if (!trimmed) {
    return readRecentSearches()
  }

  const next = [trimmed, ...readRecentSearches().filter((item) => item !== trimmed)].slice(0, limit)
  localStorage.setItem(STORAGE_KEY, JSON.stringify(next))
  return next
}
