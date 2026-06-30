import { describe, expect, it } from 'vitest'
import { sanitizeRedirectTarget } from '@/features/auth/core/redirect-sanitize'

describe('sanitizeRedirectTarget', () => {
  it('returns undefined for blocked auth routes', () => {
    expect(sanitizeRedirectTarget('/login')).toBeUndefined()
    expect(sanitizeRedirectTarget('/logout')).toBeUndefined()
    expect(sanitizeRedirectTarget('/unauthorized')).toBeUndefined()
    expect(sanitizeRedirectTarget('/forbidden')).toBeUndefined()
    expect(sanitizeRedirectTarget('/loading')).toBeUndefined()
  })

  it('allows internal application paths', () => {
    expect(sanitizeRedirectTarget('/')).toBe('/')
    expect(sanitizeRedirectTarget('/admin')).toBe('/admin')
    expect(sanitizeRedirectTarget('/alpha/health')).toBe('/alpha/health')
  })

  it('rejects external and protocol-relative targets', () => {
    expect(sanitizeRedirectTarget('https://evil.example')).toBeUndefined()
    expect(sanitizeRedirectTarget('//evil.example')).toBeUndefined()
  })
})
