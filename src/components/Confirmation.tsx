import React, { useEffect, useState, useRef } from 'react';
import { useBooking } from '../contexts/BookingContext';
import { api } from '../utils/api';
import { format } from 'date-fns';
import { fi } from 'date-fns/locale';

const Confirmation: React.FC = () => {
  const { bookingData, sessionId, resetBooking } = useBooking();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [booking, setBooking] = useState<any>(null);
  const [showConfetti, setShowConfetti] = useState(false);
  
  const confirmationRef = useRef<HTMLDivElement>(null);
  
  // Generate random booking code (e.g., AYE3, K7MQ) - NOT sequential
  const generateBookingCode = (id: number): string => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No confusing chars (0,O,I,1)
    
    // Use ID as seed for consistent code per booking, but randomize the pattern
    const seed = id * 7919; // Prime number for better distribution
    let code = '';
    
    for (let i = 0; i < 4; i++) {
      const index = (seed * (i + 1) * 31) % chars.length;
      code += chars[index];
    }
    
    return code;
  };
  
  useEffect(() => {
    createBooking();
  }, []);
  
  useEffect(() => {
    if (booking) {
      // Focus on confirmation message - WCAG 2.2
      confirmationRef.current?.focus();
      
      // Show confetti animation (if motion is allowed)
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (!prefersReducedMotion) {
        setShowConfetti(true);
        setTimeout(() => setShowConfetti(false), 3000);
      }
    }
  }, [booking]);
  
  const createBooking = async () => {
    try {
      const result = await api.createBooking({
        service_type: bookingData.service,
        booking_date: bookingData.date,
        booking_time: bookingData.time,
        customer_name: bookingData.customerName,
        customer_email: bookingData.customerEmail,
        customer_phone: bookingData.customerPhone,
        customer_message: bookingData.customerMessage,
        meeting_platform: bookingData.meetingPlatform || 'google-meet',
        session_id: sessionId,
      });
      
      setBooking(result.booking);
    } catch (err: any) {
      console.error('Booking creation failed:', err);
      const errorMessage = err.message || 'Varauksen luonti epäonnistui';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };
  
  const handleAddToCalendar = (type: 'google' | 'apple' | 'outlook') => {
    if (!booking) return;
    
    const title = 'WCAG-konsultaatio - WP Saavutettavuus';
    const description = `Videokeskustelu saavutettavuudesta\n\nTapaamislinkki: ${booking.meeting_link || 'Lähetetään sähköpostilla'}`;
    const location = booking.meeting_link || 'Google Meet';
    
    const startDate = new Date(`${booking.booking_date}T${booking.booking_time}`);
    const endDate = new Date(startDate.getTime() + (30 * 60000)); // 30 min
    
    const formatGoogleDate = (date: Date) => {
      return date.toISOString().replace(/-|:|\.\d+/g, '');
    };
    
    let url = '';
    
    switch (type) {
      case 'google':
        url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(title)}&dates=${formatGoogleDate(startDate)}/${formatGoogleDate(endDate)}&details=${encodeURIComponent(description)}&location=${encodeURIComponent(location)}`;
        break;
        
      case 'apple':
        // Improved ICS file for Apple Calendar
        const ics = `BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//WP Saavutettavuus//Booking System//FI
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:booking-${booking.id}@wpsaavutettavuus.fi
DTSTAMP:${formatGoogleDate(new Date())}
DTSTART:${formatGoogleDate(startDate)}
DTEND:${formatGoogleDate(endDate)}
SUMMARY:${title}
DESCRIPTION:${description.replace(/\n/g, '\\n')}
LOCATION:${location}
STATUS:CONFIRMED
SEQUENCE:0
BEGIN:VALARM
TRIGGER:-PT30M
DESCRIPTION:Tapaaminen alkaa 30 minuutin päästä
ACTION:DISPLAY
END:VALARM
END:VEVENT
END:VCALENDAR`;
        const blob = new Blob([ics], { type: 'text/calendar;charset=utf-8' });
        url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `wcag-konsultaatio-${booking.booking_date}.ics`;
        a.click();
        URL.revokeObjectURL(url);
        return;
        
      case 'outlook':
        // Works for both Outlook.com and Office 365
        url = `https://outlook.live.com/calendar/0/deeplink/compose?subject=${encodeURIComponent(title)}&startdt=${startDate.toISOString()}&enddt=${endDate.toISOString()}&body=${encodeURIComponent(description)}&location=${encodeURIComponent(location)}`;
        break;
    }
    
    window.open(url, '_blank');
  };
  
  if (loading) {
    return (
      <div className="apple-card max-w-2xl mx-auto text-center py-12" role="status" aria-live="polite">
        <div className="animate-spin w-16 h-16 border-4 border-apple-blue border-t-transparent rounded-full mx-auto"></div>
        <p className="mt-6 text-xl text-apple-gray-600">Luodaan varausta...</p>
      </div>
    );
  }
  
  if (error) {
    return (
      <div className="apple-card max-w-2xl mx-auto" role="alert">
        <div className="text-center py-8">
          <div className="w-16 h-16 bg-apple-red/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-apple-red" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
          </div>
          
          <h1 className="text-2xl font-semibold text-apple-gray-900 mb-2">
            Varaus epäonnistui
          </h1>
          
          <p className="text-apple-gray-600 mb-6">
            {error}
          </p>
          
          <button
            onClick={() => window.location.reload()}
            className="apple-button-primary"
          >
            Yritä uudelleen
          </button>
        </div>
      </div>
    );
  }
  
  const formattedDate = booking ? format(new Date(booking.booking_date), 'd. MMMM yyyy', { locale: fi }) : '';
  const formattedTime = booking?.booking_time || '';
  
  return (
    <div className="apple-card max-w-2xl mx-auto relative overflow-hidden px-3 sm:px-6">
      {/* Confetti animation (subtle, accessible) */}
      {showConfetti && (
        <div className="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
          {[...Array(20)].map((_, i) => (
            <div
              key={i}
              className="absolute w-2 h-2 rounded-full bg-apple-blue animate-bounce"
              style={{
                left: `${Math.random() * 100}%`,
                top: '-10px',
                animationDelay: `${Math.random() * 2}s`,
                animationDuration: `${2 + Math.random() * 2}s`,
              }}
            />
          ))}
        </div>
      )}
      
      <div 
        ref={confirmationRef}
        tabIndex={-1}
        className="text-center py-8 focus:outline-none"
        role="status"
        aria-live="polite"
        aria-atomic="true"
      >
        {/* Success icon - WCAG compliant green */}
        <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6" aria-hidden="true">
          <svg className="w-10 h-10 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Onnistui">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
          </svg>
        </div>
        
        <h1 className="text-3xl font-semibold text-apple-gray-900 mb-2">
          ✓ Varaus vahvistettu!
        </h1>
        
        <p className="text-xl font-medium text-gray-700 mb-8">
          Nähdään {formattedDate} klo {formattedTime}
        </p>
        
        {/* Booking details */}
        <div className="bg-apple-gray-100 rounded-xl p-6 mb-8 text-left">
          <h2 className="text-lg font-semibold text-apple-gray-900 mb-4">Varauksen tiedot</h2>
          
          <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-3">
            <dt className="text-apple-gray-600">Varaustunnus:</dt>
            <dd className="font-semibold text-apple-gray-900 font-mono">{generateBookingCode(booking.id)}</dd>
            
            <dt className="text-apple-gray-600">Päivämäärä:</dt>
            <dd className="font-semibold text-apple-gray-900">{formattedDate}</dd>
            
            <dt className="text-apple-gray-600">Kellonaika:</dt>
            <dd className="font-semibold text-apple-gray-900">{formattedTime}</dd>
            
            <dt className="text-apple-gray-600">Sähköposti:</dt>
            <dd className="font-semibold text-apple-gray-900 break-all">{booking.customer_email}</dd>
          </dl>
        </div>
        
        {/* Meeting link - only show if meeting is within 2 days */}
        {booking.meeting_link && (() => {
          const meetingDate = new Date(`${booking.booking_date}T${booking.booking_time}`);
          const now = new Date();
          const daysUntilMeeting = Math.ceil((meetingDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
          
          // Only show link if meeting is within 2 days (today, tomorrow, or day after)
          if (daysUntilMeeting <= 2 && daysUntilMeeting >= 0) {
            return (
              <a
                href={booking.meeting_link}
                target="_blank"
                rel="noopener noreferrer"
                className="apple-button-primary inline-block mb-4"
                aria-label="Liity videotapaamiseen (avautuu uuteen ikkunaan)"
              >
                📹 Liity tapaamiseen
              </a>
            );
          }
          return null;
        })()}
        
        {/* Add to calendar */}
        <div className="mb-8">
          <p className="text-sm text-apple-gray-600 mb-3">Lisää kalenteriin:</p>
          <div className="flex justify-center gap-3 flex-wrap">
            <button
              onClick={() => handleAddToCalendar('google')}
              className="apple-button-secondary text-sm py-2 px-4"
              aria-label="Lisää Google Kalenteriin"
            >
              Google Calendar
            </button>
            <button
              onClick={() => handleAddToCalendar('outlook')}
              className="apple-button-secondary text-sm py-2 px-4"
              aria-label="Lisää Outlook-kalenteriin"
            >
              Outlook
            </button>
            <button
              onClick={() => handleAddToCalendar('apple')}
              className="apple-button-secondary text-sm py-2 px-4"
              aria-label="Lataa .ics-tiedosto Apple Kalenteriin"
            >
              Apple Calendar (.ics)
            </button>
          </div>
          <p className="text-sm text-gray-700 mt-3 font-medium">
            💡 Saat myös sähköpostiin kalenterikutsun automaattisesti
          </p>
        </div>
        
        {/* Next steps */}
        <div className="bg-apple-blue/5 rounded-xl p-6 text-left">
          <h2 className="font-semibold text-apple-gray-900 mb-3">Valmistaudu tapaamiseen:</h2>
          <ul className="space-y-2 text-apple-gray-600">
            <li className="flex items-start">
              <svg className="w-5 h-5 text-apple-green mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
              </svg>
              Saat vahvistussähköpostin tapaamislinkillä ja kalenterikutsun
            </li>
            <li className="flex items-start">
              <svg className="w-5 h-5 text-apple-green mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
              </svg>
              Mieti 2–3 tärkeintä kysymystä sivustosi saavutettavuudesta
            </li>
            <li className="flex items-start">
              <svg className="w-5 h-5 text-apple-green mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
              </svg>
              Voit halutessasi jakaa sivustosi osoitteen etukäteen
            </li>
            <li className="flex items-start">
              <svg className="w-5 h-5 text-apple-green mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
              </svg>
              Tarkista että kamera ja mikrofoni toimivat ennen tapaamista
            </li>
          </ul>
        </div>
        
        {/* New booking */}
        <button
          onClick={resetBooking}
          className="mt-8 apple-button-secondary px-8 py-3 w-full sm:w-auto flex items-center justify-center gap-2"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Aloita alusta
        </button>
      </div>
    </div>
  );
};

export default Confirmation;
