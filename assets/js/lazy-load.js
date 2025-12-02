/**
 * Advanced Lazy Loading - AGC Video Platform
 * Optimized lazy loading with IntersectionObserver for images
 */

(function() {
    'use strict';

    const lazyLoad = () => {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        
                        // Set src from data-src
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        
                        // Set srcset from data-srcset if exists
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                            img.removeAttribute('data-srcset');
                        }
                        
                        // Add loaded class for CSS transitions
                        img.classList.add('loaded');
                        img.classList.remove('lazy');
                        
                        // Stop observing this image
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            lazyImages.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            lazyImages.forEach(img => {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                if (img.dataset.srcset) {
                    img.srcset = img.dataset.srcset;
                    img.removeAttribute('data-srcset');
                }
                img.classList.add('loaded');
                img.classList.remove('lazy');
            });
        }
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', lazyLoad);
    } else {
        lazyLoad();
    }

    // Re-run for dynamically added content
    window.lazyLoadImages = lazyLoad;

})();

