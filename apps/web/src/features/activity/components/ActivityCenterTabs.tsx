interface ActivityCenterTabsProps {
  tabs: Array<{ key: string; label: string }>
  activeTab: string
  onChange: (tab: string) => void
}

export function ActivityCenterTabs({ tabs, activeTab, onChange }: ActivityCenterTabsProps) {
  return (
    <div role="tablist" aria-label="Activity sections" className="flex flex-wrap gap-2" data-testid="activity-center-tabs">
      {tabs.map((tab) => (
        <button
          key={tab.key}
          type="button"
          role="tab"
          aria-selected={activeTab === tab.key}
          className={`rounded-full border px-3 py-1.5 text-xs ${
            activeTab === tab.key
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-border text-muted-foreground hover:bg-muted'
          }`}
          onClick={() => onChange(tab.key)}
        >
          {tab.label}
        </button>
      ))}
    </div>
  )
}
