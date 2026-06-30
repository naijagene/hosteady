import type { AdminPlatformInfo } from '@/api/types/admin'
import { formatPlatformInfoLabel } from '../core/admin-platform'
import { AdminDefinitionList } from './AdminDefinitionList'
import { AdminSection } from './AdminSection'

interface PlatformOverviewPanelProps {
  info: AdminPlatformInfo
}

export function PlatformOverviewPanel({ info }: PlatformOverviewPanelProps) {
  const items = [
    { label: 'HEOS Version', value: info.heos_version ?? '—' },
    { label: 'Backend Version', value: info.backend_version ?? '—' },
    { label: 'Frontend Version', value: info.frontend_version ?? '—' },
    { label: 'Environment', value: info.environment ?? '—' },
    { label: 'Build Number', value: info.build_number ?? '—' },
    { label: 'Runtime Status', value: info.runtime_status ?? '—' },
    ...Object.entries(info.feature_counts ?? {}).map(([key, value]) => ({
      label: formatPlatformInfoLabel(key),
      value: String(value),
    })),
  ]

  return (
    <AdminSection title="Platform Overview" description="Current HEOS platform information.">
      <AdminDefinitionList items={items} />
    </AdminSection>
  )
}
