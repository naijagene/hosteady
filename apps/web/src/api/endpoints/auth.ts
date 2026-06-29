import { apiClient } from '../client'
import type { AuthTokenResponse, AuthUser, LoginRequest } from '../types/auth'

export async function login(payload: LoginRequest): Promise<AuthTokenResponse> {
  const response = await apiClient.post<AuthTokenResponse>('auth/login', payload)
  return response.data
}

export async function logout(): Promise<void> {
  await apiClient.post('auth/logout')
}

export async function fetchCurrentUser(): Promise<AuthUser> {
  const response = await apiClient.get<{ data: AuthUser } | AuthUser>('auth/me')
  const payload = response.data

  if ('data' in payload && payload.data) {
    return payload.data
  }

  return payload as AuthUser
}

export async function fetchOrganizations(): Promise<unknown[]> {
  const response = await apiClient.get<{ data: unknown[] } | unknown[]>(
    'auth/organizations',
  )
  const payload = response.data

  if (Array.isArray(payload)) {
    return payload
  }

  return payload.data ?? []
}
