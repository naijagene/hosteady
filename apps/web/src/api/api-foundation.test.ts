import { describe, expect, it } from 'vitest'
import { ApiError } from '@/api/errors'

describe('ApiError', () => {
  it('maps 401 responses to unauthorized', () => {
    const error = ApiError.fromAxios({
      message: 'Unauthorized',
      response: { status: 401, data: { message: 'Unauthenticated.' } },
      isAxiosError: true,
    } as never)

    expect(error.kind).toBe('unauthorized')
    expect(error.status).toBe(401)
  })

  it('maps 403 responses to forbidden', () => {
    const error = ApiError.fromAxios({
      message: 'Forbidden',
      response: { status: 403, data: { message: 'Forbidden.' } },
      isAxiosError: true,
    } as never)

    expect(error.kind).toBe('forbidden')
  })

  it('maps network failures', () => {
    const error = ApiError.fromAxios({
      message: 'Network Error',
      response: undefined,
      isAxiosError: true,
    } as never)

    expect(error.kind).toBe('network')
  })

  it('extracts validation field errors', () => {
    const error = ApiError.fromAxios({
      message: 'Validation failed',
      response: {
        status: 422,
        data: { message: 'Validation failed', errors: { email: ['Invalid'] } },
      },
      isAxiosError: true,
    } as never)

    expect(error.kind).toBe('validation')
    expect(error.fieldErrors.email).toEqual(['Invalid'])
  })
})

describe('unwrapData', () => {
  it('returns wrapped data payloads', async () => {
    const { unwrapData } = await import('@/api/unwrap')
    expect(unwrapData({ data: { ok: true } })).toEqual({ ok: true })
  })

  it('returns raw payloads', async () => {
    const { unwrapData } = await import('@/api/unwrap')
    expect(unwrapData({ ok: true })).toEqual({ ok: true })
  })
})

describe('tenant headers', () => {
  it('builds organization and workspace headers', async () => {
    const { buildTenantHeaders, TENANT_HEADERS } = await import('@/api/tenant-headers')
    const headers = buildTenantHeaders({
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
      applicationPublicId: 'app-1',
    })

    expect(headers[TENANT_HEADERS.organization]).toBe('org-1')
    expect(headers[TENANT_HEADERS.workspace]).toBe('ws-1')
    expect(headers[TENANT_HEADERS.application]).toBe('app-1')
  })
})
