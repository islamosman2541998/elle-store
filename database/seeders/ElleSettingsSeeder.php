<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\FooterLink;
use App\Models\FooterSetting;
use App\Models\HomepageSetting;
use App\Models\HomepageSlider;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\PaymentMethodDisplay;
use App\Models\ShippingCity;
use App\Models\ShippingZone;
use App\Models\SocialLink;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class ElleSettingsSeeder extends Seeder
{
    private const PHONE = '01022434418';
    private const WHATSAPP = '201022434418';
    private const FACEBOOK = 'https://www.facebook.com/share/197RHJNkLQ/';
    private const INSTAGRAM = 'https://www.instagram.com/elle40311';

    public function run(): void
    {
        $this->storeSettings();
        $this->socialLinks();
        $pages = $this->pages();
        $this->footer($pages);
        $this->navigation($pages);
        $this->homepage();
        $this->paymentMethods();
        $this->shipping();
    }

    private function storeSettings(): void
    {
        StoreSetting::query()->updateOrCreate(['id' => 1], [
            'store_name_ar' => 'ELLE',
            'store_name_en' => 'ELLE',
            'logo' => 'settings/elle-logo.png',
            'favicon' => 'settings/elle-logo.png',
            // Left blank on purpose: this address is printed in outgoing
            // customer emails, so it must be a real mailbox. Fill it in from
            // the dashboard once an ELLE address exists.
            'email' => null,
            'phone' => self::PHONE,
            'whatsapp' => self::WHATSAPP,
            'address_ar' => 'جمهورية مصر العربية',
            'address_en' => 'Arab Republic of Egypt',

            'currency_code' => 'EGP',
            'currency_symbol' => 'ج.م',
            'default_locale' => 'ar',
            'is_store_active' => true,
            'maintenance_message_ar' => 'المتجر تحت الصيانة حاليًا، نرجع لك قريبًا.',
            'maintenance_message_en' => 'The store is under maintenance. We will be back shortly.',

            'meta_title_ar' => 'ELLE | اسدالات وبيجامات وطرح ولانجري',
            'meta_title_en' => "ELLE | Prayer Dresses, Pajamas, Scarves & Lingerie",
            'meta_description_ar' => 'ELLE للأزياء النسائية. اسدالات صلاة ساتان، بيجامات ساتان حريري، طرح ولانجري. توصيل لكل المحافظات.',
            'meta_description_en' => "ELLE Women's Fashion. Satin prayer dresses, satin silk pajamas, scarves and lingerie. Delivery across Egypt.",
            'meta_keywords_ar' => 'ELLE, اسدالات, اسدال صلاة, بيجامات, بيجامة ساتان, طرح, لانجري, ملابس حريمي',
            'meta_keywords_en' => 'ELLE, prayer dress, isdal, satin pajamas, scarves, lingerie, women fashion egypt',
            'og_image' => 'settings/elle-logo.png',
            'robots' => 'index, follow',

            'cash_on_delivery_enabled' => true,
            'bank_transfer_enabled' => false,
            'wallet_transfer_enabled' => true,
            'cash_on_delivery_fee' => 0,
            'wallet_details_ar' => 'فودافون كاش: ' . self::PHONE,
            'wallet_details_en' => 'Vodafone Cash: ' . self::PHONE,
            'payment_instructions_ar' => 'بعد التحويل ارفعي صورة الإيصال حتى نتمكن من تأكيد الطلب.',
            'payment_instructions_en' => 'After transferring, upload the receipt so we can confirm your order.',
            'payment_proof_required' => true,

            'shipping_enabled' => true,
            'global_free_shipping_enabled' => true,
            'global_free_shipping_minimum' => 2000,
            'default_preparation_days' => 1,
            'default_delivery_days' => 3,
            'show_tracking_to_customer' => true,
            'shipping_policy_ar' => 'يتم تجهيز الطلب خلال يوم عمل، والتوصيل من ٢ إلى ٥ أيام حسب المحافظة.',
            'shipping_policy_en' => 'Orders are prepared within one working day; delivery takes 2-5 days depending on the governorate.',

            'invoices_enabled' => true,
            'invoice_prefix' => 'ELLE',
            'invoice_seller_name_ar' => 'ELLE للأزياء النسائية',
            'invoice_seller_name_en' => "ELLE Women's Fashion",
            'show_logo_on_invoice' => true,

            'email_notifications_enabled' => true,
            // Where new-order alerts go. Defaults to the authenticated SMTP
            // mailbox because that address is guaranteed to accept mail.
            'admin_notification_email' => config('mail.from.address'),
            'notify_admin_new_order' => true,
            'notify_customer_order_status' => true,

            'customer_registration_enabled' => true,
            'customer_login_enabled' => true,

            'dashboard_primary_color' => '#111111',
            'dashboard_sidebar_color' => '#111111',
            'dashboard_sidebar_text_color' => '#F5F1E8',
            'dashboard_topbar_color' => '#FFFFFF',
            'dashboard_logo' => 'settings/elle-logo.png',
            'dashboard_favicon' => 'settings/elle-logo.png',

            'announcement_bar_enabled' => true,
            'announcement_bar_text_ar' => 'شحن مجاني للطلبات فوق ٢٠٠٠ جنيه — توصيل لكل المحافظات',
            'announcement_bar_text_en' => 'Free shipping on orders over 2000 EGP — delivery across Egypt',
            'announcement_bar_bg_color' => '#111111',
            'announcement_bar_text_color' => '#FFFFFF',
            'announcement_bar_speed' => 25,
        ]);
    }

    private function socialLinks(): void
    {
        $links = [
            ['platform' => 'facebook', 'label_ar' => 'فيسبوك', 'label_en' => 'Facebook', 'url' => self::FACEBOOK],
            ['platform' => 'instagram', 'label_ar' => 'إنستجرام', 'label_en' => 'Instagram', 'url' => self::INSTAGRAM],
            ['platform' => 'whatsapp', 'label_ar' => 'واتساب', 'label_en' => 'WhatsApp', 'url' => 'https://wa.me/' . self::WHATSAPP],
        ];

        foreach ($links as $index => $link) {
            SocialLink::query()->create($link + [
                'is_active' => true,
                'open_in_new_tab' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /** @return array<string, Page> */
    private function pages(): array
    {
        $pages = [
            'about' => [
                'title_ar' => 'من نحن',
                'title_en' => 'About Us',
                'short_ar' => 'ELLE للأزياء النسائية — أناقة وراحة في كل قطعة.',
                'short_en' => "ELLE Women's Fashion — elegance and comfort in every piece.",
                'content_ar' => '<p>ELLE براند مصري للأزياء النسائية متخصص في الاسدالات والبيجامات والطرح واللانجري.</p>'
                    . '<p>نختار خاماتنا بعناية ونصمم قطعنا لتجمع بين الراحة والأناقة، ونوفر توصيل لكل محافظات مصر.</p>',
                'content_en' => "<p>ELLE is an Egyptian women's fashion brand specialising in prayer dresses, pajamas, scarves and lingerie.</p>"
                    . '<p>We choose our fabrics carefully and design every piece to combine comfort with elegance, with delivery across all Egyptian governorates.</p>',
            ],
            'contact' => [
                'title_ar' => 'تواصلي معنا',
                'title_en' => 'Contact Us',
                'short_ar' => 'فريق ELLE في خدمتك يوميًا.',
                'short_en' => 'The ELLE team is here for you every day.',
                'content_ar' => '<p>للطلب أو الاستفسار:</p><ul>'
                    . '<li>موبايل / واتساب: ' . self::PHONE . '</li>'
                    . '<li>فيسبوك: <a href="' . self::FACEBOOK . '" target="_blank" rel="noopener">ELLE على فيسبوك</a></li>'
                    . '<li>إنستجرام: <a href="' . self::INSTAGRAM . '" target="_blank" rel="noopener">elle40311</a></li>'
                    . '</ul>',
                'content_en' => '<p>To order or ask a question:</p><ul>'
                    . '<li>Mobile / WhatsApp: ' . self::PHONE . '</li>'
                    . '<li>Facebook: <a href="' . self::FACEBOOK . '" target="_blank" rel="noopener">ELLE on Facebook</a></li>'
                    . '<li>Instagram: <a href="' . self::INSTAGRAM . '" target="_blank" rel="noopener">elle40311</a></li>'
                    . '</ul>',
            ],
            'shipping-policy' => [
                'title_ar' => 'سياسة الشحن والتوصيل',
                'title_en' => 'Shipping Policy',
                'short_ar' => 'توصيل لكل محافظات مصر خلال ٢ إلى ٥ أيام.',
                'short_en' => 'Delivery to every Egyptian governorate within 2 to 5 days.',
                'content_ar' => '<ul>'
                    . '<li>يتم تجهيز الطلب خلال يوم عمل من تأكيده.</li>'
                    . '<li>مدة التوصيل من ٢ إلى ٥ أيام عمل حسب المحافظة.</li>'
                    . '<li>الشحن مجاني للطلبات التي تتجاوز ٢٠٠٠ جنيه.</li>'
                    . '<li>يتم التواصل معك تليفونيًا قبل التسليم.</li>'
                    . '</ul>',
                'content_en' => '<ul>'
                    . '<li>Orders are prepared within one working day of confirmation.</li>'
                    . '<li>Delivery takes 2 to 5 working days depending on the governorate.</li>'
                    . '<li>Shipping is free on orders above 2000 EGP.</li>'
                    . '<li>We call you before delivery.</li>'
                    . '</ul>',
            ],
            'return-policy' => [
                'title_ar' => 'سياسة الاستبدال والاسترجاع',
                'title_en' => 'Return & Exchange Policy',
                'short_ar' => 'استبدال وإرجاع سهل خلال ١٤ يوم.',
                'short_en' => 'Easy returns and exchanges within 14 days.',
                'content_ar' => '<ul>'
                    . '<li>يمكن الاستبدال أو الاسترجاع خلال ١٤ يوم من الاستلام.</li>'
                    . '<li>يجب أن تكون القطعة بحالتها الأصلية وبكامل ملحقاتها ولم تُستخدم.</li>'
                    . '<li>لأسباب صحية لا يمكن استرجاع منتجات اللانجري بعد فتح العبوة.</li>'
                    . '<li>لطلب الاستبدال تواصلي معنا على ' . self::PHONE . '.</li>'
                    . '</ul>',
                'content_en' => '<ul>'
                    . '<li>Returns and exchanges are accepted within 14 days of delivery.</li>'
                    . '<li>Items must be unused and in their original condition with all packaging.</li>'
                    . '<li>For hygiene reasons, lingerie cannot be returned once opened.</li>'
                    . '<li>To request an exchange, contact us on ' . self::PHONE . '.</li>'
                    . '</ul>',
            ],
            'privacy-policy' => [
                'title_ar' => 'سياسة الخصوصية',
                'title_en' => 'Privacy Policy',
                'short_ar' => 'كيف نتعامل مع بياناتك.',
                'short_en' => 'How we handle your data.',
                'content_ar' => '<p>نستخدم بياناتك (الاسم، رقم الهاتف، العنوان) لتنفيذ طلبك وتوصيله فقط، ولا نشاركها مع أي جهة غير شركة الشحن.</p>'
                    . '<p>يمكنك طلب حذف بياناتك في أي وقت بالتواصل معنا.</p>',
                'content_en' => '<p>We use your data (name, phone number, address) solely to fulfil and deliver your order, and we do not share it with anyone other than the courier.</p>'
                    . '<p>You may request deletion of your data at any time by contacting us.</p>',
            ],
            'terms' => [
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms & Conditions',
                'short_ar' => 'شروط استخدام متجر ELLE.',
                'short_en' => 'Terms of using the ELLE store.',
                'content_ar' => '<ul>'
                    . '<li>الأسعار بالجنيه المصري وتشمل قيمة المنتج فقط.</li>'
                    . '<li>يتم تأكيد الطلب تليفونيًا قبل الشحن.</li>'
                    . '<li>الألوان قد تختلف قليلًا حسب إضاءة الصور وشاشة العرض.</li>'
                    . '<li>يحق للمتجر إلغاء أي طلب في حالة نفاذ الكمية مع إخطار العميلة.</li>'
                    . '</ul>',
                'content_en' => '<ul>'
                    . '<li>Prices are in Egyptian pounds and cover the product only.</li>'
                    . '<li>Every order is confirmed by phone before shipping.</li>'
                    . '<li>Colours may vary slightly with photo lighting and your screen.</li>'
                    . '<li>We may cancel an order if stock runs out, and will notify you.</li>'
                    . '</ul>',
            ],
        ];

        $created = [];
        $sortOrder = 0;

        foreach ($pages as $slug => $page) {
            $sortOrder++;

            $created[$slug] = Page::query()->create([
                'title_ar' => $page['title_ar'],
                'title_en' => $page['title_en'],
                'slug_ar' => $slug,
                'slug_en' => $slug,
                'short_description_ar' => $page['short_ar'],
                'short_description_en' => $page['short_en'],
                'content_ar' => $page['content_ar'],
                'content_en' => $page['content_en'],
                'meta_title_ar' => $page['title_ar'] . ' | ELLE',
                'meta_title_en' => $page['title_en'] . ' | ELLE',
                'meta_description_ar' => $page['short_ar'],
                'meta_description_en' => $page['short_en'],
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        return $created;
    }

    /** @param array<string, Page> $pages */
    private function footer(array $pages): void
    {
        FooterSetting::query()->updateOrCreate(['id' => 1], [
            'logo' => 'settings/elle-logo.png',
            'title_ar' => 'ELLE',
            'title_en' => 'ELLE',
            'description_ar' => 'اسدالات وبيجامات وطرح ولانجري بخامات ناعمة وتصميمات أنيقة. توصيل لكل المحافظات.',
            'description_en' => 'Prayer dresses, pajamas, scarves and lingerie in soft fabrics and elegant designs. Delivery across Egypt.',
            'copyright_ar' => '© ' . date('Y') . ' ELLE — جميع الحقوق محفوظة.',
            'copyright_en' => '© ' . date('Y') . ' ELLE — All rights reserved.',
            'show_social_links' => true,
            'show_payment_methods' => true,
            'show_newsletter' => true,
            'newsletter_title_ar' => 'اشتركي في النشرة البريدية',
            'newsletter_title_en' => 'Join our newsletter',
            'newsletter_description_ar' => 'كوني أول من يعرف عن التشكيلات الجديدة والعروض.',
            'newsletter_description_en' => 'Be the first to hear about new collections and offers.',
            'is_active' => true,
        ]);

        $links = [
            ['group' => 'quick_links', 'page' => 'about'],
            ['group' => 'quick_links', 'page' => 'contact'],
            ['group' => 'customer_service', 'page' => 'shipping-policy'],
            ['group' => 'customer_service', 'page' => 'return-policy'],
            ['group' => 'policies', 'page' => 'privacy-policy'],
            ['group' => 'policies', 'page' => 'terms'],
        ];

        foreach ($links as $index => $link) {
            $page = $pages[$link['page']];

            FooterLink::query()->create([
                'group' => $link['group'],
                'link_type' => 'page',
                'page_id' => $page->id,
                'title_ar' => $page->title_ar,
                'title_en' => $page->title_en,
                'url' => '/pages/' . $page->slug_en,
                'open_in_new_tab' => false,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /** @param array<string, Page> $pages */
    private function navigation(array $pages): void
    {
        $sortOrder = 0;

        $routes = [
            ['route' => 'site.home', 'ar' => 'الرئيسية', 'en' => 'Home'],
            ['route' => 'site.shop', 'ar' => 'المتجر', 'en' => 'Shop'],
        ];

        foreach ($routes as $route) {
            $sortOrder++;

            NavigationLink::query()->create([
                'location' => 'header',
                'link_type' => 'route',
                'route_name' => $route['route'],
                'title_ar' => $route['ar'],
                'title_en' => $route['en'],
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        $categorySlugs = ['prayer-dresses', 'pajamas', 'scarves', 'lingerie'];

        $categoryIds = CategoryTranslation::query()
            ->where('locale', 'en')
            ->whereIn('slug', $categorySlugs)
            ->pluck('category_id', 'slug');

        foreach ($categorySlugs as $slug) {
            $category = Category::query()->with('translations')->find($categoryIds[$slug] ?? null);

            if (! $category) {
                continue;
            }

            $sortOrder++;

            // The storefront filters the shop page by ?category=, there is no
            // dedicated category route — so these are custom links.
            NavigationLink::query()->create([
                'location' => 'header',
                'link_type' => 'custom',
                'category_id' => $category->id,
                'url' => '/shop?category=' . $slug,
                'title_ar' => $category->translations->firstWhere('locale', 'ar')?->name ?? $slug,
                'title_en' => $category->translations->firstWhere('locale', 'en')?->name ?? $slug,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        $sortOrder++;

        NavigationLink::query()->create([
            'location' => 'header',
            'link_type' => 'page',
            'page_id' => $pages['contact']->id,
            'title_ar' => $pages['contact']->title_ar,
            'title_en' => $pages['contact']->title_en,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    private function homepage(): void
    {
        HomepageSetting::query()->updateOrCreate(['id' => 1], [
            'slider_enabled' => true,

            'featured_categories_enabled' => true,
            'featured_categories_title_ar' => 'تسوقي حسب القسم',
            'featured_categories_title_en' => 'Shop by Category',
            'featured_categories_limit' => 4,

            'featured_products_enabled' => true,
            'featured_products_title_ar' => 'منتجات مميزة',
            'featured_products_title_en' => 'Featured Products',
            'featured_products_limit' => 8,

            'new_products_enabled' => true,
            'new_products_title_ar' => 'وصل حديثًا',
            'new_products_title_en' => 'New Arrivals',
            'new_products_limit' => 8,

            'flash_sales_enabled' => true,
            'flash_sales_title_ar' => 'عروض محدودة',
            'flash_sales_title_en' => 'Limited Offers',
            'flash_sales_limit' => 8,

            'brands_enabled' => false,
            'brands_title_ar' => 'الماركات',
            'brands_title_en' => 'Brands',
            'brands_limit' => 8,

            'is_active' => true,
        ]);

        $sliders = [
            [
                'image' => 'homepage/sliders/slider-1.jpeg',
                'title_ar' => 'تشكيلة الاسدالات الجديدة',
                'title_en' => 'The New Prayer Dress Collection',
                'description_ar' => 'ساتان ناعم وطبعات مميزة — مقاس واحد يناسب الجميع.',
                'description_en' => 'Soft satin and distinctive prints — free size to fit all.',
                'button_text_ar' => 'تسوقي الآن',
                'button_text_en' => 'Shop Now',
                'button_url' => '/shop',
            ],
            [
                'image' => 'homepage/sliders/slider-2.jpeg',
                'title_ar' => 'بيجامات ساتان حريري',
                'title_en' => 'Satin Silk Pajamas',
                'description_ar' => 'راحة تامة وأناقة ما تتفوتش.',
                'description_en' => 'Comfort meets elegance.',
                'button_text_ar' => 'اكتشفي التشكيلة',
                'button_text_en' => 'Discover',
                'button_url' => '/shop',
            ],
            [
                'image' => 'homepage/sliders/slider-3.jpeg',
                'title_ar' => 'شحن مجاني فوق ٢٠٠٠ جنيه',
                'title_en' => 'Free Shipping Over 2000 EGP',
                'description_ar' => 'توصيل لكل محافظات مصر.',
                'description_en' => 'Delivery to every Egyptian governorate.',
                'button_text_ar' => 'اطلبي الآن',
                'button_text_en' => 'Order Now',
                'button_url' => '/shop',
            ],
        ];

        foreach ($sliders as $index => $slider) {
            HomepageSlider::query()->create($slider + [
                'open_in_new_tab' => false,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function paymentMethods(): void
    {
        $methods = [
            ['key' => 'cash_on_delivery', 'name_ar' => 'الدفع عند الاستلام', 'name_en' => 'Cash on Delivery'],
            ['key' => 'wallet_transfer', 'name_ar' => 'محفظة إلكترونية', 'name_en' => 'Mobile Wallet'],
        ];

        foreach ($methods as $index => $method) {
            PaymentMethodDisplay::query()->create($method + [
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function shipping(): void
    {
        $zones = [
            [
                'name_ar' => 'القاهرة الكبرى',
                'name_en' => 'Greater Cairo',
                'fee' => 60,
                'days' => 2,
                'cities' => [
                    ['ar' => 'القاهرة', 'en' => 'Cairo'],
                    ['ar' => 'الجيزة', 'en' => 'Giza'],
                    ['ar' => 'القليوبية', 'en' => 'Qalyubia'],
                ],
            ],
            [
                'name_ar' => 'الإسكندرية',
                'name_en' => 'Alexandria',
                'fee' => 70,
                'days' => 3,
                'cities' => [
                    ['ar' => 'الإسكندرية', 'en' => 'Alexandria'],
                    ['ar' => 'مطروح', 'en' => 'Matrouh'],
                ],
            ],
            [
                'name_ar' => 'الدلتا',
                'name_en' => 'Delta',
                'fee' => 75,
                'days' => 3,
                'cities' => [
                    ['ar' => 'الغربية', 'en' => 'Gharbia'],
                    ['ar' => 'المنوفية', 'en' => 'Monufia'],
                    ['ar' => 'الدقهلية', 'en' => 'Dakahlia'],
                    ['ar' => 'الشرقية', 'en' => 'Sharqia'],
                    ['ar' => 'كفر الشيخ', 'en' => 'Kafr El Sheikh'],
                    ['ar' => 'دمياط', 'en' => 'Damietta'],
                    ['ar' => 'البحيرة', 'en' => 'Beheira'],
                ],
            ],
            [
                'name_ar' => 'القناة وسيناء',
                'name_en' => 'Canal & Sinai',
                'fee' => 85,
                'days' => 4,
                'cities' => [
                    ['ar' => 'بورسعيد', 'en' => 'Port Said'],
                    ['ar' => 'الإسماعيلية', 'en' => 'Ismailia'],
                    ['ar' => 'السويس', 'en' => 'Suez'],
                    ['ar' => 'شمال سيناء', 'en' => 'North Sinai'],
                    ['ar' => 'جنوب سيناء', 'en' => 'South Sinai'],
                ],
            ],
            [
                'name_ar' => 'الصعيد',
                'name_en' => 'Upper Egypt',
                'fee' => 90,
                'days' => 5,
                'cities' => [
                    ['ar' => 'بني سويف', 'en' => 'Beni Suef'],
                    ['ar' => 'الفيوم', 'en' => 'Fayoum'],
                    ['ar' => 'المنيا', 'en' => 'Minya'],
                    ['ar' => 'أسيوط', 'en' => 'Assiut'],
                    ['ar' => 'سوهاج', 'en' => 'Sohag'],
                    ['ar' => 'قنا', 'en' => 'Qena'],
                    ['ar' => 'الأقصر', 'en' => 'Luxor'],
                    ['ar' => 'أسوان', 'en' => 'Aswan'],
                ],
            ],
            [
                'name_ar' => 'البحر الأحمر والوادي الجديد',
                'name_en' => 'Red Sea & New Valley',
                'fee' => 110,
                'days' => 6,
                'cities' => [
                    ['ar' => 'البحر الأحمر', 'en' => 'Red Sea'],
                    ['ar' => 'الوادي الجديد', 'en' => 'New Valley'],
                ],
            ],
        ];

        foreach ($zones as $zoneIndex => $zone) {
            $model = ShippingZone::query()->create([
                'name_ar' => $zone['name_ar'],
                'name_en' => $zone['name_en'],
                'is_active' => true,
                'sort_order' => $zoneIndex + 1,
            ]);

            foreach ($zone['cities'] as $cityIndex => $city) {
                ShippingCity::query()->create([
                    'shipping_zone_id' => $model->id,
                    'name_ar' => $city['ar'],
                    'name_en' => $city['en'],
                    'delivery_fee' => $zone['fee'],
                    'free_shipping_min_order' => 2000,
                    'estimated_delivery_days' => $zone['days'],
                    'is_active' => true,
                    'sort_order' => $cityIndex + 1,
                ]);
            }
        }
    }
}
