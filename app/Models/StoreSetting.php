<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name_ar',
        'store_name_en',
        'logo',
        'notification_logo',
        'favicon',
        'email',
        'phone',
        'whatsapp',
        'address_ar',
        'address_en',
        'currency_code',
        'currency_symbol',
        'default_locale',
        'is_store_active',
        'maintenance_message_ar',
        'maintenance_message_en',
        'meta_title_ar',
        'meta_title_en',
        'meta_description_ar',
        'meta_description_en',
        'meta_keywords_ar',
        'meta_keywords_en',
        'og_image',
        'robots',
        'canonical_url',
        'google_site_verification',
        'bing_site_verification',
        'tracking_enabled',
        'meta_pixel_id',
        'meta_capi_access_token',
        'meta_test_event_code',
        'google_tag_manager_id',
        'ga4_measurement_id',
        'google_ads_conversion_id',
        'google_ads_conversion_label',
        'tiktok_pixel_id',
        'snapchat_pixel_id',
        'linkedin_partner_id',
        'pinterest_tag_id',
        'twitter_pixel_id',
        'cookie_consent_enabled',
        'cookie_consent_message_ar',
        'cookie_consent_message_en',
        'cash_on_delivery_enabled',
        'bank_transfer_enabled',
        'wallet_transfer_enabled',
        'cash_on_delivery_fee',
        'bank_account_details_ar',
        'bank_account_details_en',
        'wallet_details_ar',
        'wallet_details_en',
        'payment_instructions_ar',
        'payment_instructions_en',
        'payment_proof_required',
        'shipping_enabled',
        'global_free_shipping_enabled',
        'global_free_shipping_minimum',
        'default_preparation_days',
        'default_delivery_days',
        'show_tracking_to_customer',
        'shipping_policy_ar',
        'shipping_policy_en',
        'shipping_notes_ar',
        'shipping_notes_en',
        'invoices_enabled',
        'invoice_prefix',
        'invoice_number_format',
        'invoice_seller_name_ar',
        'invoice_seller_name_en',
        'tax_number',
        'commercial_registration_number',
        'invoice_address_ar',
        'invoice_address_en',
        'invoice_notes_ar',
        'invoice_notes_en',
        'invoice_terms_ar',
        'invoice_terms_en',
        'show_logo_on_invoice',
        'show_tax_on_invoice',
        'show_payment_status_on_invoice',
        'email_notifications_enabled',
        'admin_notification_email',
        'notify_admin_new_order',
        'notify_admin_new_payment',
        'notify_customer_order_status',
        'notify_customer_invoice_created',
        'whatsapp_notifications_enabled',
        'whatsapp_api_provider',
        'whatsapp_api_token',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'whatsapp_api_url',
        'whatsapp_sender_id',
        'whatsapp_api_secret',
        'whatsapp_from_number',
        'whatsapp_send_logo',
        'whatsapp_notify_admin_new_order',
        'whatsapp_notify_admin_new_payment',
        'whatsapp_notify_customer_new_order',
        'whatsapp_notify_customer_order_status',
        'whatsapp_notify_customer_invoice',
        'whatsapp_notify_customer_shipment',
        'notify_customer_new_order',
        'notify_customer_shipment',
        'new_order_email_subject_ar',
        'new_order_email_subject_en',
        'order_status_message_ar',
        'order_status_message_en',
        'invoice_created_message_ar',
        'invoice_created_message_en',
        'login_activity_logging_enabled',
        'admin_session_timeout_minutes',
        'force_admin_password_change',
        'password_change_days',
        'notify_admin_new_device_login',
        'customer_registration_enabled',
        'customer_login_enabled',
        'prevent_order_edit_after_delivery',
        'prevent_order_edit_after_cancellation',
        'max_login_attempts',
        'login_lockout_minutes',
        'dashboard_primary_color',
        'dashboard_sidebar_color',
        'dashboard_sidebar_text_color',
        'dashboard_topbar_color',
        'dashboard_button_radius',
        'dashboard_card_radius',
        'dashboard_logo',
        'dashboard_favicon',
        'dashboard_dark_mode_default',
        'login_background_image',
        'login_background_opacity',
        'login_card_background_color',
        'login_card_opacity',
        'login_card_blur',
        'login_logo_width',
        'login_logo_height',
        'announcement_bar_enabled',
        'announcement_bar_text_ar',
        'announcement_bar_text_en',
        'announcement_bar_url',
        'announcement_bar_open_in_new_tab',
        'announcement_bar_bg_color',
        'announcement_bar_text_color',
        'announcement_bar_speed',
    ];

    protected $casts = [
        'is_store_active' => 'boolean',
        'tracking_enabled' => 'boolean',
        'cookie_consent_enabled' => 'boolean',
        'cash_on_delivery_enabled' => 'boolean',
        'bank_transfer_enabled' => 'boolean',
        'wallet_transfer_enabled' => 'boolean',
        'cash_on_delivery_fee' => 'decimal:2',
        'payment_proof_required' => 'boolean',
        'shipping_enabled' => 'boolean',
        'global_free_shipping_enabled' => 'boolean',
        'global_free_shipping_minimum' => 'decimal:2',
        'default_preparation_days' => 'integer',
        'default_delivery_days' => 'integer',
        'show_tracking_to_customer' => 'boolean',
        'invoices_enabled' => 'boolean',
        'show_logo_on_invoice' => 'boolean',
        'show_tax_on_invoice' => 'boolean',
        'show_payment_status_on_invoice' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'notify_admin_new_order' => 'boolean',
        'notify_admin_new_payment' => 'boolean',
        'notify_customer_order_status' => 'boolean',
        'notify_customer_invoice_created' => 'boolean',
        'whatsapp_notifications_enabled' => 'boolean',
        'mail_port' => 'integer',
        'mail_password' => 'encrypted',
        'whatsapp_api_token' => 'encrypted',
        'whatsapp_api_secret' => 'encrypted',
        'whatsapp_notify_admin_new_order' => 'boolean',
        'whatsapp_notify_admin_new_payment' => 'boolean',
        'whatsapp_notify_customer_new_order' => 'boolean',
        'whatsapp_notify_customer_order_status' => 'boolean',
        'whatsapp_notify_customer_invoice' => 'boolean',
        'whatsapp_notify_customer_shipment' => 'boolean',
        'notify_customer_new_order' => 'boolean',
        'notify_customer_shipment' => 'boolean',
        'login_activity_logging_enabled' => 'boolean',
        'admin_session_timeout_minutes' => 'integer',
        'force_admin_password_change' => 'boolean',
        'password_change_days' => 'integer',
        'notify_admin_new_device_login' => 'boolean',
        'customer_registration_enabled' => 'boolean',
        'customer_login_enabled' => 'boolean',
        'prevent_order_edit_after_delivery' => 'boolean',
        'prevent_order_edit_after_cancellation' => 'boolean',
        'max_login_attempts' => 'integer',
        'login_lockout_minutes' => 'integer',
        'dashboard_button_radius' => 'integer',
        'dashboard_card_radius' => 'integer',
        'dashboard_dark_mode_default' => 'boolean',
        'login_background_opacity' => 'decimal:2',
        'login_card_opacity' => 'decimal:2',
        'login_card_blur' => 'boolean',
        'login_logo_width' => 'integer',
        'login_logo_height' => 'integer',
        'announcement_bar_enabled' => 'boolean',
'announcement_bar_open_in_new_tab' => 'boolean',
'announcement_bar_speed' => 'integer',
    ];

    public function getStoreNameAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->store_name_ar
            : $this->store_name_en;
    }

    public function getAddressAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->address_ar
            : $this->address_en;
    }

    public function getMaintenanceMessageAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->maintenance_message_ar
            : $this->maintenance_message_en;
    }

    /**
     * Memoised for the lifetime of the request. There is only ever one row and
     * it is read from layouts, tracking partials and the admin theme, so
     * without this it was fetched dozens of times per page.
     */
    private static ?self $currentInstance = null;

    /** Forget the memoised row (called from the model's saved/deleted events). */
    public static function flushCurrent(): void
    {
        static::$currentInstance = null;
    }

    public static function current(): self
    {
        if (static::$currentInstance !== null) {
            return static::$currentInstance;
        }

        return static::$currentInstance = static::query()->firstOrCreate([
            'id' => 1,
        ], [
            'store_name_ar' => 'متجري',
            'store_name_en' => 'My Store',
            'currency_code' => 'EGP',
            'currency_symbol' => 'EGP',
            'default_locale' => 'ar',
            'is_store_active' => true,
        ]);
    }
    public function getMetaTitleAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->meta_title_ar
            : $this->meta_title_en;
    }

    public function getMetaDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->meta_description_ar
            : $this->meta_description_en;
    }

    public function getMetaKeywordsAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->meta_keywords_ar
            : $this->meta_keywords_en;
    }
    public function getCookieConsentMessageAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->cookie_consent_message_ar
            : $this->cookie_consent_message_en;
    }
    public function getBankAccountDetailsAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->bank_account_details_ar
            : $this->bank_account_details_en;
    }

    public function getWalletDetailsAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->wallet_details_ar
            : $this->wallet_details_en;
    }

    public function getPaymentInstructionsAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->payment_instructions_ar
            : $this->payment_instructions_en;
    }
    public function getShippingPolicyAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->shipping_policy_ar
            : $this->shipping_policy_en;
    }

    public function getShippingNotesAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->shipping_notes_ar
            : $this->shipping_notes_en;
    }
    public function getInvoiceSellerNameAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->invoice_seller_name_ar
            : $this->invoice_seller_name_en;
    }

    public function getInvoiceAddressAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->invoice_address_ar
            : $this->invoice_address_en;
    }

    public function getInvoiceNotesAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->invoice_notes_ar
            : $this->invoice_notes_en;
    }

    public function getInvoiceTermsAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->invoice_terms_ar
            : $this->invoice_terms_en;
    }
    /**
     * The logo that rides along with an email or WhatsApp message.
     *
     * Falls back to the storefront logo, so a shop that never sets one still
     * gets branded messages.
     */
    public function notificationLogoPath(): ?string
    {
        return $this->notification_logo ?: ($this->logo ?: $this->dashboard_logo);
    }

    public function notificationLogoUrl(): ?string
    {
        $path = $this->notificationLogoPath();

        return $path ? asset('storage/' . $path) : null;
    }

    /**
     * True when the logo can actually be fetched by an outside service.
     *
     * WhatsApp providers download the image themselves, so a URL on a local
     * development host would simply fail on their side.
     */
    public function notificationLogoIsPubliclyReachable(): bool
    {
        $url = $this->notificationLogoUrl();

        if (! $url) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: '';

        foreach (['localhost', '127.0.0.1', '::1'] as $local) {
            if ($host === $local) {
                return false;
            }
        }

        return ! preg_match('/\.(test|local|localhost|invalid|example)$/i', $host);
    }

    public function getNewOrderEmailSubjectAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->new_order_email_subject_ar
            : $this->new_order_email_subject_en;
    }

    public function getOrderStatusMessageAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->order_status_message_ar
            : $this->order_status_message_en;
    }

    public function getInvoiceCreatedMessageAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->invoice_created_message_ar
            : $this->invoice_created_message_en;
    }
    public function getAnnouncementBarTextAttribute(): ?string
{
    return app()->getLocale() === 'ar'
        ? $this->announcement_bar_text_ar
        : $this->announcement_bar_text_en;
}
}