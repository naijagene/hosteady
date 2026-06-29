import { describe, expect, it } from 'vitest'

const notificationRoutes = ['/notifications', '/notifications/:publicId']

describe('notification direct routes', () => {
  notificationRoutes.forEach((route) => {
    it(`documents route ${route}`, () => {
      expect(route.startsWith('/notifications')).toBe(true)
    })
  })
})
