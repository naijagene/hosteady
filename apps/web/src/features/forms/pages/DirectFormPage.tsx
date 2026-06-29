import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'
import { fetchFormDefinition } from '@/api/endpoints/forms'
import {
  DynamicFormRenderer,
  FormLoadingState,
} from '@/features/forms'

export function DirectFormPage() {
  const { moduleKey, formKey } = useParams({ strict: false }) as {
    moduleKey: string
    formKey: string
  }

  const query = useQuery({
    queryKey: ['form-definition', moduleKey, formKey],
    queryFn: () => fetchFormDefinition(moduleKey, formKey),
    enabled: Boolean(moduleKey && formKey),
  })

  if (query.isLoading) {
    return <FormLoadingState />
  }

  if (query.isError || !query.data) {
    return (
      <div className="rounded-md border border-border bg-card p-4 text-sm text-muted-foreground">
        Unable to load form.
      </div>
    )
  }

  return (
    <div className="mx-auto w-full max-w-3xl">
      <DynamicFormRenderer
        definition={query.data}
        binding={{
          moduleKey,
          formKey,
          source: 'web',
          page: `/forms/${moduleKey}/${formKey}`,
        }}
      />
    </div>
  )
}
