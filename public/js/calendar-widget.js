/**
 * WPSA Calendar Widget
 * WCAG 2.2 AA -yhteensopiva kalenterivalitsin
 *
 * @package WPSA_Booking
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Kalenteriwidget-luokka
     */
    class WPSACalendar {
        constructor(element) {
            this.element = element;
            this.grid = document.getElementById('wpsa-calendar-grid');
            this.monthTitle = document.getElementById('wpsa-calendar-month');
            this.prevBtn = document.getElementById('wpsa-prev-month');
            this.nextBtn = document.getElementById('wpsa-next-month');
            this.liveRegion = document.getElementById('wpsa-live-region');
            
            this.currentDate = new Date();
            this.selectedDate = null;
            this.focusedDate = new Date();
            
            this.weekdays = ['Ma', 'Ti', 'Ke', 'To', 'Pe', 'La', 'Su'];
            this.monthNames = [
                'Tammikuu', 'Helmikuu', 'Maaliskuu', 'Huhtikuu',
                'Toukokuu', 'Kesäkuu', 'Heinäkuu', 'Elokuu',
                'Syyskuu', 'Lokakuu', 'Marraskuu', 'Joulukuu'
            ];
            
            this.init();
        }

        init() {
            this.renderCalendar();
            this.attachEventListeners();
            this.setupKeyboardNavigation();
        }

        /**
         * Renderöi kalenteri
         */
        renderCalendar() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            
            // Päivitä otsikko
            this.monthTitle.textContent = `${this.monthNames[month]} ${year}`;
            
            // Hae ensimmäinen ja viimeinen päivä
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            
            // Laske montako tyhjää ruutua tarvitaan alussa
            // (1 = Maanantai, 7 = Sunnuntai)
            let startDay = firstDay.getDay();
            startDay = startDay === 0 ? 6 : startDay - 1; // Muunna sunnuntai=7
            
            // Tyhjennä grid (jätä viikonpäivät)
            const existingDays = this.grid.querySelectorAll('.wpsa-calendar-day');
            existingDays.forEach(day => day.remove());
            
            // Lisää tyhjät ruudut
            for (let i = 0; i < startDay; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.setAttribute('role', 'gridcell');
                this.grid.appendChild(emptyCell);
            }
            
            // Lisää päivät
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const dayButton = this.createDayButton(year, month, day);
                this.grid.appendChild(dayButton);
            }
            
            // Fokus ensimmäiseen saatavilla olevaan päivään
            this.focusFirstAvailableDay();
        }

        /**
         * Luo päiväpainike
         */
        createDayButton(year, month, day) {
            const date = new Date(year, month, day);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'wpsa-calendar-day';
            button.textContent = day;
            button.setAttribute('role', 'gridcell');
            button.setAttribute('data-date', this.formatDate(date));
            button.setAttribute('aria-label', this.formatDateLong(date));
            
            // WCAG 2.2 2.5.8 - Target Size varmistettu CSS:ssä (min 44x44px)
            
            // Onko tänään?
            if (date.toDateString() === today.toDateString()) {
                button.classList.add('wpsa-calendar-day--today');
            }
            
            // Onko menneisyydessä? (disable)
            if (date < today) {
                button.disabled = true;
                button.classList.add('wpsa-calendar-day--disabled');
                button.setAttribute('aria-disabled', 'true');
            }
            
            // Onko valittu?
            if (this.selectedDate && date.toDateString() === this.selectedDate.toDateString()) {
                button.classList.add('wpsa-calendar-day--selected');
                button.setAttribute('aria-pressed', 'true');
            } else {
                button.setAttribute('aria-pressed', 'false');
            }
            
            // Click event
            button.addEventListener('click', () => this.selectDate(date, button));
            
            return button;
        }

        /**
         * Valitse päivä
         */
        selectDate(date, button) {
            this.selectedDate = date;
            
            // Päivitä visualisointi
            const allDays = this.grid.querySelectorAll('.wpsa-calendar-day');
            allDays.forEach(day => {
                day.classList.remove('wpsa-calendar-day--selected');
                day.setAttribute('aria-pressed', 'false');
            });
            
            button.classList.add('wpsa-calendar-day--selected');
            button.setAttribute('aria-pressed', 'true');
            
            // Ilmoita ruudunlukijalle (WCAG 2.2 - Live Regions)
            this.announceSelection(date);
            
            // Lataa aikaslotit
            this.loadTimeSlots(date);
            
            // Aktivoi "Seuraava" -nappi
            const nextBtn = document.getElementById('wpsa-next-step');
            if (nextBtn) {
                nextBtn.disabled = false;
            }
        }

        /**
         * Ilmoita valinta ruudunlukijalle
         */
        announceSelection(date) {
            const formatted = this.formatDateLong(date);
            this.liveRegion.textContent = `${formatted} valittu. Ladataan vapaita aikoja...`;
        }

        /**
         * Lataa vapaat aikaslotit
         */
        async loadTimeSlots(date) {
            const slotsContainer = document.getElementById('wpsa-time-slots-container');
            const slotsGrid = document.getElementById('wpsa-time-slots');
            const loading = document.getElementById('wpsa-loading-slots');
            const noSlots = document.getElementById('wpsa-no-slots');
            const error = document.getElementById('wpsa-error');
            
            // Näytä loading
            slotsContainer.style.display = 'none';
            noSlots.style.display = 'none';
            error.style.display = 'none';
            loading.style.display = 'block';
            
            try {
                const response = await fetch(
                    `${wpsaBooking.restUrl}/slots/${this.formatDate(date)}`,
                    {
                        headers: {
                            'X-WP-Nonce': wpsaBooking.nonce
                        }
                    }
                );
                
                if (!response.ok) {
                    throw new Error('Virhe haettaessa aikoja');
                }
                
                const data = await response.json();
                
                loading.style.display = 'none';
                
                if (data.slots && data.slots.length > 0) {
                    this.renderTimeSlots(data.slots);
                    slotsContainer.style.display = 'block';
                    
                    // Päivitä live region
                    this.liveRegion.textContent = `Löytyi ${data.count} vapaata aikaa`;
                } else {
                    noSlots.style.display = 'block';
                    this.liveRegion.textContent = 'Ei vapaita aikoja tälle päivälle';
                }
                
            } catch (err) {
                loading.style.display = 'none';
                error.style.display = 'block';
                error.textContent = 'Virhe ladattaessa aikoja. Yritä uudelleen.';
                this.liveRegion.textContent = 'Virhe ladattaessa aikoja';
                console.error('WPSA Booking Error:', err);
            }
        }

        /**
         * Renderöi aikaslotit
         */
        renderTimeSlots(slots) {
            const slotsGrid = document.getElementById('wpsa-time-slots');
            slotsGrid.innerHTML = '';
            
            slots.forEach((slot, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'wpsa-time-slot';
                button.setAttribute('role', 'radio');
                button.setAttribute('aria-checked', 'false');
                button.setAttribute('data-time', slot.time);
                
                const time = document.createElement('div');
                time.className = 'wpsa-time-slot__time';
                time.textContent = `${slot.time} - ${slot.end_time}`;
                
                const duration = document.createElement('div');
                duration.className = 'wpsa-time-slot__duration';
                duration.textContent = '45 min';
                
                button.appendChild(time);
                button.appendChild(duration);
                
                // Click event
                button.addEventListener('click', () => this.selectTimeSlot(button, slot));
                
                slotsGrid.appendChild(button);
            });
        }

        /**
         * Valitse aikasotti
         */
        selectTimeSlot(button, slot) {
            // Poista edellinen valinta
            const allSlots = document.querySelectorAll('.wpsa-time-slot');
            allSlots.forEach(s => {
                s.classList.remove('wpsa-time-slot--selected');
                s.setAttribute('aria-checked', 'false');
            });
            
            // Merkitse valituksi
            button.classList.add('wpsa-time-slot--selected');
            button.setAttribute('aria-checked', 'true');
            
            // Tallenna valinta
            this.selectedTime = slot.time;
            
            // Ilmoita ruudunlukijalle
            this.liveRegion.textContent = `Aika ${slot.time} - ${slot.end_time} valittu`;
            
            // Aktivoi "Seuraava" -nappi
            const nextBtn = document.getElementById('wpsa-next-step');
            if (nextBtn) {
                nextBtn.disabled = false;
            }
        }

        /**
         * Näppäimistönavigaatio (WCAG 2.2)
         */
        setupKeyboardNavigation() {
            this.grid.addEventListener('keydown', (e) => {
                const currentDay = document.activeElement;
                
                if (!currentDay.classList.contains('wpsa-calendar-day')) {
                    return;
                }
                
                let handled = false;
                
                switch(e.key) {
                    case 'ArrowRight':
                        this.focusNextDay(currentDay);
                        handled = true;
                        break;
                        
                    case 'ArrowLeft':
                        this.focusPreviousDay(currentDay);
                        handled = true;
                        break;
                        
                    case 'ArrowDown':
                        this.focusDayBelow(currentDay);
                        handled = true;
                        break;
                        
                    case 'ArrowUp':
                        this.focusDayAbove(currentDay);
                        handled = true;
                        break;
                        
                    case 'Home':
                        this.focusFirstDay();
                        handled = true;
                        break;
                        
                    case 'End':
                        this.focusLastDay();
                        handled = true;
                        break;
                        
                    case 'Enter':
                    case ' ':
                        // Valitse päivä
                        if (!currentDay.disabled) {
                            currentDay.click();
                        }
                        handled = true;
                        break;
                        
                    case 'Escape':
                        // Tyhjennä valinta
                        this.clearSelection();
                        handled = true;
                        break;
                }
                
                if (handled) {
                    e.preventDefault();
                }
            });
        }

        /**
         * Fokusoi seuraava päivä
         */
        focusNextDay(current) {
            const days = Array.from(this.grid.querySelectorAll('.wpsa-calendar-day:not(:disabled)'));
            const index = days.indexOf(current);
            
            if (index < days.length - 1) {
                days[index + 1].focus();
            }
        }

        /**
         * Fokusoi edellinen päivä
         */
        focusPreviousDay(current) {
            const days = Array.from(this.grid.querySelectorAll('.wpsa-calendar-day:not(:disabled)'));
            const index = days.indexOf(current);
            
            if (index > 0) {
                days[index - 1].focus();
            }
        }

        /**
         * Fokusoi päivä alapuolella
         */
        focusDayBelow(current) {
            const days = Array.from(this.grid.querySelectorAll('.wpsa-calendar-day:not(:disabled)'));
            const index = days.indexOf(current);
            const nextIndex = index + 7;
            
            if (nextIndex < days.length) {
                days[nextIndex].focus();
            }
        }

        /**
         * Fokusoi päivä yläpuolella
         */
        focusDayAbove(current) {
            const days = Array.from(this.grid.querySelectorAll('.wpsa-calendar-day:not(:disabled)'));
            const index = days.indexOf(current);
            const prevIndex = index - 7;
            
            if (prevIndex >= 0) {
                days[prevIndex].focus();
            }
        }

        /**
         * Fokusoi ensimmäinen päivä
         */
        focusFirstDay() {
            const days = this.grid.querySelectorAll('.wpsa-calendar-day:not(:disabled)');
            if (days.length > 0) {
                days[0].focus();
            }
        }

        /**
         * Fokusoi viimeinen päivä
         */
        focusLastDay() {
            const days = this.grid.querySelectorAll('.wpsa-calendar-day:not(:disabled)');
            if (days.length > 0) {
                days[days.length - 1].focus();
            }
        }

        /**
         * Fokusoi ensimmäinen saatavilla oleva päivä
         */
        focusFirstAvailableDay() {
            const days = this.grid.querySelectorAll('.wpsa-calendar-day:not(:disabled)');
            if (days.length > 0) {
                days[0].setAttribute('tabindex', '0');
            }
        }

        /**
         * Tyhjennä valinta
         */
        clearSelection() {
            this.selectedDate = null;
            this.selectedTime = null;
            
            const allDays = this.grid.querySelectorAll('.wpsa-calendar-day');
            allDays.forEach(day => {
                day.classList.remove('wpsa-calendar-day--selected');
                day.setAttribute('aria-pressed', 'false');
            });
            
            // Piilota aikaslotit
            document.getElementById('wpsa-time-slots-container').style.display = 'none';
            
            // Disabloi "Seuraava" -nappi
            const nextBtn = document.getElementById('wpsa-next-step');
            if (nextBtn) {
                nextBtn.disabled = true;
            }
            
            this.liveRegion.textContent = 'Valinta tyhjennetty';
        }

        /**
         * Tapahtumakuuntelijat
         */
        attachEventListeners() {
            // Edellinen kuukausi
            this.prevBtn.addEventListener('click', () => {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                this.renderCalendar();
            });
            
            // Seuraava kuukausi
            this.nextBtn.addEventListener('click', () => {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                this.renderCalendar();
            });
        }

        /**
         * Apufunktiot
         */
        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        formatDateLong(date) {
            const weekday = ['Sunnuntai', 'Maanantai', 'Tiistai', 'Keskiviikko', 'Torstai', 'Perjantai', 'Lauantai'][date.getDay()];
            const day = date.getDate();
            const month = this.monthNames[date.getMonth()].toLowerCase();
            const year = date.getFullYear();
            return `${weekday} ${day}. ${month}ta ${year}`;
        }

        /**
         * Hae valittu data
         */
        getSelection() {
            return {
                date: this.selectedDate,
                time: this.selectedTime,
                formatted: {
                    date: this.selectedDate ? this.formatDate(this.selectedDate) : null,
                    time: this.selectedTime
                }
            };
        }
    }

    /**
     * Alusta kun DOM on valmis
     */
    document.addEventListener('DOMContentLoaded', () => {
        const calendarElement = document.querySelector('.wpsa-calendar');
        
        if (calendarElement) {
            window.wpsaCalendar = new WPSACalendar(calendarElement);
        }
    });

})();
