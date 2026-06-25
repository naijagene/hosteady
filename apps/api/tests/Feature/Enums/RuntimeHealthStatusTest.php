<?php

namespace Tests\Feature\Enums;

use App\Enums\RuntimeHealthStatus;
use Tests\TestCase;

class RuntimeHealthStatusTest extends TestCase
{
    public function test_worst_returns_critical_when_present(): void
    {
        $this->assertSame(
            RuntimeHealthStatus::Critical,
            RuntimeHealthStatus::worst(
                RuntimeHealthStatus::Healthy,
                RuntimeHealthStatus::Warning,
                RuntimeHealthStatus::Critical,
            ),
        );
    }

    public function test_worst_returns_warning_when_no_critical(): void
    {
        $this->assertSame(
            RuntimeHealthStatus::Warning,
            RuntimeHealthStatus::worst(
                RuntimeHealthStatus::Healthy,
                RuntimeHealthStatus::Warning,
            ),
        );
    }

    public function test_worst_returns_healthy_when_all_healthy(): void
    {
        $this->assertSame(
            RuntimeHealthStatus::Healthy,
            RuntimeHealthStatus::worst(
                RuntimeHealthStatus::Healthy,
                RuntimeHealthStatus::Healthy,
            ),
        );
    }
}
