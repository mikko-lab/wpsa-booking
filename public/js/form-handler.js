/**
 * WPSA Form Handler
 * Varauslomakkeen käsittely ja lähetys
 *
 * @package WPSA_Booking
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Form Handler -luokka
     */
    class WPSAFormHandler {
        constructor() {
            this.formData = {};
            this.currentStep = 2; // Aloitetaan vaiheesta 2 (kalenteri)
            this.totalSteps = 3;
            this.init();
        }

        init() {
            this.setupNavigation();
            this.loadServiceData();
        }

        /**
         * Lataa palveludata
         */
        loadServiceData() {
            const dataElement = document.getElementById('wpsa-booking-data');
            if (dataElement) {
                try {
                    const data = JSON.parse(dataElement.textContent);
                    this.formData.service = data.selectedService;
                    this.formData.services = data.services;
                } catch (e) {
                    console.error('Error parsing booking data:', e);
                }
            }
        }

        /**
         * Navigaatio-painikkeet
         */
        setupNavigation() {
            const prevBtn = document.getElementById('wpsa-prev-step');
            const nextBtn = document.getElementById('wpsa-next-step');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => this.previousStep());
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => this.nextStep());
            }
        }

        /**
         * Seuraava vaihe
         */
        async nextStep() {
            // Kerää data nykyisestä vaiheesta
            if (this.currentStep === 2) {
                // Kalenteri-vaihe
                if (!window.wpsaCalendar || !window.wpsaTimeSlots) {
                    return;
                }
                
                const selection = window.wpsaCalendar.getSelection();
                const timeSlot = window.wpsaTimeSlots.getSelected();
                
                if (!selection.date || !timeSlot) {
                    this.showError('Valitse ensin päivä ja aika');
                    return;
                }
                
                this.formData.date = selection.formatted.date;
                this.formData.time = timeSlot.time;
                
                // Siirry yhteystietovaiheeseen (vaihe 3)
                this.showContactForm();
                
            } else if (this.currentStep === 3) {
                // Yhteystiedot-vaihe - lähetä varaus
                await this.submitBooking();
            }
        }

        /**
         * Edellinen vaihe
         */
        previousStep() {
            if (this.currentStep === 2) {
                // Takaisin palveluvalintaan (vaihe 1)
                // TODO: Toteuta palveluvalinta
            } else if (this.currentStep === 3) {
                // Takaisin kalenteriin
                this.showCalendar();
            }
        }

        /**
         * Näytä yhteystietolomake
         */
        showCalendar() {
            this.currentStep = 2;
            this.updateProgress();
            // TODO: Näytä kalenterinäkymä
        }

        /**
         * Näytä yhteystietolomake
         */
        showContactForm() {
            this.currentStep = 3;
            this.updateProgress();
            
            // TODO: Renderöi yhteystietolomake
            // Tämä on placeholder - toteuta myöhemmin
            console.log('Yhteystietolomake tulossa...');
        }

        /**
         * Päivitä progress-indikaattori
         */
        updateProgress() {
            const progress = document.querySelector('.wpsa-progress');
            if (progress) {
                progress.setAttribute('aria-valuenow', this.currentStep);
                
                const steps = progress.querySelectorAll('.wpsa-step');
                steps.forEach((step, index) => {
                    const stepNum = index + 1;
                    step.classList.remove('wpsa-step--complete', 'wpsa-step--active', 'wpsa-step--incomplete');
                    
                    if (stepNum < this.currentStep) {
                        step.classList.add('wpsa-step--complete');
                    } else if (stepNum === this.currentStep) {
                        step.classList.add('wpsa-step--active');
                        step.setAttribute('aria-current', 'step');
                    } else {
                        step.classList.add('wpsa-step--incomplete');
                        step.removeAttribute('aria-current');
                    }
                });
            }
        }

        /**
         * Lähetä varaus
         */
        async submitBooking() {
            const nextBtn = document.getElementById('wpsa-next-step');
            
            if (window.wpsaA11y) {
                window.wpsaA11y.setLoading(nextBtn, true);
            }
            
            try {
                const response = await fetch(`${wpsaBooking.restUrl}/bookings`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpsaBooking.nonce
                    },
                    body: JSON.stringify(this.formData)
                });
                
                if (!response.ok) {
                    throw new Error('Virhe varausta luodessa');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.showSuccess(data.message);
                    
                    // Custom event
                    document.dispatchEvent(new CustomEvent('wpsa:booking-created', {
                        detail: data.booking
                    }));
                } else {
                    this.showError(data.message || 'Virhe varausta luodessa');
                }
                
            } catch (error) {
                console.error('Booking error:', error);
                this.showError('Virhe varausta luodessa. Yritä uudelleen.');
            } finally {
                if (window.wpsaA11y) {
                    window.wpsaA11y.setLoading(nextBtn, false);
                }
            }
        }

        /**
         * Näytä virheviesti
         */
        showError(message) {
            const errorElement = document.getElementById('wpsa-error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                errorElement.focus();
                
                if (window.wpsaA11y) {
                    window.wpsaA11y.announce(message, 'assertive');
                }
            }
        }

        /**
         * Näytä onnistumisviesti
         */
        showSuccess(message) {
            // TODO: Näytä onnistumissivu
            console.log('Success:', message);
            
            if (window.wpsaA11y) {
                window.wpsaA11y.announce(message, 'polite');
            }
        }
    }

    /**
     * Alusta
     */
    document.addEventListener('DOMContentLoaded', () => {
        window.wpsaFormHandler = new WPSAFormHandler();
    });

})();
