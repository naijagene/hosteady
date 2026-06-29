import { asArray, asRecord, asString, type MetadataRecord } from './metadata-common'

export interface ReportDefinition {
  public_id?: string
  module_key: string
  report_key: string
  name: string
  description?: string | null
  sections?: MetadataRecord[]
  metadata?: MetadataRecord
}

export interface ReportRenderPayload {
  report: ReportDefinition
  sections: MetadataRecord[]
  metadata?: MetadataRecord
}

export function normalizeReportDefinition(raw: unknown): ReportDefinition {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    module_key: asString(data.module_key ?? data.moduleKey),
    report_key: asString(data.report_key ?? data.reportKey),
    name: asString(data.name, 'Report'),
    description:
      typeof data.description === 'string' ? data.description : null,
    sections: asArray<MetadataRecord>(data.sections),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportRenderPayload(raw: unknown): ReportRenderPayload {
  const data = asRecord(raw)
  const report = normalizeReportDefinition(data.report ?? data.definition ?? data)

  return {
    report,
    sections:
      asArray<MetadataRecord>(data.sections).length > 0
        ? asArray<MetadataRecord>(data.sections)
        : report.sections ?? [],
    metadata: asRecord(data.metadata),
  }
}
