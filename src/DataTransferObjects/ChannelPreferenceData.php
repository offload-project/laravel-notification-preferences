<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\DataTransferObjects;

final readonly class ChannelPreferenceData
{
    public function __construct(
        public string $channel,
        public bool $enabled,
        public bool $forced
    ) {}

    /**
     * @return array{enabled: bool, forced: bool}
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'forced' => $this->forced,
        ];
    }
}
