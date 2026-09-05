<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Attribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ProductAttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'productAttributes';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.product_attributes');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('attribute_id')
                    ->label(__('admin.attribute'))
                    ->options(function () {
                        return Attribute::query()
                            ->with('arabicTranslation')
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($attribute) => [
                                $attribute->id => $attribute->arabicTranslation?->name ?? 'Attribute #' . $attribute->id,
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules(fn ($record) => [
                        Rule::unique('product_attributes', 'attribute_id')
                            ->where('product_id', $this->ownerRecord->id)
                            ->ignore($record?->id),
                    ]),

                Forms\Components\TextInput::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('attribute.arabicTranslation.name')
                    ->label(__('admin.name_ar'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('attribute.englishTranslation.name')
                    ->label(__('admin.name_en'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('attribute.type')
                    ->label(__('admin.attribute_type'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'color' => 'warning',
                        'button' => 'info',
                        'text' => 'gray',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_attribute')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}