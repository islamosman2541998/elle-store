<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.customer_addresses');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Address Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.basic_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.address_contact'))
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label(__('admin.address_label'))
                                            ->maxLength(255)
                                            ->placeholder(__('admin.address_label_placeholder')),

                                        Forms\Components\Toggle::make('is_default')
                                            ->label(__('admin.default_address'))
                                            ->default(false),

                                        Forms\Components\TextInput::make('name')
                                            ->label(__('admin.receiver_name'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('phone')
                                            ->label(__('admin.receiver_phone'))
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.address_details'))
                                    ->schema([
                                        Forms\Components\TextInput::make('country')
                                            ->label(__('admin.country'))
                                            ->required()
                                            ->default('Egypt')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('city')
                                            ->label(__('admin.city'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('area')
                                            ->label(__('admin.area'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('street')
                                            ->label(__('admin.street'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('building')
                                            ->label(__('admin.building'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('floor')
                                            ->label(__('admin.floor'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('apartment')
                                            ->label(__('admin.apartment'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('landmark')
                                            ->label(__('admin.landmark'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('notes')
                                            ->label(__('admin.notes'))
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.location'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.map_coordinates'))
                                    ->schema([
                                        Forms\Components\TextInput::make('latitude')
                                            ->label(__('admin.latitude'))
                                            ->numeric()
                                            ->nullable(),

                                        Forms\Components\TextInput::make('longitude')
                                            ->label(__('admin.longitude'))
                                            ->numeric()
                                            ->nullable(),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label(__('admin.address_label'))
                    ->badge()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.receiver_name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin.receiver_phone'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('city')
                    ->label(__('admin.city'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('area')
                    ->label(__('admin.area'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('street')
                    ->label(__('admin.street'))
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->street),

                Tables\Columns\IconColumn::make('is_default')
                    ->label(__('admin.default_address'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label(__('admin.default_address')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_address'))
                    ->after(function ($record): void {
                        $this->handleDefaultAddress($record);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record): void {
                        $this->handleDefaultAddress($record);
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('is_default', 'desc');
    }

    protected function handleDefaultAddress($record): void
    {
        if (! $record->is_default) {
            return;
        }

        $this->ownerRecord
            ->addresses()
            ->where('id', '!=', $record->id)
            ->update([
                'is_default' => false,
            ]);
    }
}