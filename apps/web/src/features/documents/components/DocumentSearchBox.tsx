interface DocumentSearchBoxProps {
  value: string
  onChange: (value: string) => void
  disabled?: boolean
}

export function DocumentSearchBox({ value, onChange, disabled = false }: DocumentSearchBoxProps) {
  return (
    <input
      type="search"
      value={value}
      onChange={(event) => onChange(event.target.value)}
      placeholder="Search documents"
      aria-label="Search documents"
      disabled={disabled}
      className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm md:max-w-sm"
      data-testid="document-search-box"
    />
  )
}
