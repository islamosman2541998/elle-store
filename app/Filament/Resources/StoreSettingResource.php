<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreSettingResource\Pages;
use App\Models\StoreSetting;
use App\Filament\Resources\NotificationTemplateResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreSettingResource extends Resource
{
    protected static ?string $model = StoreSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.system_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.store_settings');
    }

    public static function getModelLabel(): string
    {
        return __('admin.store_settings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.store_settings');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Store Settings Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.general_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.store_identity'))
                                    ->schema([
                                        Forms\Components\TextInput::make('store_name_ar')
                                            ->label(__('admin.store_name_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('store_name_en')
                                            ->label(__('admin.store_name_en'))
                                            ->maxLength(255),

                                        Forms\Components\FileUpload::make('logo')
                                            ->label(__('admin.logo'))
                                            ->image()
                                            ->directory('settings')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(2048),

                                        Forms\Components\FileUpload::make('favicon')
                                            ->label(__('admin.favicon'))
                                            ->image()
                                            ->directory('settings')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(1024),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.store_status'))
                                    ->schema([
                                        Forms\Components\Toggle::make('is_store_active')
                                            ->label(__('admin.store_active'))
                                            ->default(true),

                                        Forms\Components\Select::make('default_locale')
                                            ->label(__('admin.default_locale'))
                                            ->options([
                                                'ar' => __('admin.arabic'),
                                                'en' => __('admin.english'),
                                            ])
                                            ->required()
                                            ->default('ar'),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make(__('admin.announcement_bar_settings'))
                                    ->schema([
                                        Forms\Components\Toggle::make('announcement_bar_enabled')
                                            ->label(__('admin.announcement_bar_enabled'))
                                            ->default(false),

                                        Forms\Components\TextInput::make('announcement_bar_text_ar')
                                            ->label(__('admin.announcement_bar_text_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('announcement_bar_text_en')
                                            ->label(__('admin.announcement_bar_text_en'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('announcement_bar_url')
                                            ->label(__('admin.announcement_bar_url'))
                                            // The bar usually links somewhere internal, e.g. /shop.
                                            ->rule(new \App\Rules\LinkUrl())
                                            ->maxLength(255),

                                        Forms\Components\Toggle::make('announcement_bar_open_in_new_tab')
                                            ->label(__('admin.open_in_new_tab'))
                                            ->default(false),

                                        Forms\Components\ColorPicker::make('announcement_bar_bg_color')
                                            ->label(__('admin.announcement_bar_bg_color'))
                                            ->default('#111827'),

                                        Forms\Components\ColorPicker::make('announcement_bar_text_color')
                                            ->label(__('admin.announcement_bar_text_color'))
                                            ->default('#ffffff'),

                                        Forms\Components\TextInput::make('announcement_bar_speed')
                                            ->label(__('admin.announcement_bar_speed'))
                                            ->numeric()
                                            ->minValue(8)
                                            ->maxValue(80)
                                            ->default(25)
                                            ->suffix(__('admin.seconds')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.contact_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.contact_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->label(__('admin.email'))
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('phone')
                                            ->label(__('admin.phone'))
                                            ->tel()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('whatsapp')
                                            ->label(__('admin.whatsapp'))
                                            ->tel()
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('address_ar')
                                            ->label(__('admin.address_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('address_en')
                                            ->label(__('admin.address_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.currency_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.currency_settings'))
                                    ->schema([
                                        Forms\Components\TextInput::make('currency_code')
                                            ->label(__('admin.currency_code'))
                                            ->required()
                                            ->maxLength(10)
                                            ->default('EGP'),

                                        Forms\Components\TextInput::make('currency_symbol')
                                            ->label(__('admin.currency_symbol'))
                                            ->required()
                                            ->maxLength(10)
                                            ->default('EGP'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.maintenance_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.maintenance_settings'))
                                    ->schema([
                                        Forms\Components\Textarea::make('maintenance_message_ar')
                                            ->label(__('admin.maintenance_message_ar'))
                                            ->rows(4),

                                        Forms\Components\Textarea::make('maintenance_message_en')
                                            ->label(__('admin.maintenance_message_en'))
                                            ->rows(4),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.seo_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.meta_tags'))
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title_ar')
                                            ->label(__('admin.meta_title_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('meta_title_en')
                                            ->label(__('admin.meta_title_en'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('meta_description_ar')
                                            ->label(__('admin.meta_description_ar'))
                                            ->rows(3)
                                            ->helperText(__('admin.meta_description_helper')),

                                        Forms\Components\Textarea::make('meta_description_en')
                                            ->label(__('admin.meta_description_en'))
                                            ->rows(3)
                                            ->helperText(__('admin.meta_description_helper')),

                                        Forms\Components\Textarea::make('meta_keywords_ar')
                                            ->label(__('admin.meta_keywords_ar'))
                                            ->rows(2)
                                            ->helperText(__('admin.meta_keywords_helper')),

                                        Forms\Components\Textarea::make('meta_keywords_en')
                                            ->label(__('admin.meta_keywords_en'))
                                            ->rows(2)
                                            ->helperText(__('admin.meta_keywords_helper')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.open_graph_settings'))
                                    ->schema([
                                        Forms\Components\FileUpload::make('og_image')
                                            ->label(__('admin.og_image'))
                                            ->image()
                                            ->directory('settings/seo')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make(__('admin.technical_seo'))
                                    ->schema([
                                        Forms\Components\Select::make('robots')
                                            ->label(__('admin.robots'))
                                            ->options([
                                                'index, follow' => 'index, follow',
                                                'noindex, follow' => 'noindex, follow',
                                                'index, nofollow' => 'index, nofollow',
                                                'noindex, nofollow' => 'noindex, nofollow',
                                            ])
                                            ->default('index, follow')
                                            ->required(),

                                        Forms\Components\TextInput::make('canonical_url')
                                            ->label(__('admin.canonical_url'))
                                            ->url()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('google_site_verification')
                                            ->label(__('admin.google_site_verification'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('bing_site_verification')
                                            ->label(__('admin.bing_site_verification'))
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.tracking_integrations'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.tracking_general'))
                                    ->schema([
                                        Forms\Components\Toggle::make('tracking_enabled')
                                            ->label(__('admin.tracking_enabled'))
                                            ->default(false),

                                        Forms\Components\Toggle::make('cookie_consent_enabled')
                                            ->label(__('admin.cookie_consent_enabled'))
                                            ->default(true),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.meta_tracking'))
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_pixel_id')
                                            ->label(__('admin.meta_pixel_id'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('meta_capi_access_token')
                                            ->label(__('admin.meta_capi_access_token'))
                                            ->password()
                                            ->revealable()
                                            ->maxLength(1000)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('meta_test_event_code')
                                            ->label(__('admin.meta_test_event_code'))
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.google_tracking'))
                                    ->schema([
                                        Forms\Components\TextInput::make('google_tag_manager_id')
                                            ->label(__('admin.google_tag_manager_id'))
                                            ->placeholder('GTM-XXXXXXX')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('ga4_measurement_id')
                                            ->label(__('admin.ga4_measurement_id'))
                                            ->placeholder('G-XXXXXXXXXX')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('google_ads_conversion_id')
                                            ->label(__('admin.google_ads_conversion_id'))
                                            ->placeholder('AW-XXXXXXXXX')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('google_ads_conversion_label')
                                            ->label(__('admin.google_ads_conversion_label'))
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.other_pixels'))
                                    ->schema([
                                        Forms\Components\TextInput::make('tiktok_pixel_id')
                                            ->label(__('admin.tiktok_pixel_id'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('snapchat_pixel_id')
                                            ->label(__('admin.snapchat_pixel_id'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('linkedin_partner_id')
                                            ->label(__('admin.linkedin_partner_id'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('pinterest_tag_id')
                                            ->label(__('admin.pinterest_tag_id'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('twitter_pixel_id')
                                            ->label(__('admin.twitter_pixel_id'))
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.cookie_consent'))
                                    ->schema([
                                        Forms\Components\Textarea::make('cookie_consent_message_ar')
                                            ->label(__('admin.cookie_consent_message_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('cookie_consent_message_en')
                                            ->label(__('admin.cookie_consent_message_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2),
                            ]),

                        // Everything the shopper sees on the storefront that
                        // is not a page of its own.
                        Forms\Components\Tabs\Tab::make(__('admin.storefront_widgets'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.coupon_popup'))
                                    ->description(__('admin.coupon_popup_hint'))
                                    ->schema([
                                        Forms\Components\Toggle::make('coupon_popup_enabled')
                                            ->label(__('admin.coupon_popup_enabled'))
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('coupon_popup_title_ar')
                                            ->label(__('admin.coupon_popup_title_ar'))
                                            ->placeholder('خصم 10% على أول طلب'),

                                        Forms\Components\TextInput::make('coupon_popup_title_en')
                                            ->label(__('admin.coupon_popup_title_en'))
                                            ->placeholder('10% off your first order'),

                                        Forms\Components\Textarea::make('coupon_popup_text_ar')
                                            ->label(__('admin.coupon_popup_text_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('coupon_popup_text_en')
                                            ->label(__('admin.coupon_popup_text_en'))
                                            ->rows(3),

                                        Forms\Components\TextInput::make('coupon_popup_code')
                                            ->label(__('admin.coupon_popup_code'))
                                            ->helperText(__('admin.coupon_popup_code_hint'))
                                            ->placeholder('ELLE10'),

                                        Forms\Components\FileUpload::make('coupon_popup_image')
                                            ->label(__('admin.coupon_popup_image'))
                                            ->image()
                                            ->directory('settings/popup')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(2048),

                                        Forms\Components\TextInput::make('coupon_popup_button_label_ar')
                                            ->label(__('admin.coupon_popup_button_label_ar'))
                                            ->placeholder('تسوقي الآن'),

                                        Forms\Components\TextInput::make('coupon_popup_button_label_en')
                                            ->label(__('admin.coupon_popup_button_label_en'))
                                            ->placeholder('Shop now'),

                                        Forms\Components\TextInput::make('coupon_popup_delay')
                                            ->label(__('admin.coupon_popup_delay'))
                                            ->helperText(__('admin.coupon_popup_delay_hint'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(60)
                                            ->default(4)
                                            ->suffix(__('admin.seconds')),

                                        Forms\Components\TextInput::make('coupon_popup_remember_days')
                                            ->label(__('admin.coupon_popup_remember_days'))
                                            ->helperText(__('admin.coupon_popup_remember_days_hint'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(365)
                                            ->default(7)
                                            ->suffix(__('admin.days')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.whatsapp_button'))
                                    ->description(__('admin.whatsapp_button_hint'))
                                    ->schema([
                                        Forms\Components\Toggle::make('whatsapp_button_enabled')
                                            ->label(__('admin.whatsapp_button_enabled'))
                                            ->default(true)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('whatsapp_button_message_ar')
                                            ->label(__('admin.whatsapp_button_message_ar'))
                                            ->helperText(__('admin.whatsapp_button_message_hint'))
                                            ->placeholder('السلام عليكم، عايزة أستفسر عن...'),

                                        Forms\Components\TextInput::make('whatsapp_button_message_en')
                                            ->label(__('admin.whatsapp_button_message_en'))
                                            ->placeholder('Hello, I have a question about...'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.payment_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.payment_methods'))
                                    ->schema([
                                        Forms\Components\Toggle::make('cash_on_delivery_enabled')
                                            ->label(__('admin.cash_on_delivery_enabled'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('bank_transfer_enabled')
                                            ->label(__('admin.bank_transfer_enabled'))
                                            ->default(false),

                                        Forms\Components\Toggle::make('wallet_transfer_enabled')
                                            ->label(__('admin.wallet_transfer_enabled'))
                                            ->default(false),

                                        Forms\Components\Toggle::make('instapay_enabled')
                                            ->label(__('admin.instapay_enabled'))
                                            ->helperText(__('admin.instapay_enabled_hint'))
                                            ->default(false),

                                        Forms\Components\Toggle::make('payment_proof_required')
                                            ->label(__('admin.payment_proof_required'))
                                            ->default(false),

                                        Forms\Components\TextInput::make('cash_on_delivery_fee')
                                            ->label(__('admin.cash_on_delivery_fee'))
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('EGP'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.bank_transfer_details'))
                                    ->schema([
                                        Forms\Components\Textarea::make('bank_account_details_ar')
                                            ->label(__('admin.bank_account_details_ar'))
                                            ->rows(4),

                                        Forms\Components\Textarea::make('bank_account_details_en')
                                            ->label(__('admin.bank_account_details_en'))
                                            ->rows(4),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.wallet_transfer_details'))
                                    ->schema([
                                        Forms\Components\Textarea::make('wallet_details_ar')
                                            ->label(__('admin.wallet_details_ar'))
                                            ->rows(4),

                                        Forms\Components\Textarea::make('wallet_details_en')
                                            ->label(__('admin.wallet_details_en'))
                                            ->rows(4),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.instapay_details'))
                                    ->description(__('admin.instapay_details_hint'))
                                    ->schema([
                                        Forms\Components\TextInput::make('instapay_handle')
                                            ->label(__('admin.instapay_handle'))
                                            ->placeholder('osama3lwany@instapay')
                                            ->helperText(__('admin.instapay_handle_hint')),

                                        Forms\Components\TextInput::make('instapay_link')
                                            ->label(__('admin.instapay_link'))
                                            ->url()
                                            ->placeholder('https://ipn.eg/S/username/instapay/XXXXXX')
                                            ->helperText(__('admin.instapay_link_hint')),

                                        Forms\Components\Textarea::make('instapay_details_ar')
                                            ->label(__('admin.instapay_details_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('instapay_details_en')
                                            ->label(__('admin.instapay_details_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.payment_instructions'))
                                    ->schema([
                                        Forms\Components\Textarea::make('payment_instructions_ar')
                                            ->label(__('admin.payment_instructions_ar'))
                                            ->rows(4),

                                        Forms\Components\Textarea::make('payment_instructions_en')
                                            ->label(__('admin.payment_instructions_en'))
                                            ->rows(4),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.shipping_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.shipping_general'))
                                    ->schema([
                                        Forms\Components\Toggle::make('shipping_enabled')
                                            ->label(__('admin.shipping_enabled'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('global_free_shipping_enabled')
                                            ->label(__('admin.global_free_shipping_enabled'))
                                            ->default(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('global_free_shipping_minimum')
                                            ->label(__('admin.global_free_shipping_minimum'))
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('EGP')
                                            ->visible(fn(Forms\Get $get) => (bool) $get('global_free_shipping_enabled')),

                                        Forms\Components\Toggle::make('show_tracking_to_customer')
                                            ->label(__('admin.show_tracking_to_customer'))
                                            ->default(true),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.delivery_time_settings'))
                                    ->schema([
                                        Forms\Components\TextInput::make('default_preparation_days')
                                            ->label(__('admin.default_preparation_days'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(1)
                                            ->suffix(__('admin.days')),

                                        Forms\Components\TextInput::make('default_delivery_days')
                                            ->label(__('admin.default_delivery_days'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(3)
                                            ->suffix(__('admin.days')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.shipping_policy'))
                                    ->schema([
                                        Forms\Components\Textarea::make('shipping_policy_ar')
                                            ->label(__('admin.shipping_policy_ar'))
                                            ->rows(4),

                                        Forms\Components\Textarea::make('shipping_policy_en')
                                            ->label(__('admin.shipping_policy_en'))
                                            ->rows(4),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.shipping_notes'))
                                    ->schema([
                                        Forms\Components\Textarea::make('shipping_notes_ar')
                                            ->label(__('admin.shipping_notes_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('shipping_notes_en')
                                            ->label(__('admin.shipping_notes_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.invoice_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.invoice_general'))
                                    ->schema([
                                        Forms\Components\Toggle::make('invoices_enabled')
                                            ->label(__('admin.invoices_enabled'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('show_logo_on_invoice')
                                            ->label(__('admin.show_logo_on_invoice'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('show_tax_on_invoice')
                                            ->label(__('admin.show_tax_on_invoice'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('show_payment_status_on_invoice')
                                            ->label(__('admin.show_payment_status_on_invoice'))
                                            ->default(true),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.invoice_numbering'))
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_prefix')
                                            ->label(__('admin.invoice_prefix'))
                                            ->default('INV')
                                            ->maxLength(20),

                                        Forms\Components\TextInput::make('invoice_number_format')
                                            ->label(__('admin.invoice_number_format'))
                                            ->default('{prefix}-{year}{month}{day}-{id}')
                                            ->helperText(__('admin.invoice_number_format_helper'))
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.invoice_seller_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_seller_name_ar')
                                            ->label(__('admin.invoice_seller_name_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('invoice_seller_name_en')
                                            ->label(__('admin.invoice_seller_name_en'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('tax_number')
                                            ->label(__('admin.tax_number'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('commercial_registration_number')
                                            ->label(__('admin.commercial_registration_number'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('invoice_address_ar')
                                            ->label(__('admin.invoice_address_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('invoice_address_en')
                                            ->label(__('admin.invoice_address_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.invoice_notes_and_terms'))
                                    ->schema([
                                        Forms\Components\Textarea::make('invoice_notes_ar')
                                            ->label(__('admin.invoice_notes_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('invoice_notes_en')
                                            ->label(__('admin.invoice_notes_en'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('invoice_terms_ar')
                                            ->label(__('admin.invoice_terms_ar'))
                                            ->rows(4),

                                        Forms\Components\Textarea::make('invoice_terms_en')
                                            ->label(__('admin.invoice_terms_en'))
                                            ->rows(4),
                                    ])
                                    ->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('admin.dashboard_appearance_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.dashboard_branding'))
                                    ->schema([
                                        Forms\Components\FileUpload::make('dashboard_logo')
                                            ->label(__('admin.dashboard_logo'))
                                            ->image()
                                            ->directory('settings/dashboard')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(2048),

                                        Forms\Components\FileUpload::make('dashboard_favicon')
                                            ->label(__('admin.dashboard_favicon'))
                                            ->image()
                                            ->directory('settings/dashboard')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(1024),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.dashboard_colors'))
                                    ->schema([
                                        Forms\Components\ColorPicker::make('dashboard_primary_color')
                                            ->label(__('admin.dashboard_primary_color'))
                                            ->default('#f59e0b'),

                                        Forms\Components\ColorPicker::make('dashboard_sidebar_color')
                                            ->label(__('admin.dashboard_sidebar_color'))
                                            ->default('#111827'),

                                        Forms\Components\ColorPicker::make('dashboard_sidebar_text_color')
                                            ->label(__('admin.dashboard_sidebar_text_color'))
                                            ->default('#ffffff'),

                                        Forms\Components\ColorPicker::make('dashboard_topbar_color')
                                            ->label(__('admin.dashboard_topbar_color'))
                                            ->default('#ffffff'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.dashboard_layout'))
                                    ->schema([
                                        Forms\Components\TextInput::make('dashboard_button_radius')
                                            ->label(__('admin.dashboard_button_radius'))
                                            ->numeric()
                                            ->default(8)
                                            ->suffix('px'),

                                        Forms\Components\TextInput::make('dashboard_card_radius')
                                            ->label(__('admin.dashboard_card_radius'))
                                            ->numeric()
                                            ->default(12)
                                            ->suffix('px'),

                                        Forms\Components\Toggle::make('dashboard_dark_mode_default')
                                            ->label(__('admin.dashboard_dark_mode_default'))
                                            ->default(false),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make(__('admin.login_page_appearance'))
                                    ->schema([
                                        Forms\Components\FileUpload::make('login_background_image')
                                            ->label(__('admin.login_background_image'))
                                            ->image()
                                            ->directory('settings/login')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(4096)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('login_background_opacity')
                                            ->label(__('admin.login_background_opacity'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1)
                                            ->step(0.01)
                                            ->default(0.35)
                                            ->helperText(__('admin.opacity_helper')),

                                        Forms\Components\ColorPicker::make('login_card_background_color')
                                            ->label(__('admin.login_card_background_color'))
                                            ->default('#ffffff'),

                                        Forms\Components\TextInput::make('login_card_opacity')
                                            ->label(__('admin.login_card_opacity'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1)
                                            // 0.01, not 0.05: the browser blocks the whole form from
                                            // submitting when a stored value is not a multiple of the step.
                                            ->step(0.01)
                                            ->default(0.92)
                                            ->helperText(__('admin.opacity_helper')),

                                        Forms\Components\Toggle::make('login_card_blur')
                                            ->label(__('admin.login_card_blur'))
                                            ->default(true),
                                        Forms\Components\TextInput::make('login_logo_width')
                                            ->label(__('admin.login_logo_width'))
                                            ->numeric()
                                            ->minValue(40)
                                            ->maxValue(1000)
                                            ->default(96)
                                            ->suffix('px'),

                                        Forms\Components\TextInput::make('login_logo_height')
                                            ->label(__('admin.login_logo_height'))
                                            ->numeric()
                                            ->minValue(40)
                                            ->maxValue(1000)
                                            ->default(96)
                                            ->suffix('px'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.notification_settings'))
                            ->schema([
                                // First in the tab: it applies to both channels.
                                Forms\Components\Section::make(__('admin.message_branding'))
                                    ->description(__('admin.message_branding_hint'))
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\FileUpload::make('notification_logo')
                                            ->label(__('admin.notification_logo'))
                                            ->helperText(__('admin.notification_logo_hint'))
                                            ->image()
                                            ->directory('settings/notifications')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(1024),

                                        Forms\Components\Toggle::make('whatsapp_send_logo')
                                            ->label(__('admin.whatsapp_send_logo'))
                                            ->helperText(__('admin.whatsapp_send_logo_hint'))
                                            ->default(false),
                                    ]),

                                Forms\Components\Section::make(__('admin.mail_server_settings'))
                                    ->description(__('admin.mail_server_hint'))
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('mail_mailer')
                                            ->label(__('admin.mail_mailer'))
                                            ->options([
                                                'smtp' => 'SMTP',
                                                'log' => __('admin.mail_mailer_log'),
                                            ])
                                            ->default('smtp')
                                            ->live(),

                                        Forms\Components\TextInput::make('mail_host')
                                            ->label(__('admin.mail_host'))
                                            ->maxLength(255)
                                            ->placeholder('smtp.example.com')
                                            ->visible(fn (Forms\Get $get) => $get('mail_mailer') !== 'log'),

                                        Forms\Components\TextInput::make('mail_port')
                                            ->label(__('admin.mail_port'))
                                            ->numeric()
                                            ->placeholder('465')
                                            ->visible(fn (Forms\Get $get) => $get('mail_mailer') !== 'log'),

                                        Forms\Components\Select::make('mail_encryption')
                                            ->label(__('admin.mail_encryption'))
                                            ->options([
                                                'tls' => 'TLS',
                                                'ssl' => 'SSL',
                                                'none' => __('admin.none'),
                                            ])
                                            ->default('ssl')
                                            ->visible(fn (Forms\Get $get) => $get('mail_mailer') !== 'log'),

                                        Forms\Components\TextInput::make('mail_username')
                                            ->label(__('admin.mail_username'))
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('mail_mailer') !== 'log'),

                                        Forms\Components\TextInput::make('mail_password')
                                            ->label(__('admin.mail_password'))
                                            ->password()
                                            ->revealable()
                                            ->maxLength(500)
                                            ->visible(fn (Forms\Get $get) => $get('mail_mailer') !== 'log'),

                                        Forms\Components\TextInput::make('mail_from_address')
                                            ->label(__('admin.mail_from_address'))
                                            ->email()
                                            ->maxLength(255)
                                            ->helperText(__('admin.mail_from_address_hint')),

                                        Forms\Components\TextInput::make('mail_from_name')
                                            ->label(__('admin.mail_from_name'))
                                            ->maxLength(255),
                                    ]),
                                Forms\Components\Section::make(__('admin.email_notification_settings'))
                                    ->schema([
                                        Forms\Components\Toggle::make('email_notifications_enabled')
                                            ->label(__('admin.email_notifications_enabled'))
                                            ->default(true),

                                        Forms\Components\TextInput::make('admin_notification_email')
                                            ->label(__('admin.admin_notification_email'))
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\Toggle::make('notify_admin_new_order')
                                            ->label(__('admin.notify_admin_new_order'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('notify_admin_new_payment')
                                            ->label(__('admin.notify_admin_new_payment'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('notify_customer_new_order')
                                            ->label(__('admin.notify_customer_new_order'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('notify_customer_shipment')
                                            ->label(__('admin.notify_customer_shipment'))
                                            ->default(true),
                                        Forms\Components\Toggle::make('notify_customer_order_status')
                                            ->label(__('admin.notify_customer_order_status'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('notify_customer_invoice_created')
                                            ->label(__('admin.notify_customer_invoice_created'))
                                            ->default(true),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.whatsapp_notification_settings'))
                                    ->description(__('admin.whatsapp_settings_hint'))
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('whatsapp_notifications_enabled')
                                            ->label(__('admin.whatsapp_notifications_enabled'))
                                            ->default(false)
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('whatsapp_api_provider')
                                            ->label(__('admin.whatsapp_api_provider'))
                                            ->options(fn () => \App\Services\Notifications\WhatsappGateway::providerOptions())
                                            ->live()
                                            // Meta refuses free-form text outside a 24h window, which is
                                            // the one thing filling in credentials does not solve.
                                            ->helperText(fn (Forms\Get $get) => $get('whatsapp_api_provider') === 'cloud_api'
                                                ? __('admin.whatsapp_cloud_api_warning')
                                                : null)
                                            ->visible(fn (Forms\Get $get) => (bool) $get('whatsapp_notifications_enabled')),

                                        Forms\Components\TextInput::make('whatsapp_api_url')
                                            ->label(__('admin.whatsapp_api_url'))
                                            ->url()
                                            ->maxLength(255)
                                            ->helperText(__('admin.whatsapp_api_url_hint'))
                                            ->visible(fn (Forms\Get $get) => (bool) $get('whatsapp_notifications_enabled')),

                                        Forms\Components\TextInput::make('whatsapp_sender_id')
                                            ->label(fn (Forms\Get $get) => match ($get('whatsapp_api_provider')) {
                                                'cloud_api' => __('admin.whatsapp_phone_number_id'),
                                                'ultramsg' => __('admin.whatsapp_instance_id'),
                                                'twilio' => __('admin.whatsapp_account_sid'),
                                                default => __('admin.whatsapp_sender_id'),
                                            })
                                            ->maxLength(255)
                                            ->required(fn (Forms\Get $get) => (bool) $get('whatsapp_notifications_enabled'))
                                            ->visible(fn (Forms\Get $get) => (bool) $get('whatsapp_notifications_enabled')
                                                && filled($get('whatsapp_api_provider'))),

                                        Forms\Components\TextInput::make('whatsapp_api_token')
                                            ->label(__('admin.whatsapp_api_token'))
                                            ->password()
                                            ->revealable()
                                            ->maxLength(1000)
                                            ->visible(fn (Forms\Get $get) => (bool) $get('whatsapp_notifications_enabled')
                                                && in_array($get('whatsapp_api_provider'), ['cloud_api', 'ultramsg'], true)),

                                        Forms\Components\TextInput::make('whatsapp_api_secret')
                                            ->label(__('admin.whatsapp_auth_token'))
                                            ->password()
                                            ->revealable()
                                            ->maxLength(1000)
                                            ->visible(fn (Forms\Get $get) => (bool) $get('whatsapp_notifications_enabled')
                                                && $get('whatsapp_api_provider') === 'twilio'),

                                        Forms\Components\TextInput::make('whatsapp_from_number')
                                            ->label(__('admin.whatsapp_from_number'))
                                            ->tel()
                                            ->maxLength(50)
                                            ->visible(fn (Forms\Get $get) => (bool) $get('whatsapp_notifications_enabled')
                                                && $get('whatsapp_api_provider') === 'twilio'),

                                        Forms\Components\Fieldset::make(__('admin.whatsapp_events'))
                                            ->columns(2)
                                            ->visible(fn (Forms\Get $get) => (bool) $get('whatsapp_notifications_enabled'))
                                            ->schema([
                                                Forms\Components\Toggle::make('whatsapp_notify_admin_new_order')
                                                    ->label(__('admin.notify_admin_new_order'))
                                                    ->default(true),

                                                Forms\Components\Toggle::make('whatsapp_notify_admin_new_payment')
                                                    ->label(__('admin.notify_admin_new_payment'))
                                                    ->default(true),

                                                Forms\Components\Toggle::make('whatsapp_notify_customer_new_order')
                                                    ->label(__('admin.notify_customer_new_order'))
                                                    ->default(true),

                                                Forms\Components\Toggle::make('whatsapp_notify_customer_order_status')
                                                    ->label(__('admin.notify_customer_order_status'))
                                                    ->default(true),

                                                Forms\Components\Toggle::make('whatsapp_notify_customer_shipment')
                                                    ->label(__('admin.notify_customer_shipment'))
                                                    ->default(true),

                                                Forms\Components\Toggle::make('whatsapp_notify_customer_invoice')
                                                    ->label(__('admin.notify_customer_invoice_created'))
                                                    ->default(false),
                                            ]),
                                    ]),
                                Forms\Components\Section::make(__('admin.notification_messages'))
                                    ->description(__('admin.message_templates_moved'))
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('edit_message_templates')
                                                ->label(__('admin.edit_message_templates'))
                                                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                                ->color('primary')
                                                ->url(fn () => NotificationTemplateResource::getUrl('index')),
                                        ]),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('admin.security_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.admin_security'))
                                    ->schema([
                                        Forms\Components\Toggle::make('login_activity_logging_enabled')
                                            ->label(__('admin.login_activity_logging_enabled'))
                                            ->default(true),

                                        Forms\Components\TextInput::make('admin_session_timeout_minutes')
                                            ->label(__('admin.admin_session_timeout_minutes'))
                                            ->numeric()
                                            ->minValue(5)
                                            ->default(120)
                                            ->suffix(__('admin.minutes')),

                                        Forms\Components\Toggle::make('force_admin_password_change')
                                            ->label(__('admin.force_admin_password_change'))
                                            ->default(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('password_change_days')
                                            ->label(__('admin.password_change_days'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->nullable()
                                            ->suffix(__('admin.days'))
                                            ->visible(fn(Forms\Get $get) => (bool) $get('force_admin_password_change')),

                                        Forms\Components\Toggle::make('notify_admin_new_device_login')
                                            ->label(__('admin.notify_admin_new_device_login'))
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.customer_security'))
                                    ->schema([
                                        Forms\Components\Toggle::make('customer_registration_enabled')
                                            ->label(__('admin.customer_registration_enabled'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('customer_login_enabled')
                                            ->label(__('admin.customer_login_enabled'))
                                            ->default(true),

                                        Forms\Components\TextInput::make('max_login_attempts')
                                            ->label(__('admin.max_login_attempts'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(5),

                                        Forms\Components\TextInput::make('login_lockout_minutes')
                                            ->label(__('admin.login_lockout_minutes'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(15)
                                            ->suffix(__('admin.minutes')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.order_security'))
                                    ->schema([
                                        Forms\Components\Toggle::make('prevent_order_edit_after_delivery')
                                            ->label(__('admin.prevent_order_edit_after_delivery'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('prevent_order_edit_after_cancellation')
                                            ->label(__('admin.prevent_order_edit_after_cancellation'))
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label(__('admin.logo'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('store_name_ar')
                    ->label(__('admin.store_name_ar'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('store_name_en')
                    ->label(__('admin.store_name_en'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('currency_code')
                    ->label(__('admin.currency_code'))
                    ->badge(),

                Tables\Columns\IconColumn::make('is_store_active')
                    ->label(__('admin.store_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('admin.edit')),

                Tables\Actions\ViewAction::make()
                    ->label(__('admin.view')),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.store_identity'))
                    ->schema([
                        Infolists\Components\ImageEntry::make('logo')
                            ->label(__('admin.logo'))
                            ->disk('public')
                            ->square()
                            ->placeholder('-'),

                        Infolists\Components\ImageEntry::make('favicon')
                            ->label(__('admin.favicon'))
                            ->disk('public')
                            ->square()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('store_name_ar')
                            ->label(__('admin.store_name_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('store_name_en')
                            ->label(__('admin.store_name_en'))
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('is_store_active')
                            ->label(__('admin.store_active'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('default_locale')
                            ->label(__('admin.default_locale'))
                            ->badge(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.contact_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('email')
                            ->label(__('admin.email'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('admin.phone'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('whatsapp')
                            ->label(__('admin.whatsapp'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('address_ar')
                            ->label(__('admin.address_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('address_en')
                            ->label(__('admin.address_en'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.currency_settings'))
                    ->schema([
                        Infolists\Components\TextEntry::make('currency_code')
                            ->label(__('admin.currency_code'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('currency_symbol')
                            ->label(__('admin.currency_symbol'))
                            ->badge(),
                    ])
                    ->columns(2),

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

                        Infolists\Components\TextEntry::make('meta_keywords_ar')
                            ->label(__('admin.meta_keywords_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('meta_keywords_en')
                            ->label(__('admin.meta_keywords_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\ImageEntry::make('og_image')
                            ->label(__('admin.og_image'))
                            ->disk('public')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('robots')
                            ->label(__('admin.robots'))
                            ->badge()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('canonical_url')
                            ->label(__('admin.canonical_url'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('google_site_verification')
                            ->label(__('admin.google_site_verification'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('bing_site_verification')
                            ->label(__('admin.bing_site_verification'))
                            ->copyable()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.tracking_integrations'))
                    ->schema([
                        Infolists\Components\IconEntry::make('tracking_enabled')
                            ->label(__('admin.tracking_enabled'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('cookie_consent_enabled')
                            ->label(__('admin.cookie_consent_enabled'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('meta_pixel_id')
                            ->label(__('admin.meta_pixel_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('meta_test_event_code')
                            ->label(__('admin.meta_test_event_code'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('google_tag_manager_id')
                            ->label(__('admin.google_tag_manager_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('ga4_measurement_id')
                            ->label(__('admin.ga4_measurement_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('google_ads_conversion_id')
                            ->label(__('admin.google_ads_conversion_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('google_ads_conversion_label')
                            ->label(__('admin.google_ads_conversion_label'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('tiktok_pixel_id')
                            ->label(__('admin.tiktok_pixel_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('snapchat_pixel_id')
                            ->label(__('admin.snapchat_pixel_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('linkedin_partner_id')
                            ->label(__('admin.linkedin_partner_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('pinterest_tag_id')
                            ->label(__('admin.pinterest_tag_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('twitter_pixel_id')
                            ->label(__('admin.twitter_pixel_id'))
                            ->copyable()
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.payment_settings'))
                    ->schema([
                        Infolists\Components\IconEntry::make('cash_on_delivery_enabled')
                            ->label(__('admin.cash_on_delivery_enabled'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('bank_transfer_enabled')
                            ->label(__('admin.bank_transfer_enabled'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('wallet_transfer_enabled')
                            ->label(__('admin.wallet_transfer_enabled'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('payment_proof_required')
                            ->label(__('admin.payment_proof_required'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('cash_on_delivery_fee')
                            ->label(__('admin.cash_on_delivery_fee'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('bank_account_details_ar')
                            ->label(__('admin.bank_account_details_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('bank_account_details_en')
                            ->label(__('admin.bank_account_details_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('wallet_details_ar')
                            ->label(__('admin.wallet_details_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('wallet_details_en')
                            ->label(__('admin.wallet_details_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Infolists\Components\Section::make(__('admin.security_settings'))
                    ->schema([
                        Infolists\Components\IconEntry::make('login_activity_logging_enabled')
                            ->label(__('admin.login_activity_logging_enabled'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('admin_session_timeout_minutes')
                            ->label(__('admin.admin_session_timeout_minutes'))
                            ->suffix(' ' . __('admin.minutes')),

                        Infolists\Components\IconEntry::make('force_admin_password_change')
                            ->label(__('admin.force_admin_password_change'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('password_change_days')
                            ->label(__('admin.password_change_days'))
                            ->suffix(' ' . __('admin.days'))
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('notify_admin_new_device_login')
                            ->label(__('admin.notify_admin_new_device_login'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('customer_registration_enabled')
                            ->label(__('admin.customer_registration_enabled'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('customer_login_enabled')
                            ->label(__('admin.customer_login_enabled'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('max_login_attempts')
                            ->label(__('admin.max_login_attempts')),

                        Infolists\Components\TextEntry::make('login_lockout_minutes')
                            ->label(__('admin.login_lockout_minutes'))
                            ->suffix(' ' . __('admin.minutes')),

                        Infolists\Components\IconEntry::make('prevent_order_edit_after_delivery')
                            ->label(__('admin.prevent_order_edit_after_delivery'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('prevent_order_edit_after_cancellation')
                            ->label(__('admin.prevent_order_edit_after_cancellation'))
                            ->boolean(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.shipping_settings'))
                    ->schema([
                        Infolists\Components\IconEntry::make('shipping_enabled')
                            ->label(__('admin.shipping_enabled'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('global_free_shipping_enabled')
                            ->label(__('admin.global_free_shipping_enabled'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('global_free_shipping_minimum')
                            ->label(__('admin.global_free_shipping_minimum'))
                            ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-'),

                        Infolists\Components\IconEntry::make('show_tracking_to_customer')
                            ->label(__('admin.show_tracking_to_customer'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('default_preparation_days')
                            ->label(__('admin.default_preparation_days'))
                            ->suffix(' ' . __('admin.days')),

                        Infolists\Components\TextEntry::make('default_delivery_days')
                            ->label(__('admin.default_delivery_days'))
                            ->suffix(' ' . __('admin.days')),

                        Infolists\Components\TextEntry::make('shipping_policy_ar')
                            ->label(__('admin.shipping_policy_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('shipping_policy_en')
                            ->label(__('admin.shipping_policy_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('shipping_notes_ar')
                            ->label(__('admin.shipping_notes_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('shipping_notes_en')
                            ->label(__('admin.shipping_notes_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.invoice_settings'))
                    ->schema([
                        Infolists\Components\IconEntry::make('invoices_enabled')
                            ->label(__('admin.invoices_enabled'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('show_logo_on_invoice')
                            ->label(__('admin.show_logo_on_invoice'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('show_tax_on_invoice')
                            ->label(__('admin.show_tax_on_invoice'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('show_payment_status_on_invoice')
                            ->label(__('admin.show_payment_status_on_invoice'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('invoice_prefix')
                            ->label(__('admin.invoice_prefix'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('invoice_number_format')
                            ->label(__('admin.invoice_number_format'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('invoice_seller_name_ar')
                            ->label(__('admin.invoice_seller_name_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('invoice_seller_name_en')
                            ->label(__('admin.invoice_seller_name_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('tax_number')
                            ->label(__('admin.tax_number'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('commercial_registration_number')
                            ->label(__('admin.commercial_registration_number'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('invoice_address_ar')
                            ->label(__('admin.invoice_address_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('invoice_address_en')
                            ->label(__('admin.invoice_address_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Infolists\Components\Section::make(__('admin.dashboard_appearance_settings'))
                    ->schema([
                        Infolists\Components\ImageEntry::make('dashboard_logo')
                            ->label(__('admin.dashboard_logo'))
                            ->disk('public')
                            ->placeholder('-'),

                        Infolists\Components\ImageEntry::make('dashboard_favicon')
                            ->label(__('admin.dashboard_favicon'))
                            ->disk('public')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('dashboard_primary_color')
                            ->label(__('admin.dashboard_primary_color'))
                            ->badge()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('dashboard_sidebar_color')
                            ->label(__('admin.dashboard_sidebar_color'))
                            ->badge()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('dashboard_sidebar_text_color')
                            ->label(__('admin.dashboard_sidebar_text_color'))
                            ->badge()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('dashboard_topbar_color')
                            ->label(__('admin.dashboard_topbar_color'))
                            ->badge()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('dashboard_button_radius')
                            ->label(__('admin.dashboard_button_radius'))
                            ->suffix('px'),

                        Infolists\Components\TextEntry::make('dashboard_card_radius')
                            ->label(__('admin.dashboard_card_radius'))
                            ->suffix('px'),

                        Infolists\Components\IconEntry::make('dashboard_dark_mode_default')
                            ->label(__('admin.dashboard_dark_mode_default'))
                            ->boolean(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreSettings::route('/'),
            'view' => Pages\ViewStoreSetting::route('/{record}'),
            'edit' => Pages\EditStoreSetting::route('/{record}/edit'),
        ];
    }
}