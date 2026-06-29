import { useQuery } from '@tanstack/react-query'
import { fetchFormDefinition } from '@/api/endpoints/forms'
import { DynamicFormRenderer, FormLoadingState } from '@/features/forms'

interface RecordDrawerProps {
  open: boolean
  title: string
  moduleKey: string
  formKey: string
  mode?: 'create' | 'edit' | 'readonly'
  onClose: () => void
  onSuccess?: () => void
}

export function RecordDrawer({
  open,
  title,
  moduleKey,
  formKey,
  mode = 'create',
  onClose,
  onSuccess,
}: RecordDrawerProps) {
  const formQuery = useQuery({
    queryKey: ['form-definition', moduleKey, formKey],
    queryFn: () => fetchFormDefinition(moduleKey, formKey),
    enabled: open && Boolean(moduleKey && formKey),
  })

  if (!open) {
    return null
  }

  return (
    <div
      className="fixed inset-0 z-40 flex justify-end bg-background/60"
      data-testid="record-drawer"
    >
      <div className="flex h-full w-full max-w-xl flex-col border-l border-border bg-card shadow-xl">
        <header className="flex items-center justify-between border-b border-border px-4 py-3">
          <h3 className="text-sm font-semibold text-foreground">{title}</h3>
          <button
            type="button"
            className="rounded-md border border-border px-2 py-1 text-xs"
            onClick={onClose}
          >
            Close
          </button>
        </header>
        <div className="flex-1 overflow-auto p-4">
          {formQuery.isLoading ? (
            <FormLoadingState />
          ) : formQuery.isError || !formQuery.data ? (
            <p className="text-sm text-muted-foreground">Unable to load form.</p>
          ) : (
            <DynamicFormRenderer
              definition={formQuery.data}
              mode={mode}
              binding={{
                moduleKey,
                formKey,
                mode,
                source: 'web',
                refresh_bindings_on_success: true,
              }}
              onSubmitSuccess={() => {
                onSuccess?.()
                onClose()
              }}
            />
          )}
        </div>
      </div>
    </div>
  )
}
