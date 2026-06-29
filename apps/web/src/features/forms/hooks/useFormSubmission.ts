import { useCallback, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import type { UseFormSetError } from 'react-hook-form'
import { submitForm } from '@/api/endpoints/forms'
import { ApiError } from '@/api/errors'
import type {
  FormBindingContext,
  FormSubmissionResult,
} from '@/api/types/forms'
import {
  applyBackendFieldErrors,
  flattenFieldErrors,
  toFormSubmissionError,
} from '../core/form-errors'
import {
  buildSubmissionMetadata,
  buildSubmissionPayload,
} from '../core/form-transform'
import type { FormValues, NormalizedFormDefinition } from '../types'

export function useFormSubmission(options: {
  model: NormalizedFormDefinition
  binding?: FormBindingContext
  preserveHidden?: boolean
  setError: UseFormSetError<Record<string, unknown>>
  onSuccess?: (result: FormSubmissionResult) => void
}) {
  const queryClient = useQueryClient()
  const [submissionError, setSubmissionError] = useState<string | null>(null)
  const [result, setResult] = useState<FormSubmissionResult | null>(null)

  const mutation = useMutation({
    mutationFn: async (input: {
      values: FormValues
      visibleFieldKeys: Set<string>
    }) => {
      const payloadValues = buildSubmissionPayload(input.values, options.model, {
        binding: options.binding,
        preserveHidden: options.preserveHidden,
        visibleFieldKeys: input.visibleFieldKeys,
      })

      return submitForm(
        options.model.definition.module_key,
        options.model.definition.form_key,
        {
          values: payloadValues,
          metadata: buildSubmissionMetadata(options.binding),
        },
      )
    },
    onSuccess: async (submissionResult) => {
      setSubmissionError(null)
      setResult(submissionResult)

      if (options.binding?.refresh_bindings_on_success) {
        await queryClient.invalidateQueries({ queryKey: ['form-definition'] })
        await queryClient.invalidateQueries({ queryKey: ['ui-page-render'] })
      }

      options.onSuccess?.(submissionResult)
    },
    onError: (error) => {
      const normalized = toFormSubmissionError(error)
      setResult(null)
      setSubmissionError(normalized.message)
      applyBackendFieldErrors(options.setError, normalized.field_errors)
    },
  })

  const submit = useCallback(
    async (values: FormValues, visibleFieldKeys: Set<string>) => {
      setSubmissionError(null)
      await mutation.mutateAsync({ values, visibleFieldKeys })
    },
    [mutation],
  )

  const fieldErrorSummary =
    mutation.error instanceof ApiError
      ? flattenFieldErrors(mutation.error.fieldErrors)
      : []

  return {
    submit,
    isSubmitting: mutation.isPending,
    isSuccess: mutation.isSuccess && result?.success !== false,
    result,
    submissionError,
    fieldErrorSummary,
    resetSubmission: () => {
      mutation.reset()
      setResult(null)
      setSubmissionError(null)
    },
  }
}
