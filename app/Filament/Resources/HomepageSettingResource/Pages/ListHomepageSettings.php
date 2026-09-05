<?php

namespace App\Filament\Resources\HomepageSettingResource\Pages;

use App\Filament\Resources\HomepageSettingResource;
use App\Models\HomepageSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomepageSettings extends ListRecords
{
    protected static string $resource = HomepageSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_settings')
                ->label(__('admin.edit'))
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => HomepageSettingResource::getUrl('edit', [
                    'record' => HomepageSetting::current()->id,
                ])),
        ];
    }
}