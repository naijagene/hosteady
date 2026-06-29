import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import {
  normalizeUiPage,
  normalizeUiRenderPayload,
  type UiPage,
  type UiRenderPayload,
} from '../types/ui'
import { asArray } from '../types/metadata-common'

export async function fetchUiRuntime(): Promise<Record<string, unknown>> {
  const response = await apiClient.get<
    Record<string, unknown> | { data: Record<string, unknown> }
  >('tenant/ui/runtime')

  return unwrapData(response.data)
}

export async function fetchUiPages(): Promise<UiPage[]> {
  const response = await apiClient.get<
    UiPage[] | { data: UiPage[] } | { data: unknown[] }
  >('tenant/ui/pages')

  return asArray(unwrapData(response.data)).map(normalizeUiPage)
}

export async function fetchUiPage(
  moduleKey: string,
  pageKey: string,
): Promise<UiPage> {
  const response = await apiClient.get<
    UiPage | { data: UiPage }
  >(`tenant/ui/pages/${encodeURIComponent(moduleKey)}/${encodeURIComponent(pageKey)}`)

  return normalizeUiPage(unwrapData(response.data))
}

export async function fetchUiPageRender(
  moduleKey: string,
  pageKey: string,
): Promise<UiRenderPayload> {
  const response = await apiClient.get<
    UiRenderPayload | { data: UiRenderPayload }
  >(
    `tenant/ui/pages/${encodeURIComponent(moduleKey)}/${encodeURIComponent(pageKey)}/render`,
  )

  return normalizeUiRenderPayload(unwrapData(response.data))
}
