<?php

namespace App\Services\Notification;

use App\Modules\Sdk\Notification\Contracts\NotificationRenderer;
use App\Modules\Sdk\Notification\Data\NotificationTemplate;

class NotificationRendererService implements NotificationRenderer
{
    /**
     * @var list<string>
     */
    private const KNOWN_VARIABLES = [
        'user_name',
        'workspace',
        'organization',
        'task_name',
        'approval_name',
        'document_name',
        'entity_name',
        'record_name',
        'report_name',
        'dashboard_name',
        'date',
        'time',
    ];

    /**
     * @param  array<string, mixed>  $mergeData
     *
     * @return array{title: string, body: string}
     */
    public function render(NotificationTemplate $template, array $mergeData): array
    {
        $title = $template->subject ?? $template->type;
        $body = $template->body;

        return [
            'title' => $this->replaceVariables($title, $mergeData),
            'body' => $this->replaceVariables($body, $mergeData),
        ];
    }

    /**
     * @param  array<string, mixed>  $mergeData
     */
    public function replaceVariables(string $content, array $mergeData): string
    {
        $replacements = [];

        foreach (self::KNOWN_VARIABLES as $variable) {
            $value = $mergeData[$variable] ?? $mergeData[$this->camelCase($variable)] ?? '';
            $replacements['{{'.$variable.'}}'] = $this->stringify($value);
        }

        foreach ($mergeData as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $replacements['{{'.$key.'}}'] = $this->stringify($value);
        }

        return strtr($content, $replacements);
    }

    private function camelCase(string $snake): string
    {
        return lcfirst(str_replace('_', '', ucwords($snake, '_')));
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
