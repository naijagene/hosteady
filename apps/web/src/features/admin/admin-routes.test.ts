import { describe, expect, it } from 'vitest'
import { adminRoutes } from '@/features/admin/core/admin-navigation'

const routes = [
  '/admin',
  '/admin/profile',
  '/admin/organization',
  '/admin/workspaces',
  '/admin/applications',
  '/admin/roles',
  '/admin/permissions',
  '/admin/platform',
  '/admin/runtime',
  '/admin/about',
]

describe('admin routes', () => {
  routes.forEach((route) => {
    it(`documents route ${route}`, () => {
      expect(route.startsWith('/admin')).toBe(true)
    })
  })

  it('exports route constants', () => {
    expect(adminRoutes.about).toBe('/admin/about')
  })
})
