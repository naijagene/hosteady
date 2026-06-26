<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovalResource;
use App\Policies\ApprovalPolicy;
use App\Services\Enterprise\Workflow\Human\ApprovalService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApprovalService $approvalService,
        private readonly ApprovalPolicy $approvalPolicy,
    ) {
    }

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        abort_unless($this->approvalPolicy->viewAny($request->user()), 403);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,approved,rejected,completed'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return ApprovalResource::collection(
            $this->approvalService->list($context, $validated['status'] ?? null),
        );
    }

    public function show(Request $request, string $publicId): ApprovalResource
    {
        abort_unless($this->approvalPolicy->view($request->user()), 403);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new ApprovalResource(
            $this->approvalService->show($context, $publicId),
        );
    }

    public function approve(Request $request, string $publicId): \Illuminate\Http\JsonResponse
    {
        abort_unless($this->approvalPolicy->decide($request->user()), 403);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return response()->json([
            'data' => $this->approvalService->approve($context, $publicId, $validated['comment'] ?? null)->toArray(),
        ]);
    }

    public function reject(Request $request, string $publicId): \Illuminate\Http\JsonResponse
    {
        abort_unless($this->approvalPolicy->decide($request->user()), 403);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return response()->json([
            'data' => $this->approvalService->reject($context, $publicId, $validated['comment'] ?? null)->toArray(),
        ]);
    }
}
