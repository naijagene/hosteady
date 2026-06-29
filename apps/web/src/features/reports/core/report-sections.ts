import type { ReportRenderPayload, ReportSection } from '@/api/types/reports'
import { normalizeReportSection } from '@/api/types/reports'

const supportedSectionTypes = new Set([
  'summary',
  'table',
  'chart',
  'metric',
  'text',
  'group',
  'custom',
])

export function normalizeSectionType(sectionType: string | undefined | null): string {
  const normalized = (sectionType ?? 'custom').toLowerCase()
  return supportedSectionTypes.has(normalized) ? normalized : 'custom'
}

export function resolveReportSections(payload: ReportRenderPayload): ReportSection[] {
  if (payload.sections.length > 0) {
    return payload.sections
  }

  const synthesized: ReportSection[] = []

  if ((payload.metrics?.length ?? 0) > 0) {
    synthesized.push(
      normalizeReportSection({
        section_key: 'summary',
        label: 'Summary',
        section_type: 'summary',
        metrics: payload.metrics,
      }),
    )
  }

  const dataset = payload.datasets?.[0]
  if (dataset?.rows && dataset.rows.length > 0) {
    synthesized.push(
      normalizeReportSection({
        section_key: 'table',
        label: 'Results',
        section_type: 'table',
        columns: payload.columns,
        rows: dataset.rows,
        metadata: { total: dataset.total },
      }),
    )
  }

  if ((payload.charts?.length ?? 0) > 0) {
    synthesized.push(
      normalizeReportSection({
        section_key: 'charts',
        label: 'Charts',
        section_type: 'chart',
        charts: payload.charts,
      }),
    )
  }

  return synthesized
}

export function flattenSections(sections: ReportSection[]): ReportSection[] {
  return sections.flatMap((section) =>
    section.section_type === 'group' && section.sections?.length
      ? [section, ...flattenSections(section.sections)]
      : [section],
  )
}
