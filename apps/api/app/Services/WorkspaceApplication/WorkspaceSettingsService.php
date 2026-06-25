<?php

namespace App\Services\WorkspaceApplication;

use App\Enums\WorkspaceApplicationStatus;
use App\Enums\WorkspaceSettingChangeType;
use App\Enums\WorkspaceSettingType;
use App\Exceptions\WorkspaceApplication\InvalidWorkspaceSettingKeyException;
use App\Exceptions\WorkspaceApplication\InvalidWorkspaceSettingTypeException;
use App\Exceptions\WorkspaceApplication\SensitiveSettingDowngradeException;
use App\Exceptions\WorkspaceApplication\UnknownWorkspaceSettingKeysException;
use App\Exceptions\WorkspaceApplication\WorkspaceApplicationNotConfigurableException;
use App\Exceptions\WorkspaceApplication\WorkspaceApplicationNotFoundException;
use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationSetting;
use App\Models\WorkspaceApplicationSettingHistory;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceSettingsService
{
    public function __construct(
        private readonly WorkspaceSettingValueValidator $valueValidator,
        private readonly WorkspaceSettingsNormalizer $normalizer,
        private readonly WorkspaceSettingMasker $masker,
    ) {
    }

    /**
     * @return Collection<int, WorkspaceApplicationSetting>
     */
    public function listSettings(TenantContext $context, string $workspaceApplicationPublicId): Collection
    {
        $workspaceApplication = $this->resolveForRead($context, $workspaceApplicationPublicId);

        return WorkspaceApplicationSetting::query()
            ->where('workspace_application_id', $workspaceApplication->id)
            ->whereNull('deleted_at')
            ->orderBy('setting_key')
            ->get();
    }

    /**
     * @param  array<string, array{value: mixed, type: string, is_sensitive?: bool}>  $settings
     * @return Collection<int, WorkspaceApplicationSetting>
     */
    public function bulkUpdate(
        TenantContext $context,
        string $workspaceApplicationPublicId,
        array $settings,
        ?string $reason = null,
    ): Collection {
        $workspaceApplication = $this->resolveForWrite($context, $workspaceApplicationPublicId);

        if ($settings === []) {
            return $this->listSettings($context, $workspaceApplicationPublicId);
        }

        $this->assertUniqueSettingKeys(array_keys($settings));

        return DB::transaction(function () use ($context, $workspaceApplication, $settings, $reason) {
            foreach ($settings as $settingKey => $payload) {
                $this->assertValidSettingKey($settingKey);

                $type = WorkspaceSettingType::from($payload['type']);
                $normalizedValue = $this->valueValidator->assertValid($payload['value'], $type);
                $isSensitive = (bool) ($payload['is_sensitive'] ?? false);

                /** @var WorkspaceApplicationSetting|null $existing */
                $existing = WorkspaceApplicationSetting::query()
                    ->where('workspace_application_id', $workspaceApplication->id)
                    ->where('setting_key', $settingKey)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if ($existing === null) {
                    $this->createSetting(
                        $context,
                        $workspaceApplication,
                        $settingKey,
                        $type,
                        $normalizedValue,
                        $isSensitive,
                        $reason,
                    );

                    continue;
                }

                $this->updateSetting(
                    $context,
                    $workspaceApplication,
                    $existing,
                    $type,
                    $normalizedValue,
                    $isSensitive,
                    $reason,
                );
            }

            return WorkspaceApplicationSetting::query()
                ->where('workspace_application_id', $workspaceApplication->id)
                ->whereNull('deleted_at')
                ->orderBy('setting_key')
                ->get();
        });
    }

    /**
     * @param  list<string>|null  $keys
     * @return Collection<int, WorkspaceApplicationSetting>
     */
    public function reset(
        TenantContext $context,
        string $workspaceApplicationPublicId,
        ?array $keys = null,
        ?string $reason = null,
    ): Collection {
        $workspaceApplication = $this->resolveForWrite($context, $workspaceApplicationPublicId);

        return DB::transaction(function () use ($context, $workspaceApplication, $keys, $reason) {
            $query = WorkspaceApplicationSetting::query()
                ->where('workspace_application_id', $workspaceApplication->id)
                ->whereNull('deleted_at');

            if ($keys !== null && $keys !== []) {
                foreach ($keys as $key) {
                    $this->assertValidSettingKey($key);
                }

                $existingKeys = (clone $query)->pluck('setting_key')->all();
                $unknownKeys = array_values(array_diff($keys, $existingKeys));

                if ($unknownKeys !== []) {
                    throw new UnknownWorkspaceSettingKeysException($unknownKeys);
                }

                $query->whereIn('setting_key', $keys);
            }

            $settings = $query->lockForUpdate()->get();

            foreach ($settings as $setting) {
                $this->resetSetting($context, $setting, $workspaceApplication, $reason);
            }

            return WorkspaceApplicationSetting::query()
                ->where('workspace_application_id', $workspaceApplication->id)
                ->whereNull('deleted_at')
                ->orderBy('setting_key')
                ->get();
        });
    }

    /**
     * @return LengthAwarePaginator<int, WorkspaceApplicationSettingHistory>
     */
    public function history(
        TenantContext $context,
        string $workspaceApplicationPublicId,
        ?string $settingKey = null,
        int $page = 1,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $workspaceApplication = $this->resolveForRead($context, $workspaceApplicationPublicId);

        $query = WorkspaceApplicationSettingHistory::query()
            ->with('changedByMembership')
            ->where('workspace_application_id', $workspaceApplication->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($settingKey !== null) {
            $this->assertValidSettingKey($settingKey);
            $query->where('setting_key', $settingKey);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function resolveSettingsVersion(TenantContext $context): int
    {
        $maxVersion = WorkspaceApplicationSetting::query()
            ->whereNull('deleted_at')
            ->whereHas('workspaceApplication', function ($query) use ($context) {
                $query->where('workspace_id', $context->workspace->id)
                    ->where('organization_id', $context->organization->id)
                    ->where('status', WorkspaceApplicationStatus::Active)
                    ->whereNull('deleted_at');
            })
            ->max('version');

        return (int) ($maxVersion ?? 0);
    }

    private function createSetting(
        TenantContext $context,
        WorkspaceApplication $workspaceApplication,
        string $settingKey,
        WorkspaceSettingType $type,
        mixed $normalizedValue,
        bool $isSensitive,
        ?string $reason,
    ): WorkspaceApplicationSetting {
        $setting = new WorkspaceApplicationSetting([
            'workspace_application_id' => $workspaceApplication->id,
            'setting_key' => $settingKey,
            'setting_value' => $normalizedValue,
            'setting_type' => $type,
            'version' => 1,
            'is_sensitive' => $isSensitive,
            'is_encrypted' => false,
        ]);
        $setting->applyAuditActor($context->user->id)->save();

        $this->appendHistory(
            $context,
            $setting,
            $workspaceApplication,
            WorkspaceSettingChangeType::Created,
            null,
            $normalizedValue,
            1,
            $reason,
            $isSensitive,
            $isSensitive,
        );

        return $setting;
    }

    private function updateSetting(
        TenantContext $context,
        WorkspaceApplication $workspaceApplication,
        WorkspaceApplicationSetting $setting,
        WorkspaceSettingType $type,
        mixed $normalizedValue,
        bool $isSensitive,
        ?string $reason,
    ): void {
        if ($setting->is_sensitive && ! $isSensitive) {
            throw new SensitiveSettingDowngradeException($setting->setting_key);
        }

        $typeChanged = $setting->setting_type !== $type;
        $valueChanged = ! $this->normalizer->equals($setting->setting_value, $normalizedValue, $setting->setting_type)
            || ($typeChanged && ! $this->normalizer->equals($setting->setting_value, $normalizedValue, $type));
        $sensitiveChanged = $setting->is_sensitive !== $isSensitive;

        if (! $valueChanged && ! $typeChanged && ! $sensitiveChanged) {
            return;
        }

        $beforeValue = $setting->setting_value;
        $wasSensitive = $setting->is_sensitive;
        $nextVersion = $setting->version + 1;

        $setting->fill([
            'setting_value' => $normalizedValue,
            'setting_type' => $type,
            'version' => $nextVersion,
            'is_sensitive' => $isSensitive,
        ]);
        $setting->applyAuditActor($context->user->id)->save();

        $this->appendHistory(
            $context,
            $setting,
            $workspaceApplication,
            WorkspaceSettingChangeType::Updated,
            $beforeValue,
            $normalizedValue,
            $nextVersion,
            $reason,
            $wasSensitive,
            $isSensitive,
        );
    }

    private function resetSetting(
        TenantContext $context,
        WorkspaceApplicationSetting $setting,
        WorkspaceApplication $workspaceApplication,
        ?string $reason,
    ): void {
        $beforeValue = $setting->setting_value;
        $version = $setting->version;

        $this->appendHistory(
            $context,
            $setting,
            $workspaceApplication,
            WorkspaceSettingChangeType::Reset,
            $beforeValue,
            null,
            $version,
            $reason,
            $setting->is_sensitive,
            false,
        );

        $setting->applyDeleteActor($context->user->id);
        $setting->delete();
    }

    private function appendHistory(
        TenantContext $context,
        WorkspaceApplicationSetting $setting,
        WorkspaceApplication $workspaceApplication,
        WorkspaceSettingChangeType $changeType,
        mixed $beforeValue,
        mixed $afterValue,
        int $version,
        ?string $reason,
        bool $maskBefore,
        bool $maskAfter,
    ): void {
        WorkspaceApplicationSettingHistory::query()->create([
            'id' => (string) Str::uuid7(),
            'public_id' => (string) Str::uuid7(),
            'workspace_application_setting_id' => $setting->id,
            'workspace_application_id' => $workspaceApplication->id,
            'setting_key' => $setting->setting_key,
            'version' => $version,
            'change_type' => $changeType,
            'before_value' => $this->encodeHistoryValue($beforeValue, $maskBefore),
            'after_value' => $this->encodeHistoryValue($afterValue, $maskAfter),
            'changed_by_user_id' => $context->user->id,
            'changed_by_membership_id' => $context->membership->id,
            'reason' => $reason !== null ? Str::limit($reason, 255, '') : null,
            'created_at' => now(),
        ]);
    }

    private function encodeHistoryValue(mixed $value, bool $mask): mixed
    {
        if ($value === null) {
            return null;
        }

        return $mask ? WorkspaceSettingMasker::MASK : $value;
    }

    private function resolveForRead(TenantContext $context, string $workspaceApplicationPublicId): WorkspaceApplication
    {
        $workspaceApplication = WorkspaceApplication::query()
            ->where('public_id', $workspaceApplicationPublicId)
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_id', $context->organization->id)
            ->whereNull('deleted_at')
            ->whereIn('status', [
                WorkspaceApplicationStatus::Active,
                WorkspaceApplicationStatus::Disabled,
                WorkspaceApplicationStatus::Archived,
            ])
            ->first();

        if ($workspaceApplication === null) {
            throw new WorkspaceApplicationNotFoundException;
        }

        return $workspaceApplication;
    }

    private function resolveForWrite(TenantContext $context, string $workspaceApplicationPublicId): WorkspaceApplication
    {
        $workspaceApplication = $this->resolveForRead($context, $workspaceApplicationPublicId);

        if ($workspaceApplication->status !== WorkspaceApplicationStatus::Active) {
            throw new WorkspaceApplicationNotConfigurableException;
        }

        return $workspaceApplication;
    }

    private function assertValidSettingKey(string $settingKey): void
    {
        if (! preg_match('/^[a-z0-9_.-]{1,128}$/', $settingKey)) {
            throw new InvalidWorkspaceSettingKeyException($settingKey);
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function assertUniqueSettingKeys(array $keys): void
    {
        if (count($keys) !== count(array_unique($keys))) {
            throw new InvalidWorkspaceSettingTypeException('Duplicate setting keys are not allowed in a single request.');
        }
    }
}
