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
}
