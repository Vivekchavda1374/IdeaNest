/**
 * Client-side Anti-Injection Script
 * Blocks and removes injected scripts from hosting provider
 * Must be loaded FIRST in the <head> section
 */

(function() {
    'use strict';
    
    // Immediately block any HTTP scripts
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.tagName === 'SCRIPT') {
                    const src = node.getAttribute('src') || '';
                    
                    // Block HTTP scripts (non-HTTPS)
                    if (src.startsWith('http://') && !src.includes('localhost') && !src.includes('127.0.0.1')) {
                        console.warn('Blocked insecure script:', src);
                        node.remove();
                        return;
                    }
                    
                    // Block known injection domains
                    const blockedDomains = [
                        'directfwd.com',
                        'jsinit',
                        'jspark',
                        'cdn.jsinit'
                    ];
                    
                    for (const domain of blockedDomains) {
                        if (src.includes(domain)) {
                            console.warn('Blocked injected script:', src);
                            node.remove();
                            return;
                        }
                    }
                }
            });
        });
    });
    
    // Start observing immediately
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });
    
    // Clean up existing scripts on DOM ready
    function cleanExistingScripts() {
        const scripts = document.querySelectorAll('script[src]');
        scripts.forEach(function(script) {
            const src = script.getAttribute('src') || '';
            
            // Remove HTTP scripts
            if (src.startsWith('http://') && !src.includes('localhost')) {
                console.warn('Removed insecure script:', src);
                script.remove();
                return;
            }
            
            // Remove injection scripts
            const blockedDomains = ['directfwd.com', 'jsinit', 'jspark'];
            for (const domain of blockedDomains) {
                if (src.includes(domain)) {
                    console.warn('Removed injected script:', src);
                    script.remove();
                    return;
                }
            }
        });
    }
    
    // Run cleanup immediately and on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', cleanExistingScripts);
    } else {
        cleanExistingScripts();
    }
    
    // Run cleanup again after a short delay to catch late injections
    setTimeout(cleanExistingScripts, 100);
    setTimeout(cleanExistingScripts, 500);
    
})();
