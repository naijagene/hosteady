import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import type {
  AuthTokenResponse,
  AuthUser,
  LoginRequest,
  OrganizationSummary,
} from '../types/auth'

export async function login(payload: LoginRequest): Promise<AuthTokenResponse> {
  const response = await apiClient.post<AuthTokenResponse>('auth/login', {
    email: payload.email,
    password: payload.password,
  })

  return unwrapData(response.data)
}

export async function logout(): Promise<void> {
  await apiClient.post('auth/logout')
}

export async function fetchCurrentUser(): Promise<AuthUser> {
  const response = await apiClient.get<AuthUser | { data: AuthUser }>('auth/me')
  return unwrapData(response.data)
}

export async function fetchOrganizations(): Promise<OrganizationSummary[]> {
  const response = await apiClient.get<
    OrganizationSummary[] | { data: OrganizationSummary[] }
  >('auth/organizations')

  return unwrapData(response.data)
}
