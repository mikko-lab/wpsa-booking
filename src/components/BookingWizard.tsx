import React, { useState, useEffect } from 'react';
import { useBooking } from '../contexts/BookingContext';
import ProgressIndicator from './ProgressIndicator';
import DateTimeSelection from './DateTimeSelection';
import CustomerDetails from './CustomerDetails';
import Confirmation from './Confirmation';

const BookingWizard: React.FC = () => {
  const { currentStep, setBookingData } = useBooking();
  const [liveRegionMessage, setLiveRegionMessage] = useState('');
  
  // Set default service whenever returning to step 1
  useEffect(() => {
    if (currentStep === 1) {
      setBookingData({ 
        service: 'quick-consultation',
        serviceName: 'Ilmainen saavutettavuuskeskustelu'
      });
    }
  }, [currentStep]);
  
  const renderStep = () => {
    switch (currentStep) {
      case 1:
        return <DateTimeSelection onStepChange={setLiveRegionMessage} />;
      case 2:
        return <CustomerDetails onStepChange={setLiveRegionMessage} />;
      case 3:
        return <Confirmation />;
      default:
        return <DateTimeSelection onStepChange={setLiveRegionMessage} />;
    }
  };
  
  return (
    <div className="max-w-4xl mx-auto py-8 px-3">
      {/* Live region for screen readers - WCAG 2.2 */}
      <div 
        role="status" 
        aria-live="polite" 
        aria-atomic="true" 
        className="sr-only"
      >
        {liveRegionMessage}
      </div>
      
      {/* Progress indicator - only show when not on confirmation */}
      {currentStep < 3 && <ProgressIndicator />}
      
      {/* Main content */}
      <div id="booking-main" role="region" aria-label="Varauslomake" className="mt-8">
        {renderStep()}
      </div>
    </div>
  );
};

export default BookingWizard;
