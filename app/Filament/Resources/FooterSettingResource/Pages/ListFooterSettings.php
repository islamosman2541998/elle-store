<?php

namespace App\Filament\Resources\FooterSettingResource\Pages;

use App\Filament\Resources\FooterSettingResource;
use App\Models\FooterSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFooterSettings extends ListRecords
{
    protected static string $resource = FooterSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_settings')
                ->label(__('admin.edit'))
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => FooterSettingResource::getUrl('edit', [
                    'record' => FooterSetting::current()->id,
                ])),
        ];
    }
}