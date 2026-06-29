export type ApiRecord = Record<string, unknown>

export interface ApiErrorBody {
  message?: string
  errors?: Record<string, string[]>
}

export interface ApiEnvelope<TData> {
  data: TData
}
