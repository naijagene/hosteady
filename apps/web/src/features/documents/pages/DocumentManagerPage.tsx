import { DocumentManager } from '../components/DocumentManager'

export function DocumentManagerPage() {
  return (
    <div className="mx-auto w-full max-w-7xl">
      <DocumentManager
        binding={{
          mode: 'list',
          query_enabled: true,
          search_enabled: true,
          upload_enabled: true,
          selection_enabled: false,
          detail_enabled: true,
          per_page: 25,
        }}
      />
    </div>
  )
}
