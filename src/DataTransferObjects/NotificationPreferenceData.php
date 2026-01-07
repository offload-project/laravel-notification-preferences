<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\DataTransferObjects;

final readonly class NotificationPreferenceData
{
    /**
     * @param  array<string, ChannelPreferenceData>  $channels
     */
    public function __construct(
        public string $type,
        public string $label,
        public ?string $description,
        public array $channels
    ) {}

    /**
     * @return array{type: string, label: string, description: string|null, channels: array<string, array{enabled: bool, forced: bool}>}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'description' => $this->description,
            'channels' => array_map(
                fn (ChannelPreferenceData $channel) => $channel->toArray(),
                $this->channels
            ),
        ];
    }
}
