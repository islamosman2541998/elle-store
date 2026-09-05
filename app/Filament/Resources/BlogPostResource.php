<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = 4;

     public static function getNavigationGroup(): ?string
{
    return __('admin.content_management');
}

    public static function getNavigationLabel(): string
    {
        return __('admin.blog_posts');
    }

    public static function getModelLabel(): string
    {
        return __('admin.blog_post');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.blog_posts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Blog Post Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.main_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.main_information'))
                                    ->schema([
                                        Forms\Components\Select::make('blog_category_id')
                                            ->label(__('admin.blog_category'))
                                            ->options(function () {
                                                return BlogCategory::query()
                                                    ->where('is_active', true)
                                                    ->orderBy('sort_order')
                                                    ->get()
                                                    ->mapWithKeys(fn ($category) => [
                                                        $category->id => app()->getLocale() === 'ar'
                                                            ? $category->name_ar
                                                            : $category->name_en,
                                                    ])
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),

                                        Forms\Components\TextInput::make('author_name')
                                            ->label(__('admin.author_name'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state) {
                                                    $set('slug_ar', Str::slug($state));
                                                }
                                            }),

                                        Forms\Components\TextInput::make('title_en')
                                            ->label(__('admin.title_en'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state) {
                                                    $set('slug_en', Str::slug($state));
                                                }
                                            }),

                                        Forms\Components\TextInput::make('slug_ar')
                                            ->label(__('admin.slug_ar'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('slug_en')
                                            ->label(__('admin.slug_en'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        Forms\Components\FileUpload::make('featured_image')
                                            ->label(__('admin.featured_image'))
                                            ->image()
                                            ->directory('blogs/posts')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(4096)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.content'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.arabic_content'))
                                    ->schema([
                                        Forms\Components\Textarea::make('excerpt_ar')
                                            ->label(__('admin.excerpt_ar'))
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('content_ar')
                                            ->label(__('admin.content_ar'))
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make(__('admin.english_content'))
                                    ->schema([
                                        Forms\Components\Textarea::make('excerpt_en')
                                            ->label(__('admin.excerpt_en'))
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('content_en')
                                            ->label(__('admin.content_en'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.publishing_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.publishing_settings'))
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->label(__('admin.status'))
                                            ->options(fn () => BlogPost::statuses())
                                            ->required()
                                            ->default('draft')
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state === 'published') {
                                                    $set('published_at', now());
                                                }
                                            }),

                                        Forms\Components\Toggle::make('is_featured')
                                            ->label(__('admin.featured'))
                                            ->default(false),

                                        Forms\Components\DateTimePicker::make('published_at')
                                            ->label(__('admin.published_at'))
                                            ->seconds(false),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label(__('admin.sort_order'))
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.seo_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.seo_settings'))
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title_ar')
                                            ->label(__('admin.meta_title_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('meta_title_en')
                                            ->label(__('admin.meta_title_en'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('meta_description_ar')
                                            ->label(__('admin.meta_description_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('meta_description_en')
                                            ->label(__('admin.meta_description_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('category')
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]))
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label(__('admin.title_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title_en')
                    ->label(__('admin.title_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name_ar')
                    ->label(__('admin.blog_category'))
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => BlogPost::statuses()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('admin.featured'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('admin.published_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options(fn () => BlogPost::statuses()),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label(__('admin.featured')),

                Tables\Filters\SelectFilter::make('blog_category_id')
                    ->label(__('admin.blog_category'))
                    ->options(function () {
                        return BlogCategory::query()
                            ->orderBy('sort_order')
                            ->pluck('name_ar', 'id')
                            ->toArray();
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('publish')
                    ->label(__('admin.publish'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->publish()),

                Tables\Actions\Action::make('archive')
                    ->label(__('admin.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status !== 'archived')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->archive()),

                Tables\Actions\ViewAction::make()
                    ->label(__('admin.view')),

                Tables\Actions\EditAction::make()
                    ->label(__('admin.edit')),

                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.delete')),

                Tables\Actions\RestoreAction::make()
                    ->label(__('admin.restore')),

                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('admin.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish_selected')
                        ->label(__('admin.publish_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->publish()),

                    Tables\Actions\BulkAction::make('archive_selected')
                        ->label(__('admin.archive_selected'))
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->archive()),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('admin.delete_selected')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('admin.restore_selected')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('admin.force_delete_selected')),
                ]),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.main_information'))
                    ->schema([
                        Infolists\Components\ImageEntry::make('featured_image')
                            ->label(__('admin.featured_image'))
                            ->disk('public')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('category.name_ar')
                            ->label(__('admin.blog_category'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('title_ar')
                            ->label(__('admin.title_ar')),

                        Infolists\Components\TextEntry::make('title_en')
                            ->label(__('admin.title_en')),

                        Infolists\Components\TextEntry::make('slug_ar')
                            ->label(__('admin.slug_ar'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('slug_en')
                            ->label(__('admin.slug_en'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('author_name')
                            ->label(__('admin.author_name'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.publishing_settings'))
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => BlogPost::statuses()[$state] ?? $state)
                            ->color(fn ($state) => match ($state) {
                                'draft' => 'gray',
                                'published' => 'success',
                                'archived' => 'warning',
                                default => 'gray',
                            }),

                        Infolists\Components\IconEntry::make('is_featured')
                            ->label(__('admin.featured'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('published_at')
                            ->label(__('admin.published_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('sort_order')
                            ->label(__('admin.sort_order')),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.content'))
                    ->schema([
                        Infolists\Components\TextEntry::make('excerpt_ar')
                            ->label(__('admin.excerpt_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('excerpt_en')
                            ->label(__('admin.excerpt_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('content_ar')
                            ->label(__('admin.content_ar'))
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('content_en')
                            ->label(__('admin.content_en'))
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.seo_settings'))
                    ->schema([
                        Infolists\Components\TextEntry::make('meta_title_ar')
                            ->label(__('admin.meta_title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('meta_title_en')
                            ->label(__('admin.meta_title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('meta_description_ar')
                            ->label(__('admin.meta_description_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('meta_description_en')
                            ->label(__('admin.meta_description_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'view' => Pages\ViewBlogPost::route('/{record}'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}