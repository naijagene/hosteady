import { useParams } from '@tanstack/react-router'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { PageRenderer } from '@/features/renderer/layouts/PageRenderer'
import { useUiPageRender } from '@/features/renderer/hooks/useUiPageRender'
import '@/features/renderer/register-default-components'

export function MetadataPage() {
  const { moduleKey, pageKey } = useParams({ strict: false }) as {
    moduleKey: string
    pageKey: string
  }
  const runtime = useHydratedRuntime()
  const query = useUiPageRender(moduleKey, pageKey)

  return (
    <PageRenderer
      payload={query.data ?? null}
      loading={query.isLoading}
      error={
        query.isError
          ? query.error instanceof Error
            ? query.error
            : new Error('Page not found')
          : null
      }
      runtimePermissions={runtime?.permissions ?? []}
      moduleKey={moduleKey}
      pageKey={pageKey}
    />
  )
}
