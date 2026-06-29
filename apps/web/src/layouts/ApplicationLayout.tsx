import { Outlet } from '@tanstack/react-router'
import { HStatusBar } from '@/components/hds'
import { AppSidebar } from '@/components/navigation/AppSidebar'
import { NotificationBell } from '@/components/shell/NotificationBell'
import { SearchDialog } from '@/components/shell/SearchDialog'
import { UserMenu } from '@/components/shell/UserMenu'
import { WorkspaceSwitcher } from '@/components/shell/WorkspaceSwitcher'
import { RuntimeLoader } from '@/features/runtime'
import { NavigationProvider } from '@/app/providers/NavigationProvider'
import { ThemeProvider } from '@/app/providers/ThemeProvider'
import { HLogo } from '@/components/hds/HLogo'

export function ApplicationLayout() {
  return (
    <RuntimeLoader>
      <ThemeProvider>
        <NavigationProvider>
          <div className="flex h-screen flex-col bg-background text-foreground">
            <header className="flex h-14 shrink-0 items-center justify-between border-b border-border bg-primary px-4 text-primary-foreground">
              <div className="flex items-center gap-4">
                <HLogo size="sm" />
                <WorkspaceSwitcher />
              </div>
              <div className="flex items-center gap-3">
                <SearchDialog />
                <NotificationBell />
                <UserMenu />
              </div>
            </header>
            <div className="flex min-h-0 flex-1">
              <AppSidebar />
              <main className="flex min-h-0 flex-1 flex-col overflow-auto p-6">
                <Outlet />
              </main>
            </div>
            <HStatusBar />
          </div>
        </NavigationProvider>
      </ThemeProvider>
    </RuntimeLoader>
  )
}
