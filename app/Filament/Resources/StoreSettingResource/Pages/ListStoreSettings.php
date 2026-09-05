<?php

namespace App\Filament\Resources\StoreSettingResource\Pages;

use App\Filament\Resources\StoreSettingResource;
use App\Models\StoreSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreSettings extends ListRecords
{
    protected static string $resource = StoreSettingResource::class;

    public function mount(): void
    {
        parent::mount();

        StoreSetting::current();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit_settings')
                ->label(__('admin.edit_store_settings'))
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn () => StoreSettingResource::getUrl('edit', [
                    'record' => StoreSetting::current()->id,
                ])),
        ];
    }
}