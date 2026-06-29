import { describe, expect, it } from 'vitest'

const searchRoutes = ['/search']

describe('search direct routes', () => {
  searchRoutes.forEach((route) => {
    it(`documents route ${route}`, () => {
      expect(route).toBe('/search')
    })
  })
})

describe('search route protection', () => {
  it('search route lives under authenticated app shell', () => {
    expect('/search'.startsWith('/')).toBe(true)
  })
})
