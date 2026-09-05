<?php

namespace App\Filament\Resources\AttributeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected array $translationsData = [];

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.attribute_values');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Attribute Value Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.general_data'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.basic_information'))
                                    ->schema([
                                        Forms\Components\ColorPicker::make('color_code')
                                            ->label(__('admin.color_code'))
                                            ->visible(fn () => $this->ownerRecord->type === 'color'),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label(__('admin.sort_order'))
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.arabic_content'))
                            ->schema([
                                Forms\Components\TextInput::make('ar_value')
                                    ->label(__('admin.value_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.english_content'))
                            ->schema([
                                Forms\Components\TextInput::make('en_value')
                                    ->label(__('admin.value_en'))
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('arabicTranslation.value')
                    ->label(__('admin.value_ar'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('englishTranslation.value')
                    ->label(__('admin.value_en'))
                    ->searchable(),

                Tables\Columns\ColorColumn::make('color_code')
                    ->label(__('admin.color_code'))
                    ->visible(fn () => $this->ownerRecord->type === 'color'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.active')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_value'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $this->translationsData = [
                            'ar' => [
                                'value' => $data['ar_value'],
                            ],
                            'en' => [
                                'value' => $data['en_value'],
                            ],
                        ];

                        unset(
                            $data['ar_value'],
                            $data['en_value'],
                        );

                        return $data;
                    })
                    ->after(function ($record): void {
                        foreach ($this->translationsData as $locale => $translationData) {
                            $record->translations()->create([
                                'locale' => $locale,
                                ...$translationData,
                            ]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        $record->load([
                            'arabicTranslation',
                            'englishTranslation',
                        ]);

                        $data['ar_value'] = $record->arabicTranslation?->value;
                        $data['en_value'] = $record->englishTranslation?->value;

                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $this->translationsData = [
                            'ar' => [
                                'value' => $data['ar_value'],
                            ],
                            'en' => [
                                'value' => $data['en_value'],
                            ],
                        ];

                        unset(
                            $data['ar_value'],
                            $data['en_value'],
                        );

                        return $data;
                    })
                    ->after(function ($record): void {
                        foreach ($this->translationsData as $locale => $translationData) {
                            $record->translations()->updateOrCreate(
                                [
                                    'locale' => $locale,
                                ],
                                $translationData
                            );
                        }
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}