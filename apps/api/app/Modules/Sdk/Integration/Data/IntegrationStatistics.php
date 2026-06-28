<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationStatistics implements \JsonSerializable
{
    public function __construct(
        public int $events,
        public int $subscriptions,
        public int $connectors,
        public int $endpoints,
        public int $dispatches,
        public int $deadLetters,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            events: (int) ($data['events'] ?? $data['Events'] ?? 0),
            subscriptions: (int) ($data['subscriptions'] ?? $data['Subscriptions'] ?? 0),
            connectors: (int) ($data['connectors'] ?? $data['Connectors'] ?? 0),
            endpoints: (int) ($data['endpoints'] ?? $data['Endpoints'] ?? 0),
            dispatches: (int) ($data['dispatches'] ?? $data['Dispatches'] ?? 0),
            deadLetters: (int) ($data['dead_letters'] ?? $data['DeadLetters'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'events' => $this->events,
            'subscriptions' => $this->subscriptions,
            'connectors' => $this->connectors,
            'endpoints' => $this->endpoints,
            'dispatches' => $this->dispatches,
            'dead_letters' => $this->deadLetters,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
