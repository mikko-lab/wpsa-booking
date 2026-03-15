/**
 * WPSA Time Slots Handler
 * Aikaslotin valinta ja hallinta
 *
 * @package WPSA_Booking
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Time Slots -luokka
     */
    class WPSATimeSlots {
        constructor() {
            this.selectedSlot = null;
            this.init();
        }

        init() {
            // Kuunnellaan custom eventtiä
            document.addEventListener('wpsa:slots-loaded', (e) => {
                this.setupSlotListeners();
            });
        }

        /**
         * Aseta tapahtumakuuntelijat aikasloiteille
         */
        setupSlotListeners() {
            const slots = document.querySelectorAll('.wpsa-time-slot');
            
            slots.forEach(slot => {
                // Click
                slot.addEventListener('click', () => this.selectSlot(slot));
                
                // Keyboard
                slot.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.selectSlot(slot);
                    }
                });
            });
        }

        /**
         * Valitse aikasotti
         */
        selectSlot(slotElement) {
            // Poista aiempi valinta
            const allSlots = document.querySelectorAll('.wpsa-time-slot');
            allSlots.forEach(s => {
                s.classList.remove('wpsa-time-slot--selected');
                s.setAttribute('aria-checked', 'false');
            });
            
            // Merkitse valituksi
            slotElement.classList.add('wpsa-time-slot--selected');
            slotElement.setAttribute('aria-checked', 'true');
            
            // Tallenna
            this.selectedSlot = {
                time: slotElement.dataset.time,
                element: slotElement
            };
            
            // Ilmoita
            if (window.wpsaA11y) {
                const timeText = slotElement.querySelector('.wpsa-time-slot__time').textContent;
                window.wpsaA11y.announce(`Aika ${timeText} valittu`);
            }
            
            // Custom event
            document.dispatchEvent(new CustomEvent('wpsa:time-selected', {
                detail: this.selectedSlot
            }));
            
            // Aktivoi "Seuraava" -nappi
            const nextBtn = document.getElementById('wpsa-next-step');
            if (nextBtn) {
                nextBtn.disabled = false;
            }
        }

        /**
         * Hae valittu aikasotti
         */
        getSelected() {
            return this.selectedSlot;
        }
    }

    /**
     * Alusta
     */
    document.addEventListener('DOMContentLoaded', () => {
        window.wpsaTimeSlots = new WPSATimeSlots();
    });

})();
