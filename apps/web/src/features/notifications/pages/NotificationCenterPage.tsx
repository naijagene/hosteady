import { NotificationCenter } from '../components/NotificationCenter'

export function NotificationCenterPage() {
  return (
    <div className="mx-auto w-full max-w-7xl">
      <NotificationCenter
        binding={{
          mode: 'full',
          show_counts: true,
          actions_enabled: true,
          delete_enabled: true,
          per_page: 25,
        }}
      />
    </div>
  )
}
