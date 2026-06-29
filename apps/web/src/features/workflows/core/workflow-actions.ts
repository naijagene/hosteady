export function getWorkflowActionPlaceholder(action: string): string {
  switch (action.toLowerCase()) {
    case 'execute':
      return 'Workflow execute action is not available in this view.'
    default:
      return `${action} is not supported yet.`
  }
}
