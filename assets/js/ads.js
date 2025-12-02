/**
 * Ads JavaScript - AGC Video Platform
 * Handles: Sticky Ads, Ad Container Management, Lazy Ad Loading
 */

(function() {
    'use strict';

    // =============================================
    // STICKY AD CONTAINER
    // =============================================
    const initStickyAd = () => {
        const adContainer = document.getElementById('ad-container');
        if (!adContainer) return;

        const lastClosed = localStorage.getItem('adLastClosed');
        const now = new Date().getTime();
        const fiveMinutes = 5 * 60 * 1000;

        // Show ad if not closed recently (5 minutes)
        if (!lastClosed || (now - lastClosed) > fiveMinutes) {
            adContainer.style.display = 'block';
        }
    };

    // Global function to close ad
    window.closeAd = function() {
        const adContainer = document.getElementById('ad-container');
        if (adContainer) {
            adContainer.style.display = 'none';
            localStorage.setItem('adLastClosed', new Date().getTime());
        }
    };

    // =============================================
    // LAZY AD LOADING SYSTEM
    // =============================================
    const initLazyAds = () => {
        const adSlots = document.querySelectorAll('[data-adcode]');
        if (!adSlots.length) return;

        const loadAd = (slot) => {
            const adCode = slot.dataset.adcode;
            if (adCode && !slot.dataset.loaded) {
                slot.innerHTML = adCode;
                slot.dataset.loaded = 'true';
                
                // Execute any scripts in the ad code
                const scripts = slot.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    if (script.src) {
                        newScript.src = script.src;
                        newScript.async = true;
                    } else {
                        newScript.textContent = script.textContent;
                    }
                    // Copy attributes
                    Array.from(script.attributes).forEach(attr => {
                        if (attr.name !== 'src') {
                            newScript.setAttribute(attr.name, attr.value);
                        }
                    });
                    script.parentNode.replaceChild(newScript, script);
                });
            }
        };

        // Use IntersectionObserver for lazy loading ads
        if ('IntersectionObserver' in window) {
            const adObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        loadAd(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '200px 0px',
                threshold: 0
            });

            adSlots.forEach(slot => adObserver.observe(slot));
        } else {
            // Fallback: load all ads immediately
            adSlots.forEach(loadAd);
        }
    };

    // =============================================
    // INITIALIZE ON DOM READY
    // =============================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initLazyAds();
        });
    } else {
        initLazyAds();
    }

    // Initialize sticky ad on window load
    window.addEventListener('load', initStickyAd);

})();

