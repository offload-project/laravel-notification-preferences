<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\DataTransferObjects;

final readonly class NotificationGroupData
{
    /**
     * @param  array<int, NotificationPreferenceData>  $notifications
     */
    public function __construct(
        public string $group,
        public string $label,
        public ?string $description,
        public array $notifications
    ) {}

    /**
     * @return array{group: string, label: string, description: string|null, notifications: array<int, array{type: string, label: string, description: string|null, channels: array<string, array{enabled: bool, forced: bool}>}>}
     */
    public function toArray(): array
    {
        return [
            'group' => $this->group,
            'label' => $this->label,
            'description' => $this->description,
            'notifications' => array_map(fn ($n) => $n->toArray(), $this->notifications),
        ];
    }
}
