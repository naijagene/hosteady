import { useMemo } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import type { FormBindingContext, FormDefinition } from '@/api/types/forms'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { useDynamicForm } from '../hooks/useDynamicForm'
import { useFormConditions } from '../hooks/useFormConditions'
import { useFormSubmission } from '../hooks/useFormSubmission'
import { FormActions } from './FormActions'
import { FormErrorSummary } from './FormErrorSummary'
import { FormSectionRenderer } from './FormSectionRenderer'
import { FormSuccessState } from './FormSuccessState'

interface DynamicFormRendererProps {
  definition: FormDefinition
  binding?: FormBindingContext
  mode?: 'create' | 'edit' | 'readonly'
  submitEnabled?: boolean
  successMessage?: string
  preserveHidden?: boolean
  onSubmitSuccess?: () => void
}

export function DynamicFormRenderer({
  definition,
  binding,
  mode = 'create',
  submitEnabled = true,
  successMessage,
  preserveHidden = false,
  onSubmitSuccess,
}: DynamicFormRendererProps) {
  const runtime = useHydratedRuntime()
  const permissions = runtime?.permissions ?? []
  const effectiveMode = binding?.mode ?? mode
  const isReadOnly = effectiveMode === 'readonly'
  const canSubmit = !isReadOnly && (binding?.submit_enabled ?? submitEnabled)

  const { model, defaultValues, validationRules } = useDynamicForm({
    definition,
    permissions,
  })

  const {
    register,
    control,
    handleSubmit,
    setError,
    formState: { errors, isDirty, isSubmitting },
    reset,
  } = useForm<Record<string, unknown>>({
    defaultValues,
    mode: 'onBlur',
  })

  const watchedValues = useWatch({ control })
  const values = useMemo(
    () => ({ ...defaultValues, ...watchedValues }),
    [defaultValues, watchedValues],
  )

  const { visibleFieldKeys, isFieldVisible, isFieldEnabled } = useFormConditions({
    fields: model.fields,
    formConditions: model.conditions,
    values,
  })

  const {
    submit,
    isSubmitting: isSaving,
    isSuccess,
    result,
    submissionError,
    fieldErrorSummary,
  } = useFormSubmission({
    model,
    binding,
    preserveHidden: binding?.preserve_hidden ?? preserveHidden,
    setError,
    onSuccess: onSubmitSuccess,
  })

  const clientErrors = Object.entries(errors).flatMap(([field, error]) =>
    error?.message ? [`${field}: ${String(error.message)}`] : [],
  )

  const summaryMessages = Array.from(
    new Set([
      ...(submissionError ? [submissionError] : []),
      ...clientErrors,
      ...fieldErrorSummary,
    ]),
  )

  if (isSuccess && result) {
    return (
      <FormSuccessState
        message={
          binding?.success_message ??
          successMessage ??
          `${definition.name} submitted successfully.`
        }
        result={result}
      />
    )
  }

  return (
    <form
      className="space-y-6"
      data-testid="dynamic-form-renderer"
      onSubmit={handleSubmit(async (formValues) => {
        if (!canSubmit) {
          return
        }

        try {
          await submit(formValues, visibleFieldKeys)
        } catch {
          // Submission errors are surfaced via hook state.
        }
      })}
      noValidate
    >
      <header className="space-y-1">
        <h2 className="text-lg font-semibold text-foreground">{definition.name}</h2>
        {definition.description ? (
          <p className="text-sm text-muted-foreground">{definition.description}</p>
        ) : null}
      </header>

      <FormErrorSummary messages={summaryMessages} />

      {model.sections.map((section) => (
        <FormSectionRenderer
          key={section.section_key}
          section={section}
          register={register}
          control={control}
          errors={errors}
          validationRules={validationRules}
          isFieldVisible={isFieldVisible}
          isFieldEnabled={isFieldEnabled}
          readOnly={isReadOnly}
        />
      ))}

      {canSubmit ? (
        <FormActions
          submitLabel={
            model.actions?.find((action) => action.type === 'submit')?.label ?? 'Submit'
          }
          isSubmitting={isSubmitting || isSaving}
          disabled={!isDirty && effectiveMode === 'edit'}
        />
      ) : null}

      {isReadOnly ? (
        <p className="text-xs text-muted-foreground">This form is read-only.</p>
      ) : null}

      {!canSubmit && !isReadOnly ? null : (
        <button
          type="button"
          className="text-xs text-muted-foreground underline"
          onClick={() => reset(defaultValues)}
        >
          Reset
        </button>
      )}
    </form>
  )
}
