import { useState } from 'react'
import { Controller } from 'react-hook-form'
import type { BaseFieldProps } from './basic-fields'
import { FieldShell } from './field-shell'
import { fieldAriaProps, inputClassName } from './field-utils'
import { DocumentPicker } from '@/features/documents/components/DocumentPicker'
import { DocumentUploadPanel } from '@/features/documents/components/DocumentUploadPanel'
import { resolveDocumentReferenceFromValue } from '@/features/documents/core/document-selection'

export function FileField({
  field,
  error,
  disabled,
  readOnly,
}: Pick<BaseFieldProps, 'field' | 'error' | 'disabled' | 'readOnly'>) {
  const [selectedFileName, setSelectedFileName] = useState<string | null>(null)
  const uploadEnabled = field.metadata?.upload_enabled !== false

  return (
    <FieldShell
      fieldKey={field.field_key}
      label={field.label}
      required={field.required}
      error={error}
    >
      <div className="space-y-2 rounded-md border border-dashed border-border p-3">
        <input
          type="file"
          {...fieldAriaProps(field.field_key, error)}
          className="block w-full text-sm text-muted-foreground"
          disabled={disabled || readOnly}
          onChange={(event) => {
            const file = event.target.files?.[0]
            setSelectedFileName(file?.name ?? null)
          }}
        />
        {selectedFileName ? (
          <p className="text-xs text-muted-foreground">Selected file metadata: {selectedFileName}</p>
        ) : null}
        {uploadEnabled ? (
          <DocumentUploadPanel enabled={!disabled && !readOnly} />
        ) : (
          <p className="text-xs text-muted-foreground">Upload endpoint not available.</p>
        )}
      </div>
    </FieldShell>
  )
}

export function DocumentField({
  field,
  control,
  rules,
  error,
  disabled,
  readOnly,
}: BaseFieldProps) {
  const [pickerOpen, setPickerOpen] = useState(false)
  const [selectedDocument, setSelectedDocument] = useState<{ public_id: string; title: string } | null>(
    null,
  )

  return (
    <FieldShell
      fieldKey={field.field_key}
      label={field.label}
      required={field.required}
      error={error}
      description="Select a document reference or enter a public ID."
    >
      <Controller
        name={field.field_key}
        control={control}
        rules={rules}
        render={({ field: controllerField }) => {
          const typedReference = resolveDocumentReferenceFromValue(controllerField.value)
          const displayReference = selectedDocument ?? typedReference

          return (
            <div className="space-y-2 rounded-md border border-dashed border-border p-3">
              <input
                type="text"
                placeholder="Document public_id"
                value={typeof controllerField.value === 'string' ? controllerField.value : displayReference?.public_id ?? ''}
                onChange={(event) => controllerField.onChange(event.target.value)}
                {...fieldAriaProps(field.field_key, error)}
                className={inputClassName}
                disabled={disabled || readOnly}
              />
              {displayReference ? (
                <p className="text-xs text-muted-foreground">Selected: {displayReference.title}</p>
              ) : null}
              <button
                type="button"
                className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted disabled:opacity-50"
                disabled={disabled || readOnly}
                aria-label="Browse documents"
                onClick={() => setPickerOpen(true)}
              >
                Browse documents
              </button>
              <DocumentPicker
                open={pickerOpen}
                multiple={field.metadata?.multiple === true}
                onClose={() => setPickerOpen(false)}
                onConfirm={(result) => {
                  const document = result.documents[0]
                  controllerField.onChange(document?.public_id ?? '')
                  setSelectedDocument(document ?? null)
                  setPickerOpen(false)
                }}
              />
            </div>
          )
        }}
      />
    </FieldShell>
  )
}
