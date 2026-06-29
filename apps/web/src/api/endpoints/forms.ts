import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import {
  normalizeFormDefinition,
  type FormDefinition,
  type FormSubmissionPayload,
} from '../types/forms'
import { asArray } from '../types/metadata-common'

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
): Promise<Record<string, unknown>> {
  const response = await apiClient.post<
    Record<string, unknown> | { data: Record<string, unknown> }
  >(
    `tenant/forms/${encodeURIComponent(moduleKey)}/${encodeURIComponent(formKey)}/submit`,
    payload,
  )

  return unwrapData(response.data)
}
