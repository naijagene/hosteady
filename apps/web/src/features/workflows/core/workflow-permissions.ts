export function canReadWorkflows(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('workflow.read') || permissions.includes('workflow.runtime.read')
}

export function canExecuteWorkflows(permissions: string[]): boolean {
  return permissions.includes('workflow.execute')
}

export function canReadTasks(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('task.read')
}

export function canManageTasks(permissions: string[]): boolean {
  return permissions.includes('task.manage')
}

export function canReadApprovals(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('approval.read')
}

export function canDecideApprovals(permissions: string[]): boolean {
  return permissions.includes('approval.decide')
}
