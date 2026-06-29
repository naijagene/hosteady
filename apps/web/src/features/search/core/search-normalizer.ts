import type { SearchResult } from '@/api/types/search'

export function getSearchResultLabel(result: SearchResult): string {
  return result.title
}

export function getSearchResultDescription(result: SearchResult): string {
  return result.description ?? result.type
}

export function formatSearchSource(source: SearchResult['source']): string {
  switch (source) {
    case 'backend':
      return 'Platform search'
    case 'runtime':
      return 'Runtime'
    case 'personalization':
      return 'Personalization'
    case 'command':
      return 'Command'
    default:
      return 'Local'
  }
}
