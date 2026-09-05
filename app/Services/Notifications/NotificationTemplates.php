<?php

namespace App\Services\Notifications;

/**
 * The catalogue of notification messages: which events exist, which
 * placeholders each one understands, and the wording used when the shop has
 * not written its own.
 *
 * Everything here is static data. The editable copies live in the
 * notification_templates table; these are the fallbacks.
 */
class NotificationTemplates
{
    public const CHANNELS = ['email', 'whatsapp'];

    /** Placeholders every message can use. */
    public const STORE_PLACEHOLDERS = [
        'store_name', 'store_phone', 'store_whatsapp', 'store_email',
    ];

    /** Placeholders available on any order-driven message. */
    public const ORDER_PLACEHOLDERS = [
        'order_number', 'order_url', 'order_status', 'order_date',
        'customer_name', 'customer_phone', 'customer_email',
        'items', 'items_count',
        'subtotal', 'discount', 'shipping', 'total',
        'payment_method', 'payment_status', 'coupon_code',
        'city', 'address', 'notes',
    ];

    /**
     * Every event the store can notify about.
     *
     * @return array<string, array{audience: string, extra: array<int, string>}>
     */
    public static function events(): array
    {
        return [
            'admin_new_order' => ['audience' => 'admin', 'extra' => []],
            'admin_new_payment' => ['audience' => 'admin', 'extra' => []],
            'customer_new_order' => ['audience' => 'customer', 'extra' => []],
            'customer_order_status' => ['audience' => 'customer', 'extra' => []],
            'customer_invoice' => ['audience' => 'customer', 'extra' => [
                'invoice_number', 'invoice_total', 'invoice_date',
            ]],
            'customer_shipment' => ['audience' => 'customer', 'extra' => [
                'carrier', 'tracking_number', 'tracking_url',
            ]],
        ];
    }

    public static function eventKeys(): array
    {
        return array_keys(self::events());
    }

    public static function isEvent(string $event): bool
    {
        return array_key_exists($event, self::events());
    }

    /** Every placeholder valid for one event, in the order they are shown. */
    public static function placeholders(string $event): array
    {
        $extra = self::events()[$event]['extra'] ?? [];

        return array_merge(self::ORDER_PLACEHOLDERS, $extra, self::STORE_PLACEHOLDERS);
    }

    public static function audience(string $event): string
    {
        return self::events()[$event]['audience'] ?? 'customer';
    }

    /**
     * The built-in wording for one (event, channel).
     *
     * @return array{subject_ar: ?string, subject_en: ?string, body_ar: string, body_en: string, button_label_ar: ?string, button_label_en: ?string}
     */
    public static function default(string $event, string $channel): array
    {
        $defaults = $channel === 'whatsapp' ? self::whatsappDefaults() : self::emailDefaults();

        return $defaults[$event] ?? [
            'subject_ar' => null,
            'subject_en' => null,
            'body_ar' => '',
            'body_en' => '',
            'button_label_ar' => null,
            'button_label_en' => null,
        ];
    }

    /** All twelve defaults, ready to seed. */
    public static function all(): array
    {
        $rows = [];

        foreach (self::eventKeys() as $event) {
            foreach (self::CHANNELS as $channel) {
                $rows[] = array_merge(
                    ['event' => $event, 'channel' => $channel],
                    self::default($event, $channel)
                );
            }
        }

        return $rows;
    }

    // ------------------------------------------------------------- email

    private static function emailDefaults(): array
    {
        return [
            'admin_new_order' => [
                'subject_ar' => 'طلب جديد رقم {order_number}',
                'subject_en' => 'New order {order_number}',
                'body_ar' => "تم إنشاء طلب جديد على المتجر.\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "العميلة: {customer_name}\n"
                    . "الهاتف: {customer_phone}\n"
                    . "المدينة: {city}\n"
                    . "طريقة الدفع: {payment_method}\n\n"
                    . "المنتجات:\n{items}\n\n"
                    . "الإجمالي الفرعي: {subtotal}\n"
                    . "الخصم: {discount}\n"
                    . "الشحن: {shipping}\n"
                    . 'الإجمالي: {total}',
                'body_en' => "A new order has been placed.\n\n"
                    . "Order: {order_number}\n"
                    . "Customer: {customer_name}\n"
                    . "Phone: {customer_phone}\n"
                    . "City: {city}\n"
                    . "Payment: {payment_method}\n\n"
                    . "Items:\n{items}\n\n"
                    . "Subtotal: {subtotal}\n"
                    . "Discount: {discount}\n"
                    . "Shipping: {shipping}\n"
                    . 'Total: {total}',
                'button_label_ar' => 'عرض الطلب',
                'button_label_en' => 'View order',
            ],

            'admin_new_payment' => [
                'subject_ar' => 'دفعة جديدة على الطلب {order_number}',
                'subject_en' => 'Payment received for {order_number}',
                'body_ar' => "تم تسجيل دفعة على الطلب.\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "العميلة: {customer_name}\n"
                    . "طريقة الدفع: {payment_method}\n"
                    . "حالة الدفع: {payment_status}\n"
                    . 'الإجمالي: {total}',
                'body_en' => "A payment has been recorded.\n\n"
                    . "Order: {order_number}\n"
                    . "Customer: {customer_name}\n"
                    . "Payment: {payment_method}\n"
                    . "Payment status: {payment_status}\n"
                    . 'Total: {total}',
                'button_label_ar' => 'عرض الطلب',
                'button_label_en' => 'View order',
            ],

            'customer_new_order' => [
                'subject_ar' => 'تم استلام طلبك رقم {order_number}',
                'subject_en' => 'We received your order {order_number}',
                'body_ar' => "مرحبًا {customer_name}،\n\n"
                    . "تم استلام طلبك بنجاح ✅\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "المنتجات:\n{items}\n\n"
                    . "الإجمالي: {total}\n"
                    . "طريقة الدفع: {payment_method}\n\n"
                    . 'هنتواصل معك لتأكيد الطلب قريبًا.',
                'body_en' => "Hello {customer_name},\n\n"
                    . "Your order has been received ✅\n\n"
                    . "Order: {order_number}\n"
                    . "Items:\n{items}\n\n"
                    . "Total: {total}\n"
                    . "Payment: {payment_method}\n\n"
                    . 'We will contact you shortly to confirm it.',
                'button_label_ar' => 'متابعة الطلب',
                'button_label_en' => 'Track order',
            ],

            'customer_order_status' => [
                'subject_ar' => 'تحديث على طلبك رقم {order_number}',
                'subject_en' => 'Update on your order {order_number}',
                'body_ar' => "مرحبًا {customer_name}،\n\n"
                    . "تم تحديث حالة طلبك رقم {order_number} إلى: {order_status}\n\n"
                    . 'شكرًا لتسوقك من {store_name} 💛',
                'body_en' => "Hello {customer_name},\n\n"
                    . "Your order {order_number} is now: {order_status}\n\n"
                    . 'Thank you for shopping with {store_name} 💛',
                'button_label_ar' => 'متابعة الطلب',
                'button_label_en' => 'Track order',
            ],

            'customer_invoice' => [
                'subject_ar' => 'فاتورتك رقم {invoice_number}',
                'subject_en' => 'Your invoice {invoice_number}',
                'body_ar' => "مرحبًا {customer_name}،\n\n"
                    . "تم إصدار فاتورتك 🧾\n\n"
                    . "رقم الفاتورة: {invoice_number}\n"
                    . "رقم الطلب: {order_number}\n"
                    . "التاريخ: {invoice_date}\n"
                    . 'الإجمالي: {invoice_total}',
                'body_en' => "Hello {customer_name},\n\n"
                    . "Your invoice is ready 🧾\n\n"
                    . "Invoice: {invoice_number}\n"
                    . "Order: {order_number}\n"
                    . "Date: {invoice_date}\n"
                    . 'Total: {invoice_total}',
                'button_label_ar' => 'عرض الطلب',
                'button_label_en' => 'View order',
            ],

            'customer_shipment' => [
                'subject_ar' => 'طلبك رقم {order_number} في الطريق إليك',
                'subject_en' => 'Your order {order_number} is on its way',
                'body_ar' => "مرحبًا {customer_name}،\n\n"
                    . "تم شحن طلبك 🚚\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "شركة الشحن: {carrier}\n"
                    . "رقم التتبع: {tracking_number}\n\n"
                    . 'من فضلك جهّزي مبلغ {total} عند الاستلام.',
                'body_en' => "Hello {customer_name},\n\n"
                    . "Your order has been shipped 🚚\n\n"
                    . "Order: {order_number}\n"
                    . "Carrier: {carrier}\n"
                    . "Tracking: {tracking_number}\n\n"
                    . 'Please have {total} ready on delivery.',
                'button_label_ar' => 'تتبع الشحنة',
                'button_label_en' => 'Track shipment',
            ],
        ];
    }

    // ---------------------------------------------------------- whatsapp

    private static function whatsappDefaults(): array
    {
        return [
            'admin_new_order' => [
                'subject_ar' => null,
                'subject_en' => null,
                'body_ar' => "طلب جديد ✅\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "العميلة: {customer_name}\n"
                    . "الهاتف: {customer_phone}\n"
                    . "المدينة: {city}\n"
                    . "الإجمالي: {total}\n\n"
                    . "رابط الطلب:\n{order_url}",
                'body_en' => "New order ✅\n\n"
                    . "Order: {order_number}\n"
                    . "Customer: {customer_name}\n"
                    . "Phone: {customer_phone}\n"
                    . "City: {city}\n"
                    . "Total: {total}\n\n"
                    . "Order link:\n{order_url}",
                'button_label_ar' => null,
                'button_label_en' => null,
            ],

            'admin_new_payment' => [
                'subject_ar' => null,
                'subject_en' => null,
                'body_ar' => "دفعة جديدة 💳\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "العميلة: {customer_name}\n"
                    . "الإجمالي: {total}\n\n"
                    . "رابط الطلب:\n{order_url}",
                'body_en' => "New payment 💳\n\n"
                    . "Order: {order_number}\n"
                    . "Customer: {customer_name}\n"
                    . "Total: {total}\n\n"
                    . "Order link:\n{order_url}",
                'button_label_ar' => null,
                'button_label_en' => null,
            ],

            'customer_new_order' => [
                'subject_ar' => null,
                'subject_en' => null,
                'body_ar' => "مرحبًا {customer_name} 👋\n\n"
                    . "تم استلام طلبك بنجاح ✅\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "الإجمالي: {total}\n\n"
                    . "تابعي طلبك من هنا:\n{order_url}",
                'body_en' => "Hello {customer_name} 👋\n\n"
                    . "Your order has been received ✅\n\n"
                    . "Order: {order_number}\n"
                    . "Total: {total}\n\n"
                    . "Track it here:\n{order_url}",
                'button_label_ar' => null,
                'button_label_en' => null,
            ],

            'customer_order_status' => [
                'subject_ar' => null,
                'subject_en' => null,
                'body_ar' => "تحديث حالة طلبك 🔔\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "الحالة: {order_status}\n\n"
                    . "تابعي الطلب:\n{order_url}",
                'body_en' => "Order update 🔔\n\n"
                    . "Order: {order_number}\n"
                    . "Status: {order_status}\n\n"
                    . "Track it here:\n{order_url}",
                'button_label_ar' => null,
                'button_label_en' => null,
            ],

            'customer_invoice' => [
                'subject_ar' => null,
                'subject_en' => null,
                'body_ar' => "فاتورتك جاهزة 🧾\n\n"
                    . "رقم الفاتورة: {invoice_number}\n"
                    . "رقم الطلب: {order_number}\n"
                    . "الإجمالي: {invoice_total}\n\n"
                    . '{order_url}',
                'body_en' => "Your invoice is ready 🧾\n\n"
                    . "Invoice: {invoice_number}\n"
                    . "Order: {order_number}\n"
                    . "Total: {invoice_total}\n\n"
                    . '{order_url}',
                'button_label_ar' => null,
                'button_label_en' => null,
            ],

            'customer_shipment' => [
                'subject_ar' => null,
                'subject_en' => null,
                'body_ar' => "طلبك في الطريق 🚚\n\n"
                    . "رقم الطلب: {order_number}\n"
                    . "شركة الشحن: {carrier}\n"
                    . "رقم التتبع: {tracking_number}\n\n"
                    . "تتبعي الشحنة:\n{tracking_url}",
                'body_en' => "Your order is on its way 🚚\n\n"
                    . "Order: {order_number}\n"
                    . "Carrier: {carrier}\n"
                    . "Tracking: {tracking_number}\n\n"
                    . "Track it here:\n{tracking_url}",
                'button_label_ar' => null,
                'button_label_en' => null,
            ],
        ];
    }
}
