interface NotificationSearchProps {
  value: string
  onChange: (value: string) => void
}

export function NotificationSearch({ value, onChange }: NotificationSearchProps) {
  return (
    <label className="block w-full">
      <span className="sr-only">Search notifications</span>
      <input
        type="search"
        className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
        placeholder="Search notifications"
        value={value}
        onChange={(event) => onChange(event.target.value)}
        aria-label="Search notifications"
      />
    </label>
  )
}
