import { useParams } from '@tanstack/react-router'
import { DocumentManager } from '../components/DocumentManager'

export function DirectDocumentPage() {
  const { documentPublicId } = useParams({ strict: false }) as { documentPublicId: string }

  return (
    <div className="mx-auto w-full max-w-7xl">
      <DocumentManager
        initialDocumentId={documentPublicId}
        binding={{
          mode: 'list',
          query_enabled: true,
          search_enabled: true,
          upload_enabled: true,
          detail_enabled: true,
          per_page: 25,
        }}
      />
    </div>
  )
}
