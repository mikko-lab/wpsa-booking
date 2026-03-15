import { BookingProvider } from './contexts/BookingContext';
import BookingWizard from './components/BookingWizard';

function App() {
  return (
    <BookingProvider>
      <div className="wpsa-booking-app" role="application" aria-label="Varausjärjestelmä">
        {/* Skip link - WCAG 2.2 */}
        <a 
          href="#booking-main" 
          className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-apple-blue text-white px-4 py-2 rounded-lg z-50"
        >
          Siirry varauslomakkeeseen
        </a>
        
        <BookingWizard />
      </div>
    </BookingProvider>
  );
}

export default App;
