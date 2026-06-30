import { describe, expect, it } from 'vitest'
import { ApiError } from '@/api/errors'
import { BootstrapError } from '@/features/auth/core/bootstrap-error'

describe('BootstrapError', () => {
  it('maps unauthorized api errors', () => {
    const error = BootstrapError.fromUnknown(
      new ApiError('Unauthorized', { kind: 'unauthorized', status: 401 }),
    )

    expect(error.kind).toBe('unauthorized')
  })

  it('maps forbidden api errors', () => {
    const error = BootstrapError.fromUnknown(
      new ApiError('Forbidden', { kind: 'forbidden', status: 403 }),
    )

    expect(error.kind).toBe('forbidden')
  })

  it('maps network and server errors to recoverable', () => {
    expect(
      BootstrapError.fromUnknown(
        new ApiError('Network error', { kind: 'network', status: null }),
      ).kind,
    ).toBe('recoverable')

    expect(
      BootstrapError.fromUnknown(
        new ApiError('Server error', { kind: 'server', status: 500 }),
      ).kind,
    ).toBe('recoverable')
  })
})
