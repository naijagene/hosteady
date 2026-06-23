import {
  HSidebar,
  HStatusBar,
  HTopbar,
  HWorkspace,
} from '../components/hds'

export function WorkspaceShell() {
  return (
    <div className="flex h-full flex-col">
      <HTopbar />
      <div className="flex min-h-0 flex-1">
        <HSidebar />
        <HWorkspace />
      </div>
      <HStatusBar />
    </div>
  )
}
