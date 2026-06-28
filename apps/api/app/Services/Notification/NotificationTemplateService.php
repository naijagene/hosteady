<?php

namespace App\Services\Notification;

use App\Models\NotificationTemplate as NotificationTemplateModel;
use App\Modules\Sdk\Notification\Contracts\NotificationTemplateProvider;
use App\Modules\Sdk\Notification\Data\NotificationTemplate;
use App\Modules\Sdk\Notification\Exceptions\NotificationTemplateException;
use Illuminate\Support\Str;

class NotificationTemplateService implements NotificationTemplateProvider
{
    public function find(string $organizationId, ?string $workspaceId, string $templatePublicId): ?NotificationTemplate
    {
        $model = NotificationTemplateModel::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $templatePublicId)
            ->first();

        return $model !== null ? NotificationMapper::toTemplate($model) : null;
    }

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $templateKey): ?NotificationTemplate
    {
        $model = NotificationTemplateModel::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('type', $templateKey)
            ->first();

        return $model !== null ? NotificationMapper::toTemplate($model) : null;
    }

    /**
     * @return list<NotificationTemplate>
     */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        return NotificationTemplateModel::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (NotificationTemplateModel $model) => NotificationMapper::toTemplate($model))
            ->all();
    }

    public function create(string $organizationId, ?string $workspaceId, NotificationTemplate $template): NotificationTemplate
    {
        $model = NotificationTemplateModel::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'module_key' => $template->moduleKey,
            'type' => $template->type,
            'template_type' => $template->templateType,
            'subject' => $template->subject,
            'body' => $template->body,
            'channels' => $template->channels,
            'variables' => $template->variables,
            'scope' => $template->scope,
        ]);

        return NotificationMapper::toTemplate($model);
    }

    public function update(string $organizationId, ?string $workspaceId, NotificationTemplate $template): NotificationTemplate
    {
        $model = NotificationTemplateModel::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $template->publicId)
            ->first();

        if ($model === null) {
            throw new NotificationTemplateException(sprintf('Template [%s] was not found.', $template->publicId));
        }

        $model->fill([
            'module_key' => $template->moduleKey,
            'type' => $template->type,
            'template_type' => $template->templateType,
            'subject' => $template->subject,
            'body' => $template->body,
            'channels' => $template->channels,
            'variables' => $template->variables,
            'scope' => $template->scope,
        ]);
        $model->save();

        return NotificationMapper::toTemplate($model->fresh());
    }
}
