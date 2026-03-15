/**
 * WPSA Accessibility Helpers
 * Saavutettavuusapurit WCAG 2.2 AA -yhteensopivuuteen
 *
 * @package WPSA_Booking
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Saavutettavuusapurit
     */
    const A11yHelpers = {
        
        /**
         * Trap focus modaalissa (WCAG 2.2)
         */
        trapFocus(element) {
            const focusableElements = element.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            
            const firstFocusable = focusableElements[0];
            const lastFocusable = focusableElements[focusableElements.length - 1];
            
            element.addEventListener('keydown', function(e) {
                if (e.key !== 'Tab') return;
                
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            });
            
            // Fokus ensimmäiseen elementtiin
            firstFocusable.focus();
        },
        
        /**
         * Announce to screen readers
         */
        announce(message, priority = 'polite') {
            const liveRegion = document.getElementById('wpsa-live-region');
            if (liveRegion) {
                liveRegion.setAttribute('aria-live', priority);
                liveRegion.textContent = message;
            }
        },
        
        /**
         * Add loading state
         */
        setLoading(element, isLoading) {
            if (isLoading) {
                element.setAttribute('aria-busy', 'true');
                element.disabled = true;
            } else {
                element.setAttribute('aria-busy', 'false');
                element.disabled = false;
            }
        },
        
        /**
         * Validate form accessibility
         */
        validateFormA11y(form) {
            const inputs = form.querySelectorAll('input, select, textarea');
            let isValid = true;
            
            inputs.forEach(input => {
                // Tarkista että jokaisella input-kentällä on label
                const id = input.id;
                const label = document.querySelector(`label[for="${id}"]`);
                
                if (!label && !input.getAttribute('aria-label')) {
                    console.warn(`Input missing label:`, input);
                    isValid = false;
                }
                
                // Tarkista pakolliset kentät
                if (input.hasAttribute('required')) {
                    if (!input.getAttribute('aria-required')) {
                        input.setAttribute('aria-required', 'true');
                    }
                }
            });
            
            return isValid;
        },
        
        /**
         * Check color contrast (WCAG 2.2)
         */
        checkContrast(foreground, background) {
            // Muunna hex -> RGB
            const getRGB = (hex) => {
                const r = parseInt(hex.slice(1, 3), 16);
                const g = parseInt(hex.slice(3, 5), 16);
                const b = parseInt(hex.slice(5, 7), 16);
                return {r, g, b};
            };
            
            // Laske luminanssi
            const getLuminance = (rgb) => {
                const {r, g, b} = rgb;
                const [rs, gs, bs] = [r, g, b].map(val => {
                    val = val / 255;
                    return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
                });
                return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
            };
            
            const l1 = getLuminance(getRGB(foreground));
            const l2 = getLuminance(getRGB(background));
            
            const ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
            
            return {
                ratio: ratio.toFixed(2),
                passAAA: ratio >= 7,
                passAA: ratio >= 4.5,
                passAALarge: ratio >= 3
            };
        }
    };
    
    // Exportoi globaalisti
    window.wpsaA11y = A11yHelpers;
    
    /**
     * Alusta kun DOM on valmis
     */
    document.addEventListener('DOMContentLoaded', () => {
        // Validoi lomakkeet
        const forms = document.querySelectorAll('.wpsa-booking-wizard form');
        forms.forEach(form => {
            A11yHelpers.validateFormA11y(form);
        });
    });

})();
