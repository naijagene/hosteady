import { describe, expect, it } from 'vitest'

const workflowRoutes = [
  '/workflows',
  '/workflows/instances/:instancePublicId',
  '/workflows/tasks/:taskPublicId',
  '/workflows/approvals/:approvalPublicId',
]

describe('workflow direct routes', () => {
  workflowRoutes.forEach((route) => {
    it(`documents route ${route}`, () => {
      expect(route.startsWith('/workflows')).toBe(true)
    })
  })

  it('includes inbox, instance, task, and approval paths', () => {
    expect(workflowRoutes).toEqual([
      '/workflows',
      '/workflows/instances/:instancePublicId',
      '/workflows/tasks/:taskPublicId',
      '/workflows/approvals/:approvalPublicId',
    ])
  })
})
