<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportDefinitionResource;
use App\Http\Resources\ReportExportResource;
use App\Http\Resources\ReportRenderResource;
use App\Http\Resources\ReportRunResource;
use App\Http\Resources\ReportScheduleResource;
use App\Models\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportScheduleDefinition;
use App\Services\Report\DynamicReportDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DynamicReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DynamicReportDevelopmentService $developmentService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', ReportDefinition::class);
        $context = app(TenantContext::class);

        return ReportDefinitionResource::collection($this->developmentService->listDefinitions($context));
    }

    public function show(string $moduleKey, string $reportKey): ReportDefinitionResource
    {
        $this->authorize('view', ReportDefinition::class);
        $context = app(TenantContext::class);

        return new ReportDefinitionResource(
            $this->developmentService->showDefinition($context, $moduleKey, $reportKey),
        );
    }

    public function render(Request $request, string $moduleKey, string $reportKey): ReportRenderResource
    {
        $this->authorize('run', ReportDefinition::class);
        $context = app(TenantContext::class);
        $definition = $this->developmentService->showDefinition($context, $moduleKey, $reportKey);

        return new ReportRenderResource(
            $this->developmentService->renderReport(
                $context,
                $definition,
                $request->input('context', []),
            ),
        );
    }

    public function run(Request $request, string $moduleKey, string $reportKey): ReportRunResource
    {
        $this->authorize('run', ReportDefinition::class);
        $context = app(TenantContext::class);

        return new ReportRunResource(
            $this->developmentService->runReport(
                $context,
                $moduleKey,
                $reportKey,
                $request->input('parameters', []),
            ),
        );
    }

    public function export(Request $request, string $moduleKey, string $reportKey): ReportExportResource
    {
        $this->authorize('export', ReportDefinition::class);
        $validated = $request->validate([
            'export_format' => ['required', 'string', 'in:pdf,excel,csv,json'],
            'parameters' => ['nullable', 'array'],
        ]);
        $context = app(TenantContext::class);

        return new ReportExportResource(
            $this->developmentService->exportReport(
                $context,
                $moduleKey,
                $reportKey,
                $validated['export_format'],
                $validated['parameters'] ?? [],
            ),
        );
    }

    public function runs(string $moduleKey, string $reportKey): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', ReportDefinition::class);
        $context = app(TenantContext::class);

        return ReportRunResource::collection(
            $this->developmentService->listRuns($context, $moduleKey, $reportKey),
        );
    }

    public function showRun(string $runPublicId): ReportRunResource
    {
        $this->authorize('view', ReportDefinition::class);
        $context = app(TenantContext::class);

        return new ReportRunResource(
            $this->developmentService->showRun($context, $runPublicId),
        );
    }

    public function exports(string $moduleKey, string $reportKey): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', ReportDefinition::class);
        $context = app(TenantContext::class);

        return ReportExportResource::collection(
            $this->developmentService->listExports($context, $moduleKey, $reportKey),
        );
    }

    public function showExport(string $exportPublicId): ReportExportResource
    {
        $this->authorize('view', ReportDefinition::class);
        $context = app(TenantContext::class);

        return new ReportExportResource(
            $this->developmentService->showExport($context, $exportPublicId),
        );
    }

    public function storeSchedule(Request $request, string $moduleKey, string $reportKey): \Illuminate\Http\JsonResponse
    {
        $this->authorize('schedule', ReportDefinition::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cron_expression' => ['nullable', 'string', 'max:128'],
            'run_at' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'export_formats' => ['nullable', 'array'],
            'recipients' => ['nullable', 'array'],
            'parameters' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);
        $context = app(TenantContext::class);

        return (new ReportScheduleResource(
            $this->developmentService->createSchedule(
                $context,
                $moduleKey,
                $reportKey,
                ReportScheduleDefinition::fromArray(array_merge($validated, [
                    'module_key' => $moduleKey,
                    'report_key' => $reportKey,
                ])),
            ),
        ))->response()->setStatusCode(201);
    }

    public function schedules(string $moduleKey, string $reportKey): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', ReportDefinition::class);
        $context = app(TenantContext::class);

        return ReportScheduleResource::collection(
            $this->developmentService->listSchedules($context, $moduleKey, $reportKey),
        );
    }

    public function pauseSchedule(string $schedulePublicId): ReportScheduleResource
    {
        $this->authorize('schedule', ReportDefinition::class);
        $context = app(TenantContext::class);

        return new ReportScheduleResource(
            $this->developmentService->pauseSchedule($context, $schedulePublicId),
        );
    }

    public function resumeSchedule(string $schedulePublicId): ReportScheduleResource
    {
        $this->authorize('schedule', ReportDefinition::class);
        $context = app(TenantContext::class);

        return new ReportScheduleResource(
            $this->developmentService->resumeSchedule($context, $schedulePublicId),
        );
    }

    public function destroySchedule(string $schedulePublicId): \Illuminate\Http\Response
    {
        $this->authorize('schedule', ReportDefinition::class);
        $context = app(TenantContext::class);

        $this->developmentService->deleteSchedule($context, $schedulePublicId);

        return response()->noContent();
    }
}
