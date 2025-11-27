/**
 * Client-side Anti-Injection Script
 * Removes any injected scripts from hosting provider
 */

(function() {
    'use strict';
    
    // Function to remove injected scripts
    function removeInjectedScripts() {
        // Get all script tags
        const scripts = document.querySelectorAll('script[src]');
        
        scripts.forEach(script => {
            const src = script.getAttribute('src') || '';
            
            // Check if script is from known injection domains
            if (src.includes('directfwd.com') || 
                src.includes('jsinit') || 
                src.includes('jspark') ||
                src.startsWith('http://')) {
                
                console.warn('Removing injected script:', src);
                script.remove();
            }
        });
        
        // Also check for inline scripts with injection patterns
        const inlineScripts = document.querySelectorAll('script:not([src])');
        inlineScripts.forEach(script => {
            const content = script.textContent || '';
            if (content.includes('directfwd') || 
                content.includes('jsinit') || 
                content.includes('jspark')) {
                console.warn('Removing injected inline script');
                script.remove();
            }
        });
    }
    
    // Run immediately
    removeInjectedScripts();
    
    // Run after DOM is fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeInjectedScripts);
    }
    
    // Run after all resources are loaded
    window.addEventListener('load', removeInjectedScripts);
    
    // Monitor for dynamically added scripts
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeName === 'SCRIPT') {
                    const src = node.getAttribute('src') || '';
                    const content = node.textContent || '';
                    
                    if (src.includes('directfwd.com') || 
                        src.includes('jsinit') || 
                        src.includes('jspark') ||
                        src.startsWith('http://') ||
                        content.includes('directfwd') ||
                        content.includes('jsinit') ||
                        content.includes('jspark')) {
                        
                        console.warn('Blocking dynamically added injected script');
                        node.remove();
                    }
                }
            });
        });
    });
    
    // Start observing
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });
    
    // Override document.write to prevent injection
    const originalWrite = document.write;
    document.write = function(content) {
        if (content.includes('directfwd') || 
            content.includes('jsinit') || 
            content.includes('jspark') ||
            content.includes('http://cdn.')) {
            console.warn('Blocked document.write injection attempt');
            return;
        }
        originalWrite.apply(document, arguments);
    };
    
})();
