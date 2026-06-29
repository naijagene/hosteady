import type { SearchResult, SearchResultType } from '@/api/types/search'

const typePriority: Record<string, number> = {
  command: 120,
  navigation: 110,
  page: 105,
  application: 100,
  favorite: 95,
  shortcut: 90,
  recent: 85,
  document: 80,
  report: 75,
  dashboard: 70,
  workflow: 65,
  task: 60,
  approval: 58,
  notification: 55,
  record: 50,
  setting: 45,
  workspace: 40,
  user: 35,
  custom: 10,
}

export function scoreSearchResult(result: SearchResult, query: string): number {
  const normalizedQuery = query.trim().toLowerCase()
  if (!normalizedQuery) {
    return (typePriority[result.type] ?? 0) + (result.rank ?? 0)
  }

  const title = result.title.toLowerCase()
  const description = (result.description ?? '').toLowerCase()
  let score = typePriority[result.type] ?? 0

  if (title === normalizedQuery) {
    score += 1000
  } else if (title.startsWith(normalizedQuery)) {
    score += 500
  } else if (title.includes(normalizedQuery)) {
    score += 250
  }

  if (description.includes(normalizedQuery)) {
    score += 100
  }

  if (result.source === 'personalization') {
    score += 40
  }

  if (result.source === 'command') {
    score += 30
  }

  score += result.rank ?? 0
  score += result.confidence ?? 0

  return score
}

export function rankSearchResults(results: SearchResult[], query: string): SearchResult[] {
  return [...results]
    .map((result) => ({
      ...result,
      rank: scoreSearchResult(result, query),
    }))
    .sort((left, right) => (right.rank ?? 0) - (left.rank ?? 0))
}

export function getTypePriority(type: SearchResultType | string): number {
  return typePriority[type] ?? 0
}
