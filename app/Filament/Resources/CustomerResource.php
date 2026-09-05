<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\WishlistItemsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\CartsRelationManager;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.customers_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.customers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.customers');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Customer Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.basic_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.customer_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('admin.name'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('email')
                                            ->label(__('admin.email'))
                                            ->email()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('phone')
                                            ->label(__('admin.phone'))
                                            ->tel()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        Forms\Components\DatePicker::make('birth_date')
                                            ->label(__('admin.birth_date'))
                                            ->native(false),

                                        Forms\Components\Select::make('gender')
                                            ->label(__('admin.gender'))
                                            ->options([
                                                'male' => __('admin.male'),
                                                'female' => __('admin.female'),
                                            ])
                                            ->nullable(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.account_status'))
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('accepts_marketing')
                                            ->label(__('admin.accepts_marketing'))
                                            ->default(false),

                                        Forms\Components\DateTimePicker::make('email_verified_at')
                                            ->label(__('admin.email_verified_at'))
                                            ->seconds(false)
                                            ->nullable(),

                                        Forms\Components\DateTimePicker::make('phone_verified_at')
                                            ->label(__('admin.phone_verified_at'))
                                            ->seconds(false)
                                            ->nullable(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.security'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.password_settings'))
                                    ->schema([
                                        Forms\Components\TextInput::make('password')
                                            ->label(__('admin.password'))
                                            ->password()
                                            ->revealable()
                                            ->dehydrated(fn($state) => filled($state))
                                            ->required(fn(string $operation): bool => $operation === 'create')
                                            ->minLength(8)
                                            ->helperText(__('admin.password_helper')),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->withCount('addresses'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin.email'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin.phone'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('accepts_marketing')
                    ->label(__('admin.accepts_marketing'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('addresses_count')
                    ->label(__('admin.addresses_count'))
                    ->badge(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label(__('admin.last_login_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.active')),

                Tables\Filters\TernaryFilter::make('accepts_marketing')
                    ->label(__('admin.accepts_marketing')),

                Tables\Filters\SelectFilter::make('gender')
                    ->label(__('admin.gender'))
                    ->options([
                        'male' => __('admin.male'),
                        'female' => __('admin.female'),
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.customer_overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label(__('admin.name'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('email')
                            ->label(__('admin.email'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('admin.phone'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('gender')
                            ->label(__('admin.gender'))
                            ->formatStateUsing(fn($state) => $state ? __('admin.' . $state) : '-'),

                        Infolists\Components\TextEntry::make('birth_date')
                            ->label(__('admin.birth_date'))
                            ->date()
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('accepts_marketing')
                            ->label(__('admin.accepts_marketing'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('last_login_at')
                            ->label(__('admin.last_login_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.verification_data'))
                    ->schema([
                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label(__('admin.email_verified_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('phone_verified_at')
                            ->label(__('admin.phone_verified_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('admin.created_at'))
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('admin.updated_at'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AddressesRelationManager::class,
            WishlistItemsRelationManager::class,
            CartsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}