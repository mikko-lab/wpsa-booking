import React from 'react';
import { useBooking } from '../contexts/BookingContext';

const ProgressIndicator: React.FC = () => {
  const { currentStep } = useBooking();
  
  const steps = [
    { number: 1, label: 'Aika' },
    { number: 2, label: 'Tiedot' },
  ];
  
  return (
    <nav aria-label="Varauksen vaiheet" className="mb-8">
      <ol className="flex items-center justify-center space-x-4">
        {steps.map((step, index) => {
          const isActive = currentStep === step.number;
          const isCompleted = currentStep > step.number;
          
          return (
            <li key={step.number} className="flex items-center">
              {/* Step indicator */}
              <div className="flex flex-col items-center">
                <div
                  className={`
                    w-12 h-12 rounded-full flex items-center justify-center
                    font-bold text-lg transition-apple
                    ${isActive ? 'bg-apple-blue text-white ring-4 ring-apple-blue/30' : ''}
                    ${isCompleted ? 'bg-apple-green text-white' : ''}
                    ${!isActive && !isCompleted ? 'bg-gray-300 text-gray-800' : ''}
                  `}
                  aria-current={isActive ? 'step' : undefined}
                >
                  {isCompleted ? (
                    <svg 
                      className="w-6 h-6" 
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                      role="img"
                      aria-label="Valmis"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                  ) : (
                    step.number
                  )}
                </div>
                
                {/* Step label */}
                <span 
                  className={`
                    mt-2 text-sm transition-apple
                    ${isActive ? 'text-apple-blue font-bold underline decoration-2 underline-offset-4' : ''}
                    ${isCompleted ? 'text-apple-green font-semibold' : ''}
                    ${!isActive && !isCompleted ? 'text-gray-600 font-medium' : ''}
                  `}
                >
                  {step.label}
                </span>
              </div>
              
              {/* Connector line */}
              {index < steps.length - 1 && (
                <div 
                  className={`
                    w-16 h-1 mx-4 transition-apple
                    ${isCompleted ? 'bg-apple-green' : 'bg-gray-300'}
                  `}
                  aria-hidden="true"
                />
              )}
            </li>
          );
        })}
      </ol>
      
      {/* Screen reader announcement */}
      <div className="sr-only" role="status" aria-live="polite">
        Vaihe {currentStep} / {steps.length}: {steps[currentStep - 1]?.label}
      </div>
    </nav>
  );
};

export default ProgressIndicator;
