interface ActivitySearchBoxProps {
  value: string
  onChange: (value: string) => void
}

export function ActivitySearchBox({ value, onChange }: ActivitySearchBoxProps) {
  return (
    <input
      type="search"
      aria-label="Search activity"
      value={value}
      onChange={(event) => onChange(event.target.value)}
      placeholder="Search activity…"
      className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
      data-testid="activity-search-box"
    />
  )
}
