import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import {
  normalizeFormDefinition,
  normalizeFormSubmissionResult,
  type FormDefinition,
  type FormSubmissionPayload,
  type FormSubmissionResult,
} from '../types/forms'
import { asArray } from '../types/metadata-common'
import type { AxiosError } from 'axios'
import type { ApiErrorBody } from '../types/api'

export async function fetchForms(): Promise<FormDefinition[]> {
  const response = await apiClient.get<
    FormDefinition[] | { data: FormDefinition[] } | { data: unknown[] }
  >('tenant/forms')

  return asArray(unwrapData(response.data)).map(normalizeFormDefinition)
}

export async function fetchFormDefinition(
  moduleKey: string,
  formKey: string,
): Promise<FormDefinition> {
  const response = await apiClient.get<
    FormDefinition | { data: FormDefinition }
  >(
    `tenant/forms/${encodeURIComponent(moduleKey)}/${encodeURIComponent(formKey)}`,
  )

  return normalizeFormDefinition(unwrapData(response.data))
}

export async function submitForm(
  moduleKey: string,
  formKey: string,
  payload: FormSubmissionPayload,
): Promise<FormSubmissionResult> {
  try {
    const response = await apiClient.post<
      FormSubmissionResult | { data: FormSubmissionResult }
    >(
      `tenant/forms/${encodeURIComponent(moduleKey)}/${encodeURIComponent(formKey)}/submit`,
      payload,
    )

    return normalizeFormSubmissionResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}
