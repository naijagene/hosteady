import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import {
  normalizeTableDefinition,
  normalizeTableQueryResult,
  type TableDefinition,
  type TableQueryPayload,
  type TableQueryResult,
} from '../types/tables'
import { asArray } from '../types/metadata-common'
import type { AxiosError } from 'axios'
import type { ApiErrorBody } from '../types/api'

export async function fetchTables(): Promise<TableDefinition[]> {
  const response = await apiClient.get<
    TableDefinition[] | { data: TableDefinition[] } | { data: unknown[] }
  >('tenant/tables')

  return asArray(unwrapData(response.data)).map(normalizeTableDefinition)
}

export async function fetchTableDefinition(
  moduleKey: string,
  tableKey: string,
): Promise<TableDefinition> {
  const response = await apiClient.get<
    TableDefinition | { data: TableDefinition }
  >(
    `tenant/tables/${encodeURIComponent(moduleKey)}/${encodeURIComponent(tableKey)}`,
  )

  return normalizeTableDefinition(unwrapData(response.data))
}

export async function queryTable(
  moduleKey: string,
  tableKey: string,
  payload: TableQueryPayload = {},
): Promise<TableQueryResult> {
  try {
    const response = await apiClient.post<
      TableQueryResult | { data: TableQueryResult }
    >(
      `tenant/tables/${encodeURIComponent(moduleKey)}/${encodeURIComponent(tableKey)}/query`,
      payload,
    )

    return normalizeTableQueryResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}
