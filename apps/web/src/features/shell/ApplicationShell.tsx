import { Outlet } from '@tanstack/react-router'
import { NavigationProvider } from '@/app/providers/NavigationProvider'
import { ThemeProvider } from '@/app/providers/ThemeProvider'
import {
  HSidebar,
  HStatusBar,
  HTopbar,
  HWorkspace,
} from '@/components/hds'
import { RuntimeLoader } from '@/features/runtime'

export function ApplicationShell() {
  return (
    <RuntimeLoader>
      <ThemeProvider>
        <NavigationProvider>
          <div className="flex h-screen flex-col bg-background text-foreground">
            <HTopbar />
            <div className="flex min-h-0 flex-1">
              <HSidebar />
              <HWorkspace>
                <Outlet />
              </HWorkspace>
            </div>
            <HStatusBar />
          </div>
        </NavigationProvider>
      </ThemeProvider>
    </RuntimeLoader>
  )
}
