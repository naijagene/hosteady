export type AlphaHealthStatus = 'ready' | 'warning' | 'unavailable'

export type AlphaCheckStatus = 'ready' | 'warning' | 'unavailable'

export interface AlphaRuntimeCheck {
  key: string
  label: string
  status: AlphaCheckStatus
  detail?: string | null
}

export interface AlphaFeatureCheck {
  key: string
  label: string
  available: boolean
}

export interface AlphaApiCheck {
  base_url: string
  token_present: boolean
  tenant_headers_present: boolean
  runtime_endpoint_status: string | null
  validated_at: string
}

export interface AlphaHealthSnapshot {
  status: AlphaHealthStatus
  runtime: AlphaRuntimeCheck[]
  features: AlphaFeatureCheck[]
  api: AlphaApiCheck
}
