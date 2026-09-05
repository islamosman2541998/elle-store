<?php

namespace App\Filament\Resources\PageResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.page_images');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image')
                    ->label(__('admin.image'))
                    ->image()
                    ->directory('pages/gallery')
                    ->disk('public')
                    ->imageEditor()
                    ->required()
                    ->maxSize(4096)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('title_ar')
                    ->label(__('admin.title_ar'))
                    ->maxLength(255),

                Forms\Components\TextInput::make('title_en')
                    ->label(__('admin.title_en'))
                    ->maxLength(255),

                Forms\Components\Toggle::make('is_active')
                    ->label(__('admin.active'))
                    ->default(true),

                Forms\Components\TextInput::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('image')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label(__('admin.title_ar'))
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('title_en')
                    ->label(__('admin.title_en'))
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_image')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('admin.edit')),

                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.delete')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label(__('admin.delete_selected')),
            ])
            ->defaultSort('sort_order');
    }
}