import { AdminDefinitionList } from './AdminDefinitionList'
import { AdminSection } from './AdminSection'

interface AboutHeosPanelProps {
  about: ReturnType<typeof import('../core/admin-about').buildAboutHeos>
}

export function AboutHeosPanel({ about }: AboutHeosPanelProps) {
  return (
    <div className="space-y-4">
      <AdminSection title="About HEOS">
        <AdminDefinitionList
          items={[
            { label: 'Platform', value: about.platform },
            { label: 'Version', value: about.version },
            { label: 'License', value: about.license },
            { label: 'Repository', value: about.repository },
          ]}
        />
      </AdminSection>
      <AdminSection title="Architecture Summary">
        <p className="text-sm text-muted-foreground">{about.architecture}</p>
      </AdminSection>
      <AdminSection title="Technology Stack">
        <ul className="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
          {about.stack.map((item) => (
            <li key={item}>{item}</li>
          ))}
        </ul>
      </AdminSection>
    </div>
  )
}
