import { createContext, useContext, useState, ReactNode } from 'react';
import { getSessionId } from '../utils/session';

interface BookingData {
  service?: string;
  serviceName?: string;
  date?: string;
  time?: string;
  customerName?: string;
  customerEmail?: string;
  customerPhone?: string;
  customerMessage?: string;
  meetingPlatform?: 'google-meet' | 'microsoft-teams';
}

interface BookingContextType {
  bookingData: BookingData;
  currentStep: number;
  sessionId: string;
  lockedSlot: { date: string; time: string } | null;
  setBookingData: (data: Partial<BookingData>) => void;
  setLockedSlot: (slot: { date: string; time: string } | null) => void;
  nextStep: () => void;
  previousStep: () => void;
  resetBooking: () => void;
}

const BookingContext = createContext<BookingContextType | undefined>(undefined);

export function BookingProvider({ children }: { children: ReactNode }) {
  const [bookingData, setBookingDataState] = useState<BookingData>({});
  const [currentStep, setCurrentStep] = useState(1);
  const [sessionId] = useState(() => getSessionId());
  const [lockedSlot, setLockedSlot] = useState<{ date: string; time: string } | null>(null);
  
  const setBookingData = (data: Partial<BookingData>) => {
    setBookingDataState(prev => ({ ...prev, ...data }));
  };
  
  const nextStep = () => {
    setCurrentStep(prev => Math.min(prev + 1, 3));
  };
  
  const previousStep = () => {
    setCurrentStep(prev => Math.max(prev - 1, 1));
  };
  
  const resetBooking = () => {
    setBookingDataState({});
    setCurrentStep(1);
    setLockedSlot(null);
  };
  
  return (
    <BookingContext.Provider
      value={{
        bookingData,
        currentStep,
        sessionId,
        lockedSlot,
        setBookingData,
        setLockedSlot,
        nextStep,
        previousStep,
        resetBooking,
      }}
    >
      {children}
    </BookingContext.Provider>
  );
}

export function useBooking() {
  const context = useContext(BookingContext);
  if (!context) {
    throw new Error('useBooking must be used within BookingProvider');
  }
  return context;
}
