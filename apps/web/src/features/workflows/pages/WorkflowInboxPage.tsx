import { WorkflowInbox } from '../components/WorkflowInbox'

export function WorkflowInboxPage() {
  return (
    <div className="mx-auto w-full max-w-7xl">
      <WorkflowInbox
        binding={{
          mode: 'inbox',
          show_counts: true,
          actions_enabled: true,
          comments_enabled: true,
          per_page: 25,
        }}
      />
    </div>
  )
}
