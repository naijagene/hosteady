import { useQuery } from '@tanstack/react-query'
import { fetchUiPageRender } from '@/api/endpoints/ui'
import type { UiRenderPayload } from '@/api/types/ui'

export function useUiPageRender(moduleKey: string, pageKey: string) {
  return useQuery<UiRenderPayload>({
    queryKey: ['ui-page-render', moduleKey, pageKey],
    queryFn: () => fetchUiPageRender(moduleKey, pageKey),
    enabled: Boolean(moduleKey && pageKey),
    retry: 1,
  })
}
