import React, { useEffect, useState } from 'react';
import { useBooking } from '../contexts/BookingContext';
import { api, Service } from '../utils/api';

interface ServiceSelectionProps {
  onStepChange: (message: string) => void;
}

const ServiceSelection: React.FC<ServiceSelectionProps> = ({ onStepChange }) => {
  const { bookingData, setBookingData, nextStep } = useBooking();
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedService, setSelectedService] = useState<string | undefined>(bookingData.service);
  
  useEffect(() => {
    loadServices();
    onStepChange('Vaihe 1: Valitse palvelu');
  }, []);
  
  const loadServices = async () => {
    try {
      const data = await api.getServices();
      setServices(data);
    } catch (error) {
      console.error('Failed to load services:', error);
    } finally {
      setLoading(false);
    }
  };
  
  const handleServiceSelect = (serviceId: string) => {
    setSelectedService(serviceId);
    setBookingData({ service: serviceId });
    
    const service = services.find(s => s.id === serviceId);
    onStepChange(`Valitsit palvelun: ${service?.name}`);
  };
  
  const handleNext = () => {
    if (selectedService) {
      nextStep();
    }
  };
  
  if (loading) {
    return (
      <div className="text-center py-12" role="status" aria-live="polite">
        <div className="animate-spin w-12 h-12 border-4 border-apple-blue border-t-transparent rounded-full mx-auto"></div>
        <p className="mt-4 text-apple-gray-600">Ladataan palveluita...</p>
      </div>
    );
  }
  
  return (
    <div className="apple-card max-w-2xl mx-auto">
      <div className="text-center mb-8">
        <h1 className="text-3xl font-semibold text-apple-gray-900 mb-2">
          Milloin keskustellaan saavutettavuudesta?
        </h1>
        
        <p className="text-apple-gray-600">
          Valitse palvelu, joka vastaa tarpeitasi parhaiten.
        </p>
      </div>
      
      {/* Service cards */}
      <div 
        role="radiogroup" 
        aria-labelledby="service-selection-heading"
        className="space-y-4"
      >
        <h2 id="service-selection-heading" className="sr-only">Saatavilla olevat palvelut</h2>
        
        {services.map((service) => {
          const isSelected = selectedService === service.id;
          
          return (
            <button
              key={service.id}
              type="button"
              role="radio"
              aria-checked={isSelected}
              onClick={() => handleServiceSelect(service.id)}
              className={`
                w-full text-left p-6 rounded-xl border-2 transition-apple
                min-h-[88px] /* WCAG 2.2: Double the minimum target size for better UX */
                ${isSelected 
                  ? 'border-apple-blue bg-apple-blue/5 shadow-apple' 
                  : 'border-gray-200 hover:border-apple-blue/50 hover:shadow-apple'
                }
                focus-ring
              `}
            >
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <h3 className="text-xl font-semibold text-apple-gray-900 mb-2">
                    {service.name}
                  </h3>
                  
                  {service.description && (
                    <p className="text-apple-gray-600 text-sm mb-3">
                      {service.description}
                    </p>
                  )}
                  
                  <div className="flex items-center space-x-4 text-sm">
                    <span className="font-semibold text-apple-gray-900 text-base flex items-center">
                      <svg className="w-4 h-4 mr-1.5 text-apple-green" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                      </svg>
                      Ilmainen konsultaatio
                    </span>
                    <span className="text-apple-gray-600">•</span>
                    <span className="text-apple-gray-600">{service.duration} min</span>
                  </div>
                  
                  <p className="text-xs text-apple-gray-600 mt-2">
                    Palvelun hinta: {service.price} € (laskutetaan erikseen)
                  </p>
                </div>
                
                {/* Checkmark indicator */}
                <div 
                  className={`
                    w-6 h-6 rounded-full flex items-center justify-center ml-4
                    ${isSelected ? 'bg-apple-blue' : 'border-2 border-gray-300'}
                  `}
                  aria-hidden="true"
                >
                  {isSelected && (
                    <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  )}
                </div>
              </div>
            </button>
          );
        })}
      </div>
      
      {/* Navigation */}
      <div className="mt-8 flex justify-end">
        <button
          type="button"
          onClick={handleNext}
          disabled={!selectedService}
          className="apple-button-primary disabled:opacity-50 disabled:cursor-not-allowed"
          aria-label="Jatka aikavalinnan"
        >
          Jatka →
        </button>
      </div>
    </div>
  );
};

export default ServiceSelection;
