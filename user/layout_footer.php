
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('overlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                overlay.classList.toggle('active');
                
                // Add loading state to button
                this.classList.add('loading');
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 300);
            });
        }
        
        // Overlay click handler
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                overlay.classList.remove('active');
            });
        }
        
        // Add fade-in animation to cards
        const cards = document.querySelectorAll('.card, .stat-card');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, { threshold: 0.1 });
        
        cards.forEach(card => observer.observe(card));
        
        // Enhanced search functionality
        const searchInput = document.getElementById('search');
        const searchResults = document.getElementById('searchResults');
        
        if (searchInput) {
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchResults();
                }, 300); // Debounce search
            });
            
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length > 0) {
                    searchResults.classList.remove('d-none');
                    fetchResults();
                }
            });
        }
        
        // Close search results when clicking outside
        document.addEventListener('click', function(event) {
            if (searchResults && event.target !== searchInput && !searchResults.contains(event.target)) {
                searchResults.classList.add('d-none');
            }
        });
        
        // Add smooth scrolling to all internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                
                // Skip empty hash links
                if (href === '#') {
                    return;
                }
                
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add loading states to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.classList.contains('btn-loading')) {
                    this.classList.add('btn-loading');
                    setTimeout(() => {
                        this.classList.remove('btn-loading');
                    }, 2000);
                }
            });
        });
    });
    
    // Enhanced search function with better error handling
    function fetchResults() {
        const searchInput = document.getElementById('search');
        const searchResults = document.getElementById('searchResults');
        const searchTerm = searchInput.value.trim();
        
        if (searchTerm.length > 0) {
            searchResults.classList.remove('d-none');
            searchResults.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-muted">Searching...</span>
                </div>
            `;
            
            const basePath = typeof window.basePath !== 'undefined' ? window.basePath : '';
            fetch(`${basePath}search.php?query=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    if (data.trim()) {
                    searchResults.innerHTML = data;
                    } else {
                        searchResults.innerHTML = `
                            <div class="text-center py-3">
                                <i class="fas fa-search text-muted mb-2"></i>
                                <p class="text-muted mb-0">No results found</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = `
                        <div class="text-center py-3">
                            <i class="fas fa-exclamation-triangle text-danger mb-2"></i>
                            <p class="text-danger mb-0">Error fetching results</p>
                        </div>
                    `;
                });
        } else {
            searchResults.classList.add('d-none');
        }
    }
    
    // Utility function to show notifications
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
        }
        }, 5000);
    }
    
    // Add global error handler
    window.addEventListener('error', function(e) {
        console.error('Global error:', e.error);
        showNotification('An unexpected error occurred. Please try again.', 'danger');
    });
</script>

<!-- Additional CSS for enhanced animations -->
<style>
    .btn-loading {
        position: relative;
        pointer-events: none;
    }
    
    .btn-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    .fade-in-up {
        animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

</div> <!-- Close main-content -->
</body>
</html> 