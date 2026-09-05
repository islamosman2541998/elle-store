<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WishlistItemResource\Pages;
use App\Models\Customer;
use App\Models\Product;
use App\Models\WishlistItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class WishlistItemResource extends Resource
{
    protected static ?string $model = WishlistItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.customers_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.wishlist');
    }

    public static function getModelLabel(): string
    {
        return __('admin.wishlist_item');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.wishlist');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.wishlist_item_details'))
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label(__('admin.customer'))
                            ->options(fn () => Customer::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText(app()->getLocale() === 'ar'
                                ? 'اتركه فارغًا إذا كانت الإضافة لزائر'
                                : 'Leave empty if this wishlist item belongs to a guest'),

                        Forms\Components\TextInput::make('session_id')
                            ->label(app()->getLocale() === 'ar' ? 'جلسة الزائر' : 'Guest Session')
                            ->maxLength(255)
                            ->helperText(app()->getLocale() === 'ar'
                                ? 'يُستخدم فقط لو العنصر مضاف بواسطة زائر'
                                : 'Used only when the item belongs to a guest'),

                        Forms\Components\Select::make('product_id')
                            ->label(__('admin.product'))
                            ->options(function () {
                                return Product::query()
                                    ->with('arabicTranslation')
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->mapWithKeys(fn ($product) => [
                                        $product->id => $product->arabicTranslation?->name ?? 'Product #' . $product->id,
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'customer',
                'product.arabicTranslation',
                'product.englishTranslation',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('customer_display')
                    ->label(__('admin.customer'))
                    ->state(function ($record) {
                        if ($record->customer) {
                            return $record->customer->name;
                        }

                        return app()->getLocale() === 'ar' ? 'زائر' : 'Guest';
                    })
                    ->badge()
                    ->color(fn ($record) => $record->customer ? 'success' : 'gray')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', function (Builder $customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label(__('admin.phone'))
                    ->state(fn ($record) => $record->customer?->phone ?: '-')
                    ->copyable(fn ($record) => filled($record->customer?->phone))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', function (Builder $customerQuery) use ($search) {
                            $customerQuery->where('phone', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('session_id')
                    ->label(app()->getLocale() === 'ar' ? 'جلسة الزائر' : 'Guest Session')
                    ->formatStateUsing(fn ($state) => $state ? Str::limit($state, 14) : '-')
                    ->badge()
                    ->color('warning')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ImageColumn::make('product.main_image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label(__('admin.product'))
                    ->state(function ($record) {
                        return $record->product?->arabicTranslation?->name
                            ?? $record->product?->englishTranslation?->name
                            ?? 'Product #' . $record->product_id;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('product.arabicTranslation', function (Builder $productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%");
                        })->orWhereHas('product.englishTranslation', function (Builder $productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('product.price')
                    ->label(__('admin.price'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->sortable(),

                Tables\Columns\IconColumn::make('product.is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.added_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(app()->getLocale() === 'ar' ? 'نوع الإضافة' : 'Type')
                    ->options([
                        'customer' => app()->getLocale() === 'ar' ? 'عملاء مسجلين' : 'Customers',
                        'guest' => app()->getLocale() === 'ar' ? 'زوار' : 'Guests',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'customer' => $query->whereNotNull('customer_id'),
                            'guest' => $query->whereNull('customer_id')->whereNotNull('session_id'),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->label(__('admin.customer'))
                    ->options(fn () => Customer::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label(__('admin.product'))
                    ->options(function () {
                        return Product::query()
                            ->with('arabicTranslation')
                            ->get()
                            ->mapWithKeys(fn ($product) => [
                                $product->id => $product->arabicTranslation?->name ?? 'Product #' . $product->id,
                            ])
                            ->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('admin.view')),

                Tables\Actions\EditAction::make()
                    ->label(__('admin.edit')),

                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.delete')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label(__('admin.delete')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.wishlist_item_details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_display')
                            ->label(__('admin.customer'))
                            ->state(function ($record) {
                                if ($record->customer) {
                                    return $record->customer->name;
                                }

                                return app()->getLocale() === 'ar' ? 'زائر' : 'Guest';
                            })
                            ->badge()
                            ->color(fn ($record) => $record->customer ? 'success' : 'gray'),

                        Infolists\Components\TextEntry::make('customer.phone')
                            ->label(__('admin.phone'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('customer.email')
                            ->label(__('admin.email'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('session_id')
                            ->label(app()->getLocale() === 'ar' ? 'جلسة الزائر' : 'Guest Session')
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('product_name')
                            ->label(__('admin.product'))
                            ->state(function ($record) {
                                return $record->product?->arabicTranslation?->name
                                    ?? $record->product?->englishTranslation?->name
                                    ?? 'Product #' . $record->product_id;
                            }),

                        Infolists\Components\ImageEntry::make('product.main_image')
                            ->label(__('admin.image'))
                            ->disk('public'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('admin.added_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWishlistItems::route('/'),
            'create' => Pages\CreateWishlistItem::route('/create'),
            'view' => Pages\ViewWishlistItem::route('/{record}'),
            'edit' => Pages\EditWishlistItem::route('/{record}/edit'),
        ];
    }
}