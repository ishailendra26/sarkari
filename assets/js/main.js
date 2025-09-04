    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuBtn && mobileMenu) {
            const toggleMenu = (open = null) => {
                const isHidden = mobileMenu.classList.contains('hidden');
                const shouldOpen = open === null ? isHidden : open;
                if (shouldOpen) {
                    mobileMenu.classList.remove('hidden');
                    mobileMenuBtn.setAttribute('aria-expanded', 'true');
                } else {
                    mobileMenu.classList.add('hidden');
                    mobileMenuBtn.setAttribute('aria-expanded', 'false');
                }
            };

            mobileMenuBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleMenu();
            });

            // Prevent clicks inside the menu from bubbling to document
            mobileMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', function() {
                toggleMenu(false);
            });

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    toggleMenu(false);
                }
            });
        }

    // Search functionality with category support
    const MIN_QUERY_LEN = 2; // keep in sync with server-side validation
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');
    const heroSearchBtn = document.getElementById('hero-search-btn');
    const heroSearch = document.getElementById('hero-search');
    const heroSearchCategory = document.getElementById('search-category');
    const mobileSearchInput = document.getElementById('mobile-search-input');
    const jobSearchBtn = document.getElementById('job-search-btn');
    const jobSearchInput = document.getElementById('job-search');
    const resultSearchBtn = document.getElementById('result-search-btn');
    const resultSearchInput = document.getElementById('result-search');
    const admitSearchBtn = document.getElementById('admit-search-btn');
    const admitSearchInput = document.getElementById('admit-search');
    const syllabusSearchBtn = document.getElementById('syllabus-search-btn');
    const syllabusSearchInput = document.getElementById('syllabus-search');
    const searchCategorySelect = document.getElementById('search-category');
    const searchQueryBtn = document.getElementById('search-query-btn');
    const searchQueryInput = document.getElementById('search-query');

    // Utility: debounce
    function debounce(fn, delay = 300) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    let isNavigating = false;
    function performSearch(query, type = 'all', category = '') {
        const q = (query || '').trim();
        if (!q) {
            showNotification('Please enter a search term.', 'error');
            return;
        }
        if (q.length < MIN_QUERY_LEN) {
            showNotification(`Please enter at least ${MIN_QUERY_LEN} characters.`, 'error');
            return;
        }
        if (isNavigating) return;
        isNavigating = true;
        setTimeout(() => { isNavigating = false; }, 1200);

        let url = `search.php?q=${encodeURIComponent(q)}`;

        if (category && category !== 'all') {
            if (category.startsWith('category-')) {
                url += `&category=${encodeURIComponent(category)}`;
            } else {
                url += `&type=${encodeURIComponent(category)}`;
            }
        } else if (type !== 'all') {
            url += `&type=${encodeURIComponent(type)}`;
        }

        window.location.href = url;
    }

    // General search handlers
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            const query = searchInput?.value;
            performSearch(query);
        });
    }

    if (heroSearchBtn) {
        const handleHeroSearch = (e) => {
            if (e) {
                // Prevent duplicate navigations on some mobile browsers
                e.preventDefault();
                e.stopPropagation();
            }
            const query = heroSearch?.value;
            const category = heroSearchCategory?.value || 'all';
            performSearch(query, 'all', category);
        };

        // Click for desktop and most devices
        heroSearchBtn.addEventListener('click', debounce(handleHeroSearch, 200), { passive: false });
        // Touchend for certain mobile browsers where click may be delayed or swallowed
        heroSearchBtn.addEventListener('touchend', debounce(handleHeroSearch, 200), { passive: false });
    }

    // Page-specific search handlers
    if (jobSearchBtn) {
        jobSearchBtn.addEventListener('click', () => {
            const query = jobSearchInput?.value;
            performSearch(query, 'jobs');
        });
    }

    if (resultSearchBtn) {
        resultSearchBtn.addEventListener('click', () => {
            const query = resultSearchInput?.value;
            performSearch(query, 'results');
        });
    }

    if (admitSearchBtn) {
        admitSearchBtn.addEventListener('click', () => {
            const query = admitSearchInput?.value;
            performSearch(query, 'admits');
        });
    }

    if (syllabusSearchBtn) {
        syllabusSearchBtn.addEventListener('click', () => {
            const query = syllabusSearchInput?.value;
            performSearch(query, 'syllabus');
        });
    }

    // Search page query button
    if (searchQueryBtn) {
        searchQueryBtn.addEventListener('click', debounce(() => {
            const query = searchQueryInput?.value;
            const category = searchCategorySelect?.value || 'all';
            performSearch(query, 'all', category);
        }, 200));
    }

    // Enter key search for all inputs with category support
    [searchInput, heroSearch, mobileSearchInput, jobSearchInput, resultSearchInput, admitSearchInput, syllabusSearchInput, searchQueryInput].forEach(input => {
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = input.value;
                    let type = 'all';
                    let category = 'all';
                    
                    // Get category for hero search
                    if (input.id === 'hero-search' && heroSearchCategory) {
                        category = heroSearchCategory.value;
                    }
                    
                    // Determine search type based on input ID
                    if (input.id === 'job-search') type = 'jobs';
                    else if (input.id === 'result-search') type = 'results';
                    else if (input.id === 'admit-search') type = 'admits';
                    else if (input.id === 'syllabus-search') type = 'syllabus';
                    
                    performSearch(query, type, category);
                }
            });
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // Lazy loading for images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Copy to clipboard functionality
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copied to clipboard!', 'success');
        }).catch(() => {
            showNotification('Failed to copy', 'error');
        });
    }

    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg text-white font-medium transition-all duration-300 transform translate-x-full`;
        
        switch(type) {
            case 'success':
                notification.classList.add('bg-green-500');
                break;
            case 'error':
                notification.classList.add('bg-red-500');
                break;
            default:
                notification.classList.add('bg-blue-500');
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Bookmark button handler (homepage CTA)
    const bookmarkBtn = document.getElementById('bookmark-btn');
    if (bookmarkBtn) {
        bookmarkBtn.addEventListener('click', function() {
            const url = window.location.href;
            const title = document.title;
            try {
                // IE/Edge legacy support
                if (window.external && 'AddFavorite' in window.external) {
                    window.external.AddFavorite(url, title);
                    showNotification('Tip: Press Ctrl+D to bookmark this page', 'success');
                    return;
                }
            } catch (e) { /* ignore and fallback */ }

            // Fallback: copy URL and show keyboard shortcut hint
            copyToClipboard(url);
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const combo = isMac ? 'Cmd + D' : 'Ctrl + D';
            showNotification(`Press ${combo} to bookmark. URL copied to clipboard.`, 'success');
        });
    }

    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        // Disable native validation to avoid blocking before TinyMCE sync
        form.setAttribute('novalidate', '');
        form.addEventListener('submit', function(e) {
            // Sync TinyMCE editors back to their textareas before validation
            if (window.tinymce && typeof tinymce.triggerSave === 'function') {
                try { tinymce.triggerSave(); } catch (_) {}
            }
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                let value = field.value;
                // Special handling for TinyMCE richtext
                if (field.classList.contains('richtext') && window.tinymce) {
                    const ed = tinymce.get(field.id);
                    if (ed) {
                        const textContent = ed.getContent({ format: 'text' }).trim();
                        // Also ensure textarea mirrors content
                        field.value = ed.getContent();
                        value = textContent;
                    }
                }

                if (!value || !value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification('Please fill all required fields', 'error');
                // Focus the first invalid field
                const firstInvalid = Array.from(requiredFields).find(f => f.classList.contains('border-red-500'));
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus({ preventScroll: true });
                }
            }
        });
    });

    // Search type/category change functionality
    const searchTypeSelect = document.getElementById('search-type');
    if (searchTypeSelect) {
        searchTypeSelect.addEventListener('change', function() {
            const query = document.getElementById('search-query')?.value;
            const type = this.value;
            if (query) {
                window.location.href = `search.php?q=${encodeURIComponent(query)}&type=${type}`;
            }
        });
    }

    // Handle category change in search page
    if (searchCategorySelect && searchCategorySelect.id === 'search-category' && window.location.pathname.includes('search.php')) {
        searchCategorySelect.addEventListener('change', function() {
            const query = document.getElementById('search-query')?.value;
            const category = this.value;
            if (query) {
                performSearch(query, 'all', category);
            }
        });
    }

    // Back to top button
    const backToTop = document.createElement('button');
    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTop.className = 'fixed bottom-6 right-6 bg-primary text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-all duration-300 opacity-0 pointer-events-none z-50';
    backToTop.id = 'back-to-top';
    document.body.appendChild(backToTop);

    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.classList.remove('opacity-0', 'pointer-events-none');
        } else {
            backToTop.classList.add('opacity-0', 'pointer-events-none');
        }
    });

    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
    return `${Math.floor(diffDays / 365)} years ago`;
}
