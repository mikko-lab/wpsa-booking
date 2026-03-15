import React, { useState, useEffect, useRef } from 'react';
import { useBooking } from '../contexts/BookingContext';
import { api, TimeSlot } from '../utils/api';
import { format, addMonths, subMonths, startOfMonth, endOfMonth, startOfWeek, endOfWeek, eachDayOfInterval, isSameMonth, isSameDay, isToday, isBefore, startOfDay } from 'date-fns';
import { fi } from 'date-fns/locale';

interface DateTimeSelectionProps {
  onStepChange: (message: string) => void;
}

const DateTimeSelection: React.FC<DateTimeSelectionProps> = ({ onStepChange }) => {
  const { bookingData, setBookingData, sessionId, lockedSlot, setLockedSlot, nextStep, previousStep } = useBooking();
  
  const [currentMonth, setCurrentMonth] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState<Date | null>(
    bookingData.date ? new Date(bookingData.date) : null
  );
  const [focusedDate, setFocusedDate] = useState<Date | null>(null); // Roving tabindex
  const [selectedTime, setSelectedTime] = useState<string | undefined>(bookingData.time);
  const [timeSlots, setTimeSlots] = useState<TimeSlot[]>([]);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [lockingSlot, setLockingSlot] = useState(false);
  const [lockError, setLockError] = useState<string | null>(null);
  
  const timeSlotsRef = useRef<HTMLDivElement>(null);
  const calendarGridRef = useRef<HTMLDivElement>(null);
  
  useEffect(() => {
    onStepChange('Vaihe 1: Valitse aika');
    
    // Nollaa local state kun palataan kalenteriin
    setSelectedDate(null);
    setSelectedTime(undefined);
    setTimeSlots([]);
    setLoadingSlots(false);
    
    // Aseta fokus tähän päivään (roving tabindex)
    setFocusedDate(new Date());
  }, []);
  
  useEffect(() => {
    if (selectedDate && bookingData.service) {
      loadTimeSlots();
    }
  }, [selectedDate, bookingData.service]);
  
  const loadTimeSlots = async () => {
    console.log('🔍 loadTimeSlots called:', {
      selectedDate,
      service: bookingData.service,
      sessionId,
      formattedDate: selectedDate ? format(selectedDate, 'yyyy-MM-dd') : null
    });
    
    if (!selectedDate || !bookingData.service) {
      console.log('❌ Missing required data');
      return;
    }
    
    setLoadingSlots(true);
    setTimeSlots([]);
    setLockError(null);
    
    try {
      const dateStr = format(selectedDate, 'yyyy-MM-dd');
      console.log('📞 API call:', { date: dateStr, service: bookingData.service, sessionId });
      
      const result = await api.getAvailability(dateStr, bookingData.service, sessionId);
      
      console.log('✅ API response:', result);
      
      setTimeSlots(result.slots || []);
      
      // Announce to screen reader
      const slotsCount = result.slots?.length || 0;
      onStepChange(`Valitsit päivän ${format(selectedDate, 'd. MMMM yyyy', { locale: fi })}. ${slotsCount} vapaata aikaa löytyi.`);
      
      // Scroll to time slots - WCAG 2.2: Predictable focus management
      if (slotsCount > 0) {
        setTimeout(() => {
          timeSlotsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          // Focus first available time slot for keyboard users
          const firstSlot = document.querySelector('[role="radiogroup"] button') as HTMLElement;
          firstSlot?.focus();
        }, 100);
      }
      
    } catch (error) {
      console.error('❌ Failed to load time slots:', error);
      onStepChange('Aikojen lataaminen epäonnistui. Yritä uudelleen.');
    } finally {
      setLoadingSlots(false);
    }
  };
  
  const handleDateSelect = (date: Date) => {
    setSelectedDate(date);
    setSelectedTime(undefined); // Reset time when date changes
    setBookingData({ 
      date: format(date, 'yyyy-MM-dd'),
      time: undefined 
    });
    
    // Fokusoi aikalista automaattisesti (WCAG 2.4.3 Focus Order)
    setTimeout(() => {
      const firstTimeSlot = timeSlotsRef.current?.querySelector('[role="option"]') as HTMLButtonElement;
      if (firstTimeSlot) {
        firstTimeSlot.focus();
      }
    }, 500); // Anna aikaa AJAX-lataukselle
  };
  
  const handleTimeSelect = async (time: string) => {
    if (!selectedDate) return;
    
    const dateStr = format(selectedDate, 'yyyy-MM-dd');
    
    // Unlock previous slot if exists
    if (lockedSlot && (lockedSlot.date !== dateStr || lockedSlot.time !== time)) {
      try {
        await api.unlockTimeslot(lockedSlot.date, lockedSlot.time, sessionId);
      } catch (error) {
        console.error('Failed to unlock previous slot:', error);
      }
    }
    
    setLockingSlot(true);
    setLockError(null);
    
    try {
      // Lock the selected timeslot
      const result = await api.lockTimeslot(dateStr, time, sessionId);
      
      if (result.success) {
        setSelectedTime(time);
        setBookingData({ time });
        setLockedSlot({ date: dateStr, time });
        onStepChange(`Aika ${time} lukittu sinulle 5 minuutiksi`);
      }
    } catch (error: any) {
      // Handle lock failure (someone else locked it)
      setLockError(error.message || 'Tämä aika varattiin juuri. Valitse toinen aika.');
      setSelectedTime(undefined);
      
      // Refresh timeslots to show updated locked status
      await loadTimeSlots();
      
      onStepChange('Aika ei ole enää saatavilla');
    } finally {
      setLockingSlot(false);
    }
  };
  
  const handleNext = () => {
    if (selectedDate && selectedTime) {
      nextStep();
    }
  };
  
  // Generate calendar days
  // Generoi päivät kalenteriin (koko viikot maanantaista alkaen)
  const monthStart = startOfMonth(currentMonth);
  const monthEnd = endOfMonth(currentMonth);
  const calendarStart = startOfWeek(monthStart, { weekStartsOn: 1 }); // 1 = Maanantai
  const calendarEnd = endOfWeek(monthEnd, { weekStartsOn: 1 });
  const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd });
  
  // Weekday headers
  const weekDays = ['Ma', 'Ti', 'Ke', 'To', 'Pe', 'La', 'Su'];
  
  // Helper: Check if date is disabled
  const isDateDisabled = (date: Date) => {
    const isPast = isBefore(date, startOfDay(new Date()));
    const isWeekend = date.getDay() === 0 || date.getDay() === 6;
    const isCurrentMonthDay = isSameMonth(date, currentMonth);
    return isPast || isWeekend || !isCurrentMonthDay;
  };
  
  // Keyboard navigation for calendar (WCAG 2.1.1 Keyboard)
  const handleCalendarKeyDown = (e: React.KeyboardEvent, date: Date) => {
    let newDate: Date | null = null;
    
    switch (e.key) {
      case 'ArrowRight':
        // Seuraava päivä
        newDate = new Date(date);
        newDate.setDate(newDate.getDate() + 1);
        e.preventDefault();
        break;
        
      case 'ArrowLeft':
        // Edellinen päivä
        newDate = new Date(date);
        newDate.setDate(newDate.getDate() - 1);
        e.preventDefault();
        break;
        
      case 'ArrowDown':
        // Viikko eteenpäin
        newDate = new Date(date);
        newDate.setDate(newDate.getDate() + 7);
        e.preventDefault();
        break;
        
      case 'ArrowUp':
        // Viikko taaksepäin
        newDate = new Date(date);
        newDate.setDate(newDate.getDate() - 7);
        e.preventDefault();
        break;
        
      case 'Home':
        // Viikon ensimmäinen päivä (maanantai)
        newDate = startOfWeek(date, { weekStartsOn: 1 });
        e.preventDefault();
        break;
        
      case 'End':
        // Viikon viimeinen päivä (sunnuntai)
        newDate = endOfWeek(date, { weekStartsOn: 1 });
        e.preventDefault();
        break;
        
      case 'PageUp':
        // Edellinen kuukausi
        if (e.shiftKey) {
          // Shift+PageUp = edellinen vuosi
          newDate = new Date(date);
          newDate.setFullYear(newDate.getFullYear() - 1);
        } else {
          newDate = subMonths(date, 1);
        }
        e.preventDefault();
        break;
        
      case 'PageDown':
        // Seuraava kuukausi
        if (e.shiftKey) {
          // Shift+PageDown = seuraava vuosi
          newDate = new Date(date);
          newDate.setFullYear(newDate.getFullYear() + 1);
        } else {
          newDate = addMonths(date, 1);
        }
        e.preventDefault();
        break;
        
      case 'Enter':
      case ' ':
        // Valitse päivä
        if (!isDateDisabled(date)) {
          handleDateSelect(date);
        }
        e.preventDefault();
        break;
    }
    
    // Päivitä fokus uuteen päivään (roving tabindex)
    if (newDate) {
      setFocusedDate(newDate);
      
      // Jos uusi päivä on eri kuukaudessa, vaihda kuukautta
      if (!isSameMonth(newDate, currentMonth)) {
        setCurrentMonth(newDate);
      }
      
      // Fokusoi uusi päivä DOM:ssa
      setTimeout(() => {
        const dateButton = calendarGridRef.current?.querySelector(
          `[data-date="${format(newDate, 'yyyy-MM-dd')}"]`
        ) as HTMLButtonElement;
        if (dateButton) {
          dateButton.focus();
        }
      }, 0);
    }
  };
  
  return (
    <div className="apple-card max-w-4xl mx-auto px-3 sm:px-6">
      <div className="text-center mb-6 sm:mb-8">
        <h1 className="text-3xl font-semibold text-apple-gray-900 mb-2" style={{ hyphens: 'auto', wordBreak: 'break-word' }}>
          Varaa ilmainen saavutettavuuskeskustelu
        </h1>
        
        <p className="text-apple-gray-600">
          Valitse sinulle sopiva aika kalenterista. Kesto 30 minuuttia.
        </p>
      </div>
      
      {/* Skip link to time slots when date selected */}
      {selectedDate && timeSlots.length > 0 && (
        <a 
          href="#time-slots-heading" 
          className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-apple-blue text-white px-4 py-2 rounded-lg z-50"
        >
          Siirry aikojen valintaan
        </a>
      )}
      
      {/* Calendar */}
      <div className="mb-8">
        {/* Month navigation */}
        <div className="mb-6">
          {/* Navigation buttons */}
          <div className="flex items-center justify-between gap-2 mb-3">
            <button
              type="button"
              onClick={() => setCurrentMonth(subMonths(currentMonth, 1))}
              className="apple-button-secondary py-3 px-6 flex-1 text-lg"
              aria-label="Edellinen kuukausi"
            >
              ←
            </button>
            
            <button
              type="button"
              onClick={() => setCurrentMonth(addMonths(currentMonth, 1))}
              className="apple-button-secondary py-3 px-6 flex-1 text-lg"
              aria-label="Seuraava kuukausi"
            >
              →
            </button>
          </div>
          
          {/* Month/Year display */}
          <h2 className="text-xl sm:text-2xl font-semibold text-apple-gray-900 text-center">
            {format(currentMonth, 'MMMM yyyy', { locale: fi })}
          </h2>
        </div>
        
        {/* Calendar grid */}
        <div 
          ref={calendarGridRef}
          role="grid" 
          aria-label={`Kalenteri: ${format(currentMonth, 'MMMM yyyy', { locale: fi })}`}
          className="bg-apple-gray-100 rounded-xl p-3 sm:p-6"
        >
          {/* Weekday headers */}
          <div className="grid grid-cols-7 gap-1 sm:gap-2 mb-3" role="row">
            {weekDays.map((day) => (
              <div 
                key={day} 
                role="columnheader"
                className="text-center text-xs sm:text-sm font-semibold text-apple-gray-900 py-2"
              >
                {day}
              </div>
            ))}
          </div>
          
          {/* Days */}
          {/* Group days into weeks (rows) for proper ARIA grid structure */}
          {Array.from({ length: Math.ceil(days.length / 7) }, (_, weekIndex) => {
            const weekStart = weekIndex * 7;
            const weekDays = days.slice(weekStart, weekStart + 7);
            
            return (
              <div key={`week-${weekIndex}`} className="grid grid-cols-7 gap-1 sm:gap-2 mb-1 sm:mb-2 last:mb-0" role="row">
                {weekDays.map((date) => {
                  const dateStr = format(date, 'yyyy-MM-dd');
                  const isSelected = selectedDate && isSameDay(date, selectedDate);
                  const isPast = isBefore(date, startOfDay(new Date()));
                  const isCurrentDay = isToday(date);
                  const isWeekend = date.getDay() === 0 || date.getDay() === 6;
                  const isCurrentMonthDay = isSameMonth(date, currentMonth);
                  const isDisabled = isPast || isWeekend || !isCurrentMonthDay;
                  const isFocused = focusedDate && isSameDay(date, focusedDate);
                  
                  return (
                    <button
                      key={dateStr}
                      type="button"
                      role="gridcell"
                      data-date={dateStr}
                      tabIndex={isFocused ? 0 : -1} // Roving tabindex
                      onClick={() => !isDisabled && handleDateSelect(date)}
                      onKeyDown={(e) => handleCalendarKeyDown(e, date)}
                      disabled={isDisabled}
                      aria-label={format(date, 'EEEE d. MMMM yyyy', { locale: fi })}
                      aria-selected={isSelected ? true : undefined}
                      aria-disabled={isDisabled ? true : undefined}
                      aria-current={isCurrentDay ? 'date' : undefined}
                      className={`
                        relative min-h-[48px] sm:min-h-[52px] min-w-[48px] sm:min-w-[52px] 
                        rounded-lg font-medium text-sm sm:text-base
                        transition-apple focus-ring
                        ${isSelected 
                          ? 'bg-apple-blue text-white shadow-apple ring-4 ring-apple-blue/30' 
                          : isDisabled
                            ? 'bg-gray-100 text-gray-500 cursor-not-allowed line-through'
                            : 'bg-white text-apple-gray-900 hover:bg-apple-blue/10 hover:shadow-apple'
                        }
                        ${isCurrentDay && !isSelected ? 'ring-2 ring-apple-blue font-bold' : ''}
                        ${!isCurrentMonthDay ? 'opacity-30' : ''} 
                      `}
                    >
                      {format(date, 'd')}
                      
                      {/* Valittu päivä: teksti screen readereille */}
                      {isSelected && (
                        <span className="sr-only">Valittu päivä</span>
                      )}
                      
                      {/* Tämän päivän indikaattori */}
                      {isCurrentDay && !isSelected && (
                        <>
                          <span className="absolute -top-1 -right-1 w-2 h-2 bg-apple-blue rounded-full" aria-hidden="true" />
                          <span className="sr-only">Tänään</span>
                        </>
                      )}
                    </button>
                  );
                })}
              </div>
            );
          })}
        </div>
      </div>
      
      {/* Time slots */}
      {selectedDate && (
        <div ref={timeSlotsRef} className="mb-8">
          <h2 id="time-slots-heading" className="text-2xl font-semibold text-apple-gray-900 mb-4 text-center">
            Valitse sopiva aika
          </h2>
          
          {/* Lock error notification (aria-live="polite") */}
          {lockError && (
            <div 
              role="status" 
              aria-live="polite" 
              aria-atomic="true"
              className="mb-4 p-4 bg-amber-50 border-2 border-amber-500 rounded-lg"
            >
              <div className="flex items-start gap-3">
                <svg 
                  className="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" 
                  fill="currentColor" 
                  viewBox="0 0 20 20"
                  aria-hidden="true"
                >
                  <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                </svg>
                <div>
                  <p className="font-semibold text-amber-900">
                    {lockError}
                  </p>
                  <p className="text-sm text-amber-800 mt-1">
                    Vapaita aikoja on vielä jäljellä. Valitse uusi aika alta.
                  </p>
                </div>
              </div>
            </div>
          )}
          
          {loadingSlots ? (
            <div className="text-center py-8" role="status" aria-live="polite">
              <div className="animate-spin w-8 h-8 border-4 border-apple-blue border-t-transparent rounded-full mx-auto"></div>
              <p className="mt-4 text-apple-gray-600">Ladataan vapaita aikoja...</p>
            </div>
          ) : timeSlots.length > 0 ? (
            <div 
              role="radiogroup" 
              aria-labelledby="time-slots-heading"
              className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 sm:gap-3"
            >
              {timeSlots.map((slot, index) => {
                const isSelected = selectedTime === slot.time;
                const isLocked = slot.locked || false;
                const isLastSlot = index === timeSlots.length - 1;
                
                return (
                  <button
                    key={slot.id}
                    type="button"
                    role="radio"
                    aria-checked={isSelected}
                    disabled={isLocked || lockingSlot}
                    onClick={() => !isLocked && handleTimeSelect(slot.time)}
                    onKeyDown={(e) => {
                      // Tab viimeisestä slotista hyppää submit-nappiin
                      if (e.key === 'Tab' && !e.shiftKey && isLastSlot && selectedTime) {
                        e.preventDefault();
                        document.getElementById('datetime-submit-button')?.focus();
                      }
                    }}
                    className={`
                      min-h-[52px] sm:min-h-[56px] px-4 py-3 rounded-lg font-medium text-base sm:text-lg
                      transition-apple focus-ring
                      ${isSelected 
                        ? 'bg-apple-blue text-white shadow-apple ring-4 ring-apple-blue/30' 
                        : isLocked
                          ? 'bg-gray-200 text-gray-500 cursor-not-allowed opacity-60'
                          : 'bg-apple-gray-100 text-apple-gray-900 hover:bg-apple-blue/10 hover:shadow-apple'
                      }
                    `}
                  >
                    {slot.time}
                    {isLocked && (
                      <span className="ml-2" aria-hidden="true">🔒</span>
                    )}
                    {isSelected && (
                      <span className="sr-only">Valittu aika</span>
                    )}
                  </button>
                );
              })}
            </div>
          ) : (
            <p className="text-center py-8 text-apple-gray-600" role="status">
              Ei vapaita aikoja tälle päivälle. Valitse toinen päivä.
            </p>
          )}
        </div>
      )}
      
      {/* Navigation */}
      <div className="flex items-center justify-between gap-3 mt-8">
        <button
          type="button"
          onClick={previousStep}
          className="apple-button-secondary flex-1 py-3 flex items-center justify-center"
          aria-label="Palaa palveluvalintaan"
        >
          ← Edellinen
        </button>
        
        <button
          id="datetime-submit-button"
          type="button"
          onClick={handleNext}
          disabled={!selectedDate || !selectedTime}
          className="apple-button-primary disabled:opacity-50 disabled:cursor-not-allowed flex-1 py-3 flex items-center justify-center"
          aria-label="Jatka yhteystietoihin"
        >
          Seuraava →
        </button>
      </div>
    </div>
  );
};

export default DateTimeSelection;
