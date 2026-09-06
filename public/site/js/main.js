document.addEventListener("DOMContentLoaded", function () {
    /*
    |--------------------------------------------------------------------------
    | Header shadow on scroll
    |--------------------------------------------------------------------------
    */
  const siteHeader = document.querySelector("[data-site-header]");

if (siteHeader) {
    const canBeTransparent =
        siteHeader.dataset.headerTransparent === "true";

    const updateHeaderOnScroll = () => {
        if (!canBeTransparent) {
            siteHeader.classList.remove("site-header-transparent");
            siteHeader.classList.add("site-header-solid", "is-scrolled");
            return;
        }

        if (window.scrollY > 20) {
            siteHeader.classList.add("is-scrolled");
        } else {
            siteHeader.classList.remove("is-scrolled");
        }
    };

    updateHeaderOnScroll();

    window.addEventListener("scroll", updateHeaderOnScroll, {
        passive: true,
    });
}

    /*
    |--------------------------------------------------------------------------
    | Mobile Menu
    |--------------------------------------------------------------------------
    */
    const mobileMenuButton = document.querySelector(
        "[data-mobile-menu-button]",
    );
    const mobileMenu = document.querySelector("[data-mobile-menu]");

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener("click", function () {
            mobileMenu.classList.toggle("hidden");
            mobileMenuButton.classList.toggle("text-brand");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Hero Slider
    |--------------------------------------------------------------------------
    */
    const slider = document.querySelector("[data-hero-slider]");

    if (!slider) {
        return;
    }

    const slides = Array.from(slider.querySelectorAll(".hero-slide"));
    const dots = Array.from(document.querySelectorAll("[data-hero-dot]"));
    const next = document.querySelector("[data-hero-next]");
    const prev = document.querySelector("[data-hero-prev]");

    if (!slides.length) {
        return;
    }

    let current = 0;
    let timer = null;

    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    const dragThreshold = 60;

    function showSlide(index) {
        current = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle("is-active", slideIndex === current);
        });

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle("is-active", dotIndex === current);
        });
    }

    function nextSlide() {
        showSlide(current + 1);
    }

    function prevSlide() {
        showSlide(current - 1);
    }

    function stopAutoPlay() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    function startAutoPlay() {
        if (slides.length <= 1) {
            return;
        }

        stopAutoPlay();

        timer = setInterval(function () {
            nextSlide();
        }, 5000);
    }

    function resetAutoPlay() {
        stopAutoPlay();
        startAutoPlay();
    }

    if (next) {
        next.addEventListener("click", function () {
            nextSlide();
            resetAutoPlay();
        });
    }

    if (prev) {
        prev.addEventListener("click", function () {
            prevSlide();
            resetAutoPlay();
        });
    }

    dots.forEach((dot) => {
        dot.addEventListener("click", function () {
            showSlide(Number(dot.dataset.heroDot));
            resetAutoPlay();
        });
    });

    function getClientX(event) {
        if (event.touches && event.touches.length) {
            return event.touches[0].clientX;
        }

        return event.clientX;
    }

    function dragStart(event) {
        if (slides.length <= 1) {
            return;
        }

        isDragging = true;
        startX = getClientX(event);
        currentX = startX;

        slider.classList.add("is-dragging");
        stopAutoPlay();
    }

    function dragMove(event) {
        if (!isDragging) {
            return;
        }

        currentX = getClientX(event);
    }

    function dragEnd() {
        if (!isDragging) {
            return;
        }

        const diff = currentX - startX;

        if (Math.abs(diff) > dragThreshold) {
            const isRtl =
                document.documentElement.getAttribute("dir") === "rtl";

            if (!isRtl) {
                diff < 0 ? nextSlide() : prevSlide();
            } else {
                diff < 0 ? prevSlide() : nextSlide();
            }
        }

        isDragging = false;
        startX = 0;
        currentX = 0;

        slider.classList.remove("is-dragging");
        resetAutoPlay();
    }

    slider.addEventListener("touchstart", dragStart, { passive: true });
    slider.addEventListener("touchmove", dragMove, { passive: true });
    slider.addEventListener("touchend", dragEnd);

    slider.addEventListener("mousedown", dragStart);
    slider.addEventListener("mousemove", dragMove);
    slider.addEventListener("mouseup", dragEnd);
    slider.addEventListener("mouseleave", dragEnd);

    showSlide(0);
    startAutoPlay();
});
/*
|--------------------------------------------------------------------------
| Home Carousels
|--------------------------------------------------------------------------
|
| Categories, products, best sellers, new arrivals, flash sales and brands
| all behave the same way, so they share one implementation.
|
| Two things the previous per-section copies got wrong:
|
|   1. They advanced first and then, 450ms later, checked whether the end had
|      been reached - and if so jumped straight back to the start. The last
|      card was on screen for half a second instead of a full interval, so a
|      shopper never actually saw it. Wrapping now happens on the NEXT tick:
|      if we are already at the end, go back to the start, otherwise advance.
|
|   2. They used document.querySelector, one element each. Featured products
|      and best sellers share the data-products-slider name, so the second
|      one on the page was never wired up at all - dead arrows, no autoplay.
|      Every matching slider is initialised, and its arrows are looked up
|      inside its own <section>.
*/
(function () {
    const CAROUSELS = [
        { name: "categories", card: ".home-category-card", fallback: 245, interval: 3500 },
        { name: "products", card: ".product-card", fallback: 260, interval: 4000 },
        { name: "new-products", card: ".product-card", fallback: 260, interval: 4000 },
        { name: "flash-sales", card: ".product-card", fallback: 260, interval: 4000 },
        { name: "brands", card: ".home-brand-card", fallback: 230, interval: 3800 },
    ];

    /** How far one step moves: a whole card plus the gap beside it. */
    function stepSize(slider, config) {
        const card = slider.querySelector(config.card);

        if (!card) {
            return config.fallback;
        }

        const gap = window.innerWidth < 768 ? 16 : 20;
        const cardsToMove = window.innerWidth < 768 ? 2 : 1;

        return (card.offsetWidth + gap) * cardsToMove;
    }

    /**
     * Distance from the start, always positive.
     *
     * In a right-to-left column browsers report scrollLeft as a negative
     * number, so the raw value cannot be compared against scrollWidth.
     */
    function distanceFromStart(slider) {
        return Math.abs(slider.scrollLeft);
    }

    function maxScroll(slider) {
        return slider.scrollWidth - slider.clientWidth;
    }

    function atEnd(slider) {
        // A couple of pixels of slack: sub-pixel widths mean the scroll
        // position rarely lands exactly on the maximum.
        return distanceFromStart(slider) >= maxScroll(slider) - 4;
    }

    function atStart(slider) {
        return distanceFromStart(slider) <= 4;
    }

    function isRtl() {
        return document.documentElement.getAttribute("dir") === "rtl";
    }

    function scrollToStart(slider) {
        slider.scrollTo({ left: 0, behavior: "smooth" });
    }

    function scrollToEnd(slider) {
        const end = maxScroll(slider);

        slider.scrollTo({ left: isRtl() ? -end : end, behavior: "smooth" });
    }

    function step(slider, config, direction) {
        const amount = stepSize(slider, config) * direction;

        slider.scrollBy({ left: isRtl() ? -amount : amount, behavior: "smooth" });
    }

    function init(slider, config) {
        const section = slider.closest("section") || document;
        const next = section.querySelector("[data-" + config.name + "-next]");
        const prev = section.querySelector("[data-" + config.name + "-prev]");

        let timer = null;
        let dragging = false;
        let dragStartX = 0;
        let dragStartScroll = 0;

        /* Wrapping happens here, before moving: a slider sitting on the last
           card goes back to the start on the next tick, which leaves that
           card on screen for the whole interval like every other one. */
        function goNext() {
            if (atEnd(slider)) {
                scrollToStart(slider);

                return;
            }

            step(slider, config, 1);
        }

        function goPrev() {
            if (atStart(slider)) {
                scrollToEnd(slider);

                return;
            }

            step(slider, config, -1);
        }

        function stopAutoPlay() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function startAutoPlay() {
            stopAutoPlay();

            // Nothing to scroll: everything already fits.
            if (slider.scrollWidth <= slider.clientWidth) {
                return;
            }

            timer = setInterval(goNext, config.interval);
        }

        if (next) {
            next.addEventListener("click", function () {
                goNext();
                startAutoPlay();
            });
        }

        if (prev) {
            prev.addEventListener("click", function () {
                goPrev();
                startAutoPlay();
            });
        }

        // Reading a card should not have it slide away underneath you.
        slider.addEventListener("mouseenter", stopAutoPlay);
        slider.addEventListener("mouseleave", startAutoPlay);
        slider.addEventListener("focusin", stopAutoPlay);
        slider.addEventListener("touchstart", stopAutoPlay, { passive: true });
        slider.addEventListener("touchend", startAutoPlay);

        // Drag to scroll with a mouse.
        slider.addEventListener("mousedown", function (event) {
            dragging = true;
            slider.classList.add("is-dragging");
            dragStartX = event.pageX - slider.offsetLeft;
            dragStartScroll = slider.scrollLeft;
            stopAutoPlay();
        });

        slider.addEventListener("mousemove", function (event) {
            if (!dragging) {
                return;
            }

            event.preventDefault();

            const x = event.pageX - slider.offsetLeft;

            slider.scrollLeft = dragStartScroll - (x - dragStartX) * 1.5;
        });

        function endDrag() {
            if (!dragging) {
                return;
            }

            dragging = false;
            slider.classList.remove("is-dragging");
            startAutoPlay();
        }

        slider.addEventListener("mouseup", endDrag);
        slider.addEventListener("mouseleave", endDrag);

        // A slider that fits at one width may not fit at another.
        window.addEventListener("resize", startAutoPlay);

        // Autoplay only while the section is actually on screen.
        if (typeof IntersectionObserver === "function") {
            new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    entry.isIntersecting ? startAutoPlay() : stopAutoPlay();
                });
            }, { threshold: 0.2 }).observe(slider);
        } else {
            startAutoPlay();
        }
    }

    CAROUSELS.forEach(function (config) {
        document
            .querySelectorAll("[data-" + config.name + "-slider]")
            .forEach(function (slider) {
                init(slider, config);
            });
    });
})();


/*
|--------------------------------------------------------------------------
| Site Toast
|--------------------------------------------------------------------------
*/
const siteToast = document.getElementById("site-toast");
let siteToastTimer = null;

function showSiteToast(options = {}) {
    if (!siteToast) {
        return;
    }

    const type = options.type || "success";
    const title = options.title || "Success";
    const message = options.message || "Action completed successfully";
    const icon = options.icon || "✓";

    const titleElement = siteToast.querySelector("[data-toast-title]");
    const messageElement = siteToast.querySelector("[data-toast-message]");
    const iconElement = siteToast.querySelector("[data-toast-icon]");

    if (titleElement) {
        titleElement.textContent = title;
    }

    if (messageElement) {
        messageElement.textContent = message;
    }

    if (iconElement) {
        iconElement.textContent = icon;
    }

    siteToast.classList.remove("is-success", "is-error", "is-warning");
    siteToast.classList.add("is-" + type);
    siteToast.classList.add("is-visible");

    if (siteToastTimer) {
        clearTimeout(siteToastTimer);
    }

    siteToastTimer = setTimeout(function () {
        siteToast.classList.remove("is-visible");
    }, options.duration || 2600);
}

window.showSiteToast = showSiteToast;

window.addEventListener("site-toast", function (event) {
    showSiteToast(event.detail || {});
});

window.addEventListener("notify", function (event) {
    showSiteToast(event.detail || {});
});
