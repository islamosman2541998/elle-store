<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WishlistItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'wishlistItems';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.wishlist_items');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label(__('admin.product'))
                    ->options(function () {
                        return Product::query()
                            ->with('arabicTranslation')
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($product) => [
                                $product->id => $product->arabicTranslation?->name ?? 'Product #' . $product->id,
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'product.arabicTranslation',
                'product.englishTranslation',
            ]))
            ->recordTitleAttribute('product_id')
            ->columns([
                Tables\Columns\ImageColumn::make('product.main_image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('product.arabicTranslation.name')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('product.englishTranslation.name')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('product.price')
                    ->label(__('admin.price'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                Tables\Columns\IconColumn::make('product.is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.added_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_wishlist_item')),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}