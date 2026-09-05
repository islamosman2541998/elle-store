@php
    use App\Models\StoreSetting;

    $settings = StoreSetting::current();
@endphp

@if ($settings->tracking_enabled)

    {{-- Google Tag Manager --}}
    @if ($settings->google_tag_manager_id)
        <script>
            (function(w,d,s,l,i){
                w[l]=w[l]||[];
                w[l].push({'gtm.start': new Date().getTime(), event:'gtm.js'});
                var f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s),
                    dl=l!='dataLayer'?'&l='+l:'';
                j.async=true;
                j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
                f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ $settings->google_tag_manager_id }}');
        </script>
    @endif

    {{-- GA4 --}}
    @if ($settings->ga4_measurement_id)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $settings->ga4_measurement_id }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){ dataLayer.push(arguments); }

            gtag('js', new Date());
            gtag('config', '{{ $settings->ga4_measurement_id }}');
        </script>
    @endif

    {{-- Google Ads --}}
    @if ($settings->google_ads_conversion_id)
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){ dataLayer.push(arguments); }

            gtag('config', '{{ $settings->google_ads_conversion_id }}');
        </script>
    @endif

    {{-- Meta Pixel --}}
    @if ($settings->meta_pixel_id)
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');

            fbq('init', '{{ $settings->meta_pixel_id }}');
            fbq('track', 'PageView');
        </script>

        <noscript>
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id={{ $settings->meta_pixel_id }}&ev=PageView&noscript=1"/>
        </noscript>
    @endif

    {{-- TikTok Pixel --}}
    @if ($settings->tiktok_pixel_id)
        <script>
            !function (w, d, t) {
                w.TiktokAnalyticsObject=t;
                var ttq=w[t]=w[t]||[];
                ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];
                ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
                for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
                ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
                ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";
                    ttq._i=ttq._i||{};
                    ttq._i[e]=[];
                    ttq._i[e]._u=i;
                    ttq._t=ttq._t||{};
                    ttq._t[e]=+new Date;
                    ttq._o=ttq._o||{};
                    ttq._o[e]=n||{};
                    var o=document.createElement("script");
                    o.type="text/javascript";
                    o.async=!0;
                    o.src=i+"?sdkid="+e+"&lib="+t;
                    var a=document.getElementsByTagName("script")[0];
                    a.parentNode.insertBefore(o,a);
                };

                ttq.load('{{ $settings->tiktok_pixel_id }}');
                ttq.page();
            }(window, document, 'ttq');
        </script>
    @endif

    {{-- Snapchat Pixel --}}
    @if ($settings->snapchat_pixel_id)
        <script>
            (function(e,t,n){
                if(e.snaptr)return;
                var a=e.snaptr=function(){
                    a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)
                };
                a.queue=[];
                var s='script';
                r=t.createElement(s);
                r.async=!0;
                r.src=n;
                var u=t.getElementsByTagName(s)[0];
                u.parentNode.insertBefore(r,u);
            })(window,document,'https://sc-static.net/scevent.min.js');

            snaptr('init', '{{ $settings->snapchat_pixel_id }}');
            snaptr('track', 'PAGE_VIEW');
        </script>
    @endif

    {{-- LinkedIn Insight --}}
    @if ($settings->linkedin_partner_id)
        <script type="text/javascript">
            _linkedin_partner_id = "{{ $settings->linkedin_partner_id }}";
            window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
            window._linkedin_data_partner_ids.push(_linkedin_partner_id);
        </script>
        <script type="text/javascript">
            (function(l) {
                if (!l){
                    window.lintrk = function(a,b){window.lintrk.q.push([a,b])};
                    window.lintrk.q=[];
                }
                var s = document.getElementsByTagName("script")[0];
                var b = document.createElement("script");
                b.type = "text/javascript";
                b.async = true;
                b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
                s.parentNode.insertBefore(b, s);
            })(window.lintrk);
        </script>
    @endif

    {{-- Pinterest Tag --}}
    @if ($settings->pinterest_tag_id)
        <script>
            !function(e){
                if(!window.pintrk){
                    window.pintrk = function () {
                        window.pintrk.queue.push(Array.prototype.slice.call(arguments))
                    };
                    var n=window.pintrk;
                    n.queue=[];
                    n.version="3.0";
                    var t=document.createElement("script");
                    t.async=!0;
                    t.src=e;
                    var r=document.getElementsByTagName("script")[0];
                    r.parentNode.insertBefore(t,r);
                }
            }("https://s.pinimg.com/ct/core.js");

            pintrk('load', '{{ $settings->pinterest_tag_id }}');
            pintrk('page');
        </script>
    @endif

@endif