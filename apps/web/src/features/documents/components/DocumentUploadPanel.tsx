import { useState } from 'react'
import { useDocumentUpload } from '../hooks/useDocumentUpload'

interface DocumentUploadPanelProps {
  enabled?: boolean
  onSuccess?: () => void
}

export function DocumentUploadPanel({ enabled = true, onSuccess }: DocumentUploadPanelProps) {
  const uploadState = useDocumentUpload({ enabled })
  const [file, setFile] = useState<File | null>(null)
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [tags, setTags] = useState('')

  if (!enabled) {
    return (
      <div className="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground" role="status">
        Upload endpoint not available.
      </div>
    )
  }

  return (
    <section className="space-y-3 rounded-md border border-border p-4" data-testid="document-upload-panel" aria-label="Upload document">
      <h4 className="text-sm font-medium text-foreground">Upload document</h4>
      <input
        type="file"
        aria-label="Choose file"
        onChange={(event) => setFile(event.target.files?.[0] ?? null)}
        className="block w-full text-sm text-muted-foreground"
      />
      {file ? (
        <p className="text-xs text-muted-foreground">
          Selected: {file.name} · {file.type || 'unknown type'} · {file.size} bytes
        </p>
      ) : null}
      <input
        type="text"
        aria-label="Document title"
        placeholder="Title"
        value={title}
        onChange={(event) => setTitle(event.target.value)}
        className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
      />
      <textarea
        aria-label="Document description"
        placeholder="Description"
        value={description}
        onChange={(event) => setDescription(event.target.value)}
        className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
      />
      <input
        type="text"
        aria-label="Document tags"
        placeholder="Tags (comma separated)"
        value={tags}
        onChange={(event) => setTags(event.target.value)}
        className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
      />
      <button
        type="button"
        className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted disabled:opacity-50"
        aria-label="Submit upload"
        disabled={!file || uploadState.isUploading}
        onClick={async () => {
          if (!file) {
            return
          }

          const result = await uploadState.upload({
            file,
            title: title || file.name,
            description,
            tags: tags
              .split(',')
              .map((tag) => tag.trim())
              .filter(Boolean),
          })

          if (result) {
            onSuccess?.()
          }
        }}
      >
        {uploadState.isUploading ? 'Uploading…' : 'Upload'}
      </button>
      {uploadState.error ? (
        <p className="text-xs text-destructive" role="alert">
          {uploadState.error}
        </p>
      ) : null}
      {uploadState.result ? (
        <p className="text-xs text-muted-foreground" role="status">
          Uploaded {uploadState.result.document.title}
        </p>
      ) : null}
    </section>
  )
}
