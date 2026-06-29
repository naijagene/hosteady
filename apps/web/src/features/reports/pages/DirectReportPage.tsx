import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'
import { fetchReportRender } from '@/api/endpoints/reports'
import { normalizeReportBindingContext } from '@/api/types/reports'
import { DynamicReportViewer } from '../components/DynamicReportViewer'
import { ReportErrorState } from '../components/ReportErrorState'
import { ReportLoadingState } from '../components/ReportLoadingState'
import { toReportQueryError } from '../core/report-errors'

export function DirectReportPage() {
  const { moduleKey, reportKey } = useParams({ strict: false }) as {
    moduleKey: string
    reportKey: string
  }

  const query = useQuery({
    queryKey: ['report-render', moduleKey, reportKey],
    queryFn: () => fetchReportRender(moduleKey, reportKey),
    enabled: Boolean(moduleKey && reportKey),
  })

  if (query.isLoading) {
    return <ReportLoadingState />
  }

  if (query.isError || !query.data) {
    return (
      <ReportErrorState
        message={
          query.error ? toReportQueryError(query.error).message : 'Unable to load report.'
        }
      />
    )
  }

  const binding = normalizeReportBindingContext(
    {
      auto_render: true,
      export_enabled: true,
      run_enabled: true,
      page: `/reports/${moduleKey}/${reportKey}`,
    },
    moduleKey,
    reportKey,
  )

  return (
    <div className="mx-auto w-full max-w-7xl">
      <DynamicReportViewer
        payload={query.data}
        binding={binding}
        onRefresh={() => query.refetch()}
      />
    </div>
  )
}
