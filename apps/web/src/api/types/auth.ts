export interface AuthUser {
  public_id: string
  display_name: string
  email: string
  status: string
}

export interface AuthTokenResponse {
  token: string
  token_type: 'Bearer'
  expires_at: string
  user: AuthUser
}

export interface LoginRequest {
  email: string
  password: string
  remember?: boolean
}

export interface MembershipSummary {
  public_id: string
  status: string
  join_method: string
  default_workspace_public_id: string | null
}

export interface OrganizationSummary {
  public_id: string
  name: string
  slug: string
  status: string
  organization_code: string
  membership: MembershipSummary
}

export interface WorkspaceSummary {
  public_id: string
  name: string
  slug: string
  is_default: boolean
  status: string
}

export interface ApplicationSummary {
  public_id: string
  name?: string
  key?: string
}
