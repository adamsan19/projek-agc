/**
 * Main JavaScript - AGC Video Platform
 * Handles: Video Player, Modals, Mobile Navigation, Lazy Loading
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // =============================================
    // VIDEO PLAYER
    // =============================================
    const initVideoPlayer = () => {
        const playerContainer = document.getElementById('video-player');
        const playButton = document.getElementById('play-button');
        const videoIframe = document.getElementById('video-iframe');

        if (playButton && videoIframe && playerContainer) {
            const videoEmbedUrl = videoIframe.dataset.src || videoIframe.getAttribute('data-embed-url') || '';
            
            const playVideo = () => {
                if (videoEmbedUrl) {
                    videoIframe.src = videoEmbedUrl;
                    playerContainer.classList.add('is-playing');
                }
            };
            
            playButton.addEventListener('click', playVideo);
        }
    };

    // =============================================
    // LAZY LOADING IMAGES
    // =============================================
    const initLazyLoading = () => {
        const lazyImages = document.querySelectorAll('img.lazy');
        
        if ("IntersectionObserver" in window) {
            const lazyImageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const lazyImage = entry.target;
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.classList.add('loaded');
                        lazyImage.classList.remove('lazy');
                        lazyImageObserver.unobserve(lazyImage);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            lazyImages.forEach(lazyImage => lazyImageObserver.observe(lazyImage));
        } else {
            // Fallback for browsers without IntersectionObserver
            lazyImages.forEach(lazyImage => {
                lazyImage.src = lazyImage.dataset.src;
                lazyImage.classList.add('loaded');
                lazyImage.classList.remove('lazy');
            });
        }
    };

    // =============================================
    // MODAL SYSTEM
    // =============================================
    const initModals = () => {
        const searchModal = document.getElementById('search-modal');
        const categoryModal = document.getElementById('category-modal');
        const openSearchBtn = document.getElementById('search-btn');
        const openCategoryBtnDesktop = document.getElementById('category-btn-desktop');
        const openCategoryBtnMobile = document.getElementById('category-btn-mobile');
        const closeSearchBtn = document.getElementById('close-search-modal');
        const closeCategoryBtn = document.getElementById('close-category-modal');
        const mobileNavDrawer = document.getElementById('mobile-nav-drawer');

        const openModal = (modal) => {
            if (modal) modal.style.display = 'block';
        };

        const closeModal = (modal) => {
            if (modal) modal.style.display = 'none';
        };

        // Search Modal
        if (openSearchBtn) {
            openSearchBtn.addEventListener('click', () => openModal(searchModal));
        }

        // Category Modal
        const openCategoryModal = (e) => {
            e.preventDefault();
            openModal(categoryModal);
            // Close drawer if category is opened from there
            if (mobileNavDrawer && mobileNavDrawer.classList.contains('is-open')) {
                toggleDrawer();
            }
        };

        if (openCategoryBtnDesktop) {
            openCategoryBtnDesktop.addEventListener('click', openCategoryModal);
        }
        if (openCategoryBtnMobile) {
            openCategoryBtnMobile.addEventListener('click', openCategoryModal);
        }

        // Close buttons
        if (closeSearchBtn) {
            closeSearchBtn.addEventListener('click', () => closeModal(searchModal));
        }
        if (closeCategoryBtn) {
            closeCategoryBtn.addEventListener('click', () => closeModal(categoryModal));
        }

        // Close on backdrop click
        window.addEventListener('click', (event) => {
            if (event.target === searchModal) closeModal(searchModal);
            if (event.target === categoryModal) closeModal(categoryModal);
        });

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal(searchModal);
                closeModal(categoryModal);
            }
        });
    };

    // =============================================
    // MOBILE NAVIGATION DRAWER
    // =============================================
    let toggleDrawer; // Declare in outer scope for modal access
    
    const initMobileNav = () => {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileNavDrawer = document.getElementById('mobile-nav-drawer');
        const mobileNavCloseBtn = document.getElementById('mobile-nav-close-btn');
        const drawerOverlay = document.getElementById('drawer-overlay');
        const body = document.body;

        // Lock body scroll when drawer is open
        const lockBodyScroll = () => {
            body.style.overflow = 'hidden';
            body.style.paddingRight = window.innerWidth - document.documentElement.clientWidth + 'px';
        };

        const unlockBodyScroll = () => {
            body.style.overflow = '';
            body.style.paddingRight = '';
        };

        // Animate menu items with stagger effect
        const animateMenuItems = (isOpen) => {
            const menuItems = mobileNavDrawer.querySelectorAll('.mobile-nav-drawer__list a');
            if (!menuItems.length) return;

            if (isOpen) {
                menuItems.forEach((item, index) => {
                    item.style.transitionDelay = `${index * 0.03}s`;
                });
            } else {
                menuItems.forEach((item) => {
                    item.style.transitionDelay = '0s';
                });
            }
        };

        toggleDrawer = () => {
            if (!mobileNavDrawer || !drawerOverlay) return;

            const isOpen = mobileNavDrawer.classList.contains('is-open');
            
            if (isOpen) {
                // Closing
                mobileNavDrawer.classList.remove('is-open');
                drawerOverlay.classList.remove('is-visible');
                unlockBodyScroll();
                animateMenuItems(false);
            } else {
                // Opening
                lockBodyScroll();
                mobileNavDrawer.classList.add('is-open');
                drawerOverlay.classList.add('is-visible');
                animateMenuItems(true);
            }
        };

        // Close drawer on ESC key
        const handleEscape = (e) => {
            if (e.key === 'Escape' && mobileNavDrawer.classList.contains('is-open')) {
                toggleDrawer();
            }
        };

        if (mobileMenuBtn && mobileNavDrawer && mobileNavCloseBtn && drawerOverlay) {
            mobileMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleDrawer();
            });
            
            mobileNavCloseBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleDrawer();
            });
            
            drawerOverlay.addEventListener('click', toggleDrawer);
            
            // Prevent drawer from closing when clicking inside
            mobileNavDrawer.addEventListener('click', (e) => {
                e.stopPropagation();
            });
            
            document.addEventListener('keydown', handleEscape);
        }
    };

    // =============================================
    // EXPANDABLE SUMMARY/DESCRIPTION
    // =============================================
    const initExpandableSummary = () => {
        const summaryContainer = document.getElementById('video-summary-container');
        const summaryContent = document.getElementById('video-description-content');
        const toggleBtn = document.getElementById('toggle-summary-btn');

        if (summaryContainer && summaryContent && toggleBtn) {
            // Check if content is overflowing
            if (summaryContent.scrollHeight <= summaryContainer.clientHeight) {
                toggleBtn.style.display = 'none';
                summaryContainer.classList.remove('is-collapsed');
            }

            summaryContainer.addEventListener('click', (e) => {
                const summary = summaryContainer;
                const content = summaryContent;
                const btn = toggleBtn;
                
                if ([summary, btn, content].includes(e.target)) {
                    const isCollapsed = summaryContainer.classList.toggle('is-collapsed');
                    toggleBtn.textContent = isCollapsed ? 'Baca Selengkapnya' : 'Ringkas';
                }
            });
        }
    };

    // =============================================
    // LAZYSIZES ERROR HANDLING
    // =============================================
    const initLazySizesErrorHandling = () => {
        const placeholderSvg = window.location.origin + '/assets/img/placeholder.svg';
        
        // Handle errors for lazysizes images (status 451, 404, network errors, etc.)
        const handleImageError = function(img) {
            const currentSrc = img.src || '';
            
            // Skip if already using placeholder or has been handled
            if (currentSrc.includes('placeholder.svg') || 
                currentSrc.includes('placeholder.png') ||
                img.hasAttribute('data-error-handled')) {
                return;
            }
            
            // Mark as handled to prevent infinite loop
            img.setAttribute('data-error-handled', 'true');
            
            // Replace with placeholder
            img.src = placeholderSvg;
            img.classList.add('lazyerror');
            img.classList.remove('lazyloading', 'lazyload', 'lazyloaded');
            
            // Remove data-src to prevent lazysizes from trying again
            if (img.hasAttribute('data-src')) {
                img.removeAttribute('data-src');
            }
            if (img.hasAttribute('data-srcset')) {
                img.removeAttribute('data-srcset');
            }
        };
        
        // Wait for lazysizes to be available
        const setupErrorHandling = () => {
            // Listen for lazysizes lazyerror event (built-in)
            document.addEventListener('lazyerror', function(e) {
                const img = e.target;
                if (img && img.tagName === 'IMG') {
                    handleImageError(img);
                }
            });
            
            // Listen for lazybeforeunveil to set up error handler before load
            document.addEventListener('lazybeforeunveil', function(e) {
                const img = e.target;
                if (img && img.tagName === 'IMG') {
                    // Set up error handler before image loads
                    img.addEventListener('error', function() {
                        handleImageError(this);
                    }, { once: true });
                }
            });
            
            // Handle errors on images that are already in the process of loading
            const lazyImages = document.querySelectorAll('img.lazyload, img[data-src], img.lazyloading');
            lazyImages.forEach(img => {
                // Add error handler if not already present
                if (!img.hasAttribute('data-error-handler-added')) {
                    img.setAttribute('data-error-handler-added', 'true');
                    img.addEventListener('error', function() {
                        handleImageError(this);
                    }, { once: true });
                }
            });
            
            // Handle images that appear to load but are actually broken
            document.addEventListener('lazyloaded', function(e) {
                const img = e.target;
                if (img && img.tagName === 'IMG') {
                    // Check if image actually loaded (status 451 returns HTML, not image)
                    setTimeout(() => {
                        if (!img.complete || img.naturalWidth === 0 || img.naturalHeight === 0) {
                            handleImageError(img);
                        }
                    }, 100);
                }
            });
            
            // Global error handler for ALL images (including non-lazy ones)
            document.addEventListener('error', function(e) {
                const img = e.target;
                if (img && img.tagName === 'IMG' && !img.hasAttribute('data-error-handled')) {
                    handleImageError(img);
                }
            }, true); // Use capture phase to catch all errors
        };
        
        // Initialize when DOM is ready and lazysizes is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupErrorHandling);
        } else {
            setupErrorHandling();
        }
        
        // Handle ALL existing images (including non-lazy ones)
        const handleAllExistingImages = () => {
            const allImages = document.querySelectorAll('img');
            allImages.forEach(img => {
                // Skip if already has onerror attribute (handled by HTML) or already handled
                if (img.hasAttribute('onerror') || img.hasAttribute('data-error-handler-added')) {
                    return;
                }
                
                // Skip placeholder images
                const src = img.src || '';
                if (src.includes('placeholder.svg') || src.includes('placeholder.png') || src.includes('placeholder-logo')) {
                    return;
                }
                
                img.setAttribute('data-error-handler-added', 'true');
                img.addEventListener('error', function() {
                    handleImageError(this);
                }, { once: true });
            });
        };
        
        // Run immediately and after DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', handleAllExistingImages);
        } else {
            handleAllExistingImages();
        }
        
        // Also handle dynamically added images
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.tagName === 'IMG') {
                                if (!node.hasAttribute('data-error-handler-added') && !node.hasAttribute('onerror')) {
                                    const src = node.src || '';
                                    if (!src.includes('placeholder.svg') && !src.includes('placeholder.png') && !src.includes('placeholder-logo')) {
                                        node.setAttribute('data-error-handler-added', 'true');
                                        node.addEventListener('error', function() {
                                            handleImageError(this);
                                        }, { once: true });
                                    }
                                }
                            }
                            // Also check for images inside added nodes (ALL images, not just lazy)
                            const allImgs = node.querySelectorAll && node.querySelectorAll('img');
                            if (allImgs) {
                                allImgs.forEach(img => {
                                    if (!img.hasAttribute('data-error-handler-added') && !img.hasAttribute('onerror')) {
                                        const src = img.src || '';
                                        if (!src.includes('placeholder.svg') && !src.includes('placeholder.png') && !src.includes('placeholder-logo')) {
                                            img.setAttribute('data-error-handler-added', 'true');
                                            img.addEventListener('error', function() {
                                                handleImageError(this);
                                            }, { once: true });
                                        }
                                    }
                                });
                            }
                        }
                    });
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    };

    // =============================================
    // INITIALIZE ALL MODULES
    // =============================================
    initMobileNav(); // Must be first (toggleDrawer is used by modals)
    initVideoPlayer();
    initLazyLoading();
    initLazySizesErrorHandling(); // Handle lazysizes errors
    initModals();
    initExpandableSummary();
});

// Make toggleDrawer available globally for modal access
window.toggleDrawer = window.toggleDrawer || function() {};

