import type { ReportSection } from '@/api/types/reports'
import { asArray } from '@/api/types/metadata-common'
import { ErrorBoundary } from '@/components/errors/ErrorBoundary'
import { normalizeSectionType } from '../core/report-sections'
import { ReportChartPlaceholder } from './ReportChartPlaceholder'
import { ReportDocumentsSection } from './ReportDocumentsSection'
import { ReportSummaryCards } from './ReportSummaryCards'
import { ReportTableSection } from './ReportTableSection'

interface ReportSectionRendererProps {
  section: ReportSection
}

function SectionFallback({ label }: { label: string }) {
  return (
    <div className="rounded-md border border-dashed border-border p-3 text-xs text-muted-foreground">
      Unable to render section: {label}
    </div>
  )
}

function ReportSectionContent({ section }: ReportSectionRendererProps) {
  const sectionType = normalizeSectionType(section.section_type)

  switch (sectionType) {
    case 'summary':
    case 'metric':
      return <ReportSummaryCards metrics={section.metrics ?? []} title={section.label} />
    case 'table':
      return (
        <ReportTableSection
          title={section.label}
          columns={section.columns ?? []}
          rows={section.rows ?? []}
          maxVisibleRows={
            typeof section.metadata?.max_visible_rows === 'number'
              ? section.metadata.max_visible_rows
              : undefined
          }
        />
      )
    case 'chart':
      return (
        <section className="space-y-3" data-testid="report-chart-section">
          <h3 className="text-sm font-medium text-foreground">{section.label}</h3>
          {(section.charts ?? []).length === 0 ? (
            <p className="text-sm text-muted-foreground">No charts configured.</p>
          ) : (
            <div className="grid gap-3 lg:grid-cols-2">
              {(section.charts ?? []).map((chart) => (
                <ReportChartPlaceholder key={chart.chart_key} chart={chart} />
              ))}
            </div>
          )}
        </section>
      )
    case 'text':
      return (
        <section className="space-y-2" data-testid="report-text-section">
          <h3 className="text-sm font-medium text-foreground">{section.label}</h3>
          <p className="text-sm text-muted-foreground whitespace-pre-wrap">
            {section.content ?? 'No text content provided.'}
          </p>
        </section>
      )
    case 'documents':
      return (
        <ReportDocumentsSection
          title={section.label}
          documents={asArray(section.metadata?.documents ?? section.rows)}
        />
      )
    case 'group':
      return (
        <section className="space-y-4" data-testid="report-group-section">
          <h3 className="text-sm font-medium text-foreground">{section.label}</h3>
          {(section.sections ?? []).map((child) => (
            <ReportSectionRenderer key={child.section_key} section={child} />
          ))}
        </section>
      )
    case 'custom':
    default:
      return (
        <div
          className="rounded-md border border-dashed border-border p-3 text-xs text-muted-foreground"
          data-testid="report-custom-section"
        >
          {section.label} ({section.section_type}) placeholder
        </div>
      )
  }
}

export function ReportSectionRenderer({ section }: ReportSectionRendererProps) {
  return (
    <ErrorBoundary fallback={<SectionFallback label={section.label} />}>
      <div className="space-y-3" data-testid={`report-section-${section.section_key}`}>
        <ReportSectionContent section={section} />
      </div>
    </ErrorBoundary>
  )
}
