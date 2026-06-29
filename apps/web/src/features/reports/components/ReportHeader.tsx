interface ReportHeaderProps {
  title: string
  description?: string | null
}

export function ReportHeader({ title, description }: ReportHeaderProps) {
  return (
    <header className="space-y-1" data-testid="report-header">
      <h2 className="text-base font-semibold text-foreground">{title}</h2>
      {description ? <p className="text-sm text-muted-foreground">{description}</p> : null}
    </header>
  )
}
