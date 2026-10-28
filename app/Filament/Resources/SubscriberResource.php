<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Subscriber;

class SubscriberResource extends LegacyResource
{
    protected static ?string $model = Subscriber::class;

    protected static string $moduleKey = 'subscriber';

    public static function getPages(): array
    {
        return [
            'index' => SubscriberResource\Pages\ListSubscriber::route('/'),
        ];
    }
}
