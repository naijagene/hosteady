<?php

namespace App\Services\Module\Development;

use App\Models\BusinessModule;
use App\Modules\Sdk\Development\BusinessModuleBase;
use App\Modules\Sdk\Development\Contracts\BusinessModuleProvider;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleReference;
use App\Modules\Sdk\Development\Enums\BusinessModuleStatus;
use App\Modules\Sdk\Development\Exceptions\BusinessModuleNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessModuleRegistryService implements BusinessModuleProvider
{
    public function __construct(
        private readonly BusinessModuleValidatorService $validator,
        private readonly BusinessModuleAuditRecorder $auditRecorder,
        private readonly BusinessModuleSearchIndexer $searchIndexer,
    ) {
    }

    /**
     * @return list<BusinessModuleReference>
     */
    public function all(): array
    {
        return BusinessModule::query()
            ->orderBy('name')
            ->get()
            ->map(fn (BusinessModule $module) => BusinessModuleMapper::toReference($module))
            ->all();
    }

    public function findByKey(string $key): ?\App\Modules\Sdk\Development\Contracts\BusinessModule
    {
        $module = BusinessModule::query()->where('module_key', $key)->first();

        if ($module === null) {
            return null;
        }

        return new RegisteredBusinessModule($module);
    }

    public function findByPublicId(string $publicId): ?\App\Modules\Sdk\Development\Contracts\BusinessModule
    {
        $module = BusinessModule::query()->where('public_id', $publicId)->first();

        if ($module === null) {
            return null;
        }

        return new RegisteredBusinessModule($module);
    }

    public function normalizeManifest(BusinessModuleManifest $manifest): BusinessModuleManifest
    {
        return BusinessModuleManifest::fromArray($manifest->toArray());
    }

    public function register(
        BusinessModuleManifest|array|\App\Modules\Sdk\Development\Contracts\BusinessModule|BusinessModuleBase|string $manifest,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleReference {
        $manifest = $this->validator->resolveManifest($manifest);
        $manifest = $this->normalizeManifest($manifest);
        $this->validator->assertValid($manifest);

        return DB::transaction(function () use ($manifest, $userId, $membershipId) {
            $model = BusinessModule::query()->firstOrNew(['module_key' => $manifest->moduleKey]);
            BusinessModuleMapper::applyManifest($model, $manifest);
            $model->status = BusinessModuleStatus::Registered;
            $model->created_by_user_id = $model->exists ? $model->created_by_user_id : $userId;
            $model->created_by_membership_id = $model->exists ? $model->created_by_membership_id : $membershipId;
            $model->save();

            $this->auditRecorder->recordRegistered($model);
            $this->searchIndexer->indexModuleBestEffort($model);

            return BusinessModuleMapper::toReference($model);
        });
    }

    public function show(string $publicId): BusinessModuleReference
    {
        $module = BusinessModule::query()->where('public_id', $publicId)->first();

        if ($module === null) {
            throw new BusinessModuleNotFoundException(sprintf('Business module [%s] was not found.', $publicId));
        }

        return BusinessModuleMapper::toReference($module);
    }
}
