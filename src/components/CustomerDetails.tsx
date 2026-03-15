import React, { useState, useEffect } from 'react';
import { useBooking } from '../contexts/BookingContext';

interface CustomerDetailsProps {
  onStepChange: (message: string) => void;
}

interface FormErrors {
  name?: string;
  email?: string;
  phone?: string;
}

const CustomerDetails: React.FC<CustomerDetailsProps> = ({ onStepChange }) => {
  const { bookingData, setBookingData, nextStep, previousStep } = useBooking();
  
  const [name, setName] = useState(bookingData.customerName || '');
  const [email, setEmail] = useState(bookingData.customerEmail || '');
  const [phone, setPhone] = useState(bookingData.customerPhone || '');
  const [message, setMessage] = useState(bookingData.customerMessage || '');
  const [meetingPlatform, setMeetingPlatform] = useState<'google-meet' | 'microsoft-teams'>('google-meet');
  const [errors, setErrors] = useState<FormErrors>({});
  const [touched, setTouched] = useState<{[key: string]: boolean}>({});
  
  useEffect(() => {
    onStepChange('Vaihe 2: Täytä yhteystiedot');
    
    // Auto-focus nimi-kenttään kun sivu latautuu
    setTimeout(() => {
      document.getElementById('name')?.focus();
    }, 100);
  }, []);
  
  // THE GRACEFUL RECOVERY - Real-time validation
  const validateField = (field: string, value: string): string | undefined => {
    switch (field) {
      case 'name':
        if (!value.trim()) {
          return 'Nimi on pakollinen';
        }
        if (value.trim().length < 2) {
          return 'Nimi on liian lyhyt';
        }
        break;
        
      case 'email':
        if (!value.trim()) {
          return 'Sähköpostiosoite on pakollinen';
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          return 'Anna kelvollinen sähköpostiosoite muodossa nimi@example.fi';
        }
        break;
        
      case 'phone':
        if (value && !/^[+\d\s\-()]+$/.test(value)) {
          return 'Puhelinnumero saa sisältää vain numeroita, välilyöntejä ja erikoismerkkejä (+, -, (, ))';
        }
        break;
    }
    return undefined;
  };
  
  const handleBlur = (field: string) => {
    setTouched({ ...touched, [field]: true });
    
    const value = field === 'name' ? name : field === 'email' ? email : phone;
    const error = validateField(field, value);
    
    // Päivitä virheet: jos ei virhettä, poista avain kokonaan
    if (error) {
      setErrors({ ...errors, [field]: error });
    } else {
      const newErrors = { ...errors };
      delete newErrors[field as keyof FormErrors];
      setErrors(newErrors);
    }
  };
  
  const handleChange = (field: string, value: string) => {
    // Update value
    switch (field) {
      case 'name':
        setName(value);
        break;
      case 'email':
        setEmail(value);
        break;
      case 'phone':
        setPhone(value);
        break;
      case 'message':
        setMessage(value);
        break;
    }
    
    // Validoi VAIN jos kenttä on jo touched (käyttäjä on jo kokeillut lähettää)
    if (touched[field]) {
      const error = validateField(field, value);
      
      // Päivitä virheet: jos ei virhettä, poista avain kokonaan
      if (error) {
        setErrors({ ...errors, [field]: error });
      } else {
        const newErrors = { ...errors };
        delete newErrors[field as keyof FormErrors];
        setErrors(newErrors);
      }
    }
  };
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate all fields
    const nameError = validateField('name', name);
    const emailError = validateField('email', email);
    const phoneError = validateField('phone', phone);
    
    const newErrors: FormErrors = {};
    if (nameError) newErrors.name = nameError;
    if (emailError) newErrors.email = emailError;
    if (phoneError) newErrors.phone = phoneError;
    
    setErrors(newErrors);
    setTouched({ name: true, email: true, phone: true });
    
    // If errors, announce to screen reader and focus first error
    if (Object.keys(newErrors).length > 0) {
      const firstErrorField = Object.keys(newErrors)[0];
      onStepChange(`Lomakkeessa on ${Object.keys(newErrors).length} virhettä. ${newErrors[firstErrorField as keyof FormErrors]}`);
      
      // Focus first error field
      document.getElementById(firstErrorField)?.focus();
      return;
    }
    
    // Save data and move to next step
    setBookingData({
      customerName: name,
      customerEmail: email,
      customerPhone: phone || undefined,
      customerMessage: message || undefined,
      meetingPlatform: meetingPlatform,
    });
    
    nextStep();
  };
  
  const hasErrors = Object.keys(errors).length > 0;
  
  return (
    <div className="apple-card max-w-2xl mx-auto px-3 sm:px-6">
      <h1 className="text-3xl font-semibold text-apple-gray-900 mb-2">
        Kerro itsestäsi
      </h1>
      
      <p className="text-apple-gray-600 mb-8">
        Tarvitsemme yhteystietosi varauksen vahvistamiseksi.
      </p>
      
      {/* Error summary - WCAG 2.2 3.3.1 - Näytetään vain kun submit yritetty */}
      {hasErrors && (touched.name || touched.email || touched.phone) && (
        <div 
          role="alert" 
          aria-live="assertive"
          className="mb-6 p-4 bg-apple-red/10 border-2 border-apple-red rounded-lg"
        >
          <h2 className="text-apple-red font-semibold mb-2 flex items-center">
            <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
            Lomakkeessa on {Object.keys(errors).length} virhettä
          </h2>
          <ul className="list-disc list-inside space-y-1">
            {Object.entries(errors)
              .filter(([_, error]) => error !== undefined && error !== '')
              .map(([field, error]) => (
                <li key={field}>
                  <a 
                    href={`#${field}`} 
                    className="text-apple-red hover:underline"
                    onClick={(e) => {
                      e.preventDefault();
                      document.getElementById(field)?.focus();
                    }}
                  >
                    {error}
                  </a>
                </li>
              ))}
          </ul>
        </div>
      )}
      
      <form onSubmit={handleSubmit} noValidate>
        {/* Name field */}
        <div className="mb-6">
          <label htmlFor="name" className="apple-label">
            Nimesi <span className="text-apple-red" aria-label="pakollinen">*</span>
          </label>
          <input
            type="text"
            id="name"
            value={name}
            onChange={(e) => handleChange('name', e.target.value)}
            onBlur={() => handleBlur('name')}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('email')?.focus();
              }
            }}
            aria-required="true"
            aria-invalid={errors.name ? 'true' : 'false'}
            aria-describedby={errors.name ? 'name-error' : undefined}
            className={`
              apple-input
              ${errors.name && touched.name ? 'border-apple-red ring-apple-red/20' : ''}
            `}
          />
          {errors.name && touched.name && (
            <p id="name-error" className="apple-error" role="alert">
              {errors.name}
            </p>
          )}
        </div>
        
        {/* Email field */}
        <div className="mb-6">
          <label htmlFor="email" className="apple-label">
            Sähköpostiosoite <span className="text-apple-red" aria-label="pakollinen">*</span>
          </label>
          <input
            type="email"
            id="email"
            value={email}
            onChange={(e) => handleChange('email', e.target.value)}
            onBlur={() => handleBlur('email')}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('phone')?.focus();
              }
            }}
            aria-required="true"
            aria-invalid={errors.email ? 'true' : 'false'}
            aria-describedby={errors.email ? 'email-error' : 'email-help'}
            autoComplete="email"
            className={`
              apple-input
              ${errors.email && touched.email ? 'border-apple-red ring-apple-red/20' : ''}
            `}
          />
          {errors.email && touched.email ? (
            <p id="email-error" className="apple-error" role="alert">
              {errors.email}
            </p>
          ) : (
            <p id="email-help" className="text-sm text-apple-gray-600 mt-1">
              Lähetämme vahvistuksen tähän osoitteeseen
            </p>
          )}
        </div>
        
        {/* Phone field (optional) */}
        <div className="mb-6">
          <label htmlFor="phone" className="apple-label">
            Puhelinnumero <span className="text-apple-gray-600 text-sm">(valinnainen)</span>
          </label>
          <input
            type="tel"
            id="phone"
            value={phone}
            onChange={(e) => handleChange('phone', e.target.value)}
            onBlur={() => handleBlur('phone')}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('message')?.focus();
              }
            }}
            aria-invalid={errors.phone ? 'true' : 'false'}
            aria-describedby={errors.phone ? 'phone-error' : undefined}
            autoComplete="tel"
            className={`
              apple-input
              ${errors.phone && touched.phone ? 'border-apple-red ring-apple-red/20' : ''}
            `}
          />
          {errors.phone && touched.phone && (
            <p id="phone-error" className="apple-error" role="alert">
              {errors.phone}
            </p>
          )}
        </div>
        
        {/* Message field (optional) - SIIRRETTY TÄHÄN */}
        <div className="mb-8">
          <label htmlFor="message" className="apple-label">
            Kerro lyhyesti tarpeestasi <span className="text-apple-gray-600 text-sm">(valinnainen)</span>
          </label>
          <textarea
            id="message"
            value={message}
            onChange={(e) => handleChange('message', e.target.value)}
            rows={4}
            maxLength={500}
            className="apple-input resize-none"
            placeholder="Esim. Tarvitsen apua verkkokauppani saavutettavuuden parantamisessa..."
          />
          <p className="text-sm text-apple-gray-600 mt-1">
            {message.length} / 500 merkkiä
          </p>
        </div>
        
        {/* Meeting platform selection */}
        <div className="mb-8">
          <label className="apple-label mb-4 block">
            Tapaamisen muoto
          </label>
          
          <div role="radiogroup" aria-labelledby="platform-heading" className="space-y-3">
            <h3 id="platform-heading" className="sr-only">Valitse tapaamisen alusta</h3>
            
            {/* Google Meet */}
            <button
              type="button"
              role="radio"
              aria-checked={meetingPlatform === 'google-meet'}
              onClick={() => setMeetingPlatform('google-meet')}
              className={`
                w-full text-left p-4 rounded-lg border-2 transition-apple
                ${meetingPlatform === 'google-meet' 
                  ? 'border-apple-blue bg-apple-blue/5' 
                  : 'border-gray-200 hover:border-apple-blue/50'
                }
                focus-ring
              `}
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-semibold text-apple-gray-900">Google Meet</p>
                  <p className="text-sm text-apple-gray-600">Automaattinen linkki sähköpostiin</p>
                </div>
                <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center
                  ${meetingPlatform === 'google-meet' ? 'border-apple-blue bg-apple-blue' : 'border-gray-300'}`}>
                  {meetingPlatform === 'google-meet' && (
                    <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                  )}
                </div>
              </div>
            </button>
            
            {/* Microsoft Teams */}
            <button
              type="button"
              role="radio"
              aria-checked={meetingPlatform === 'microsoft-teams'}
              onClick={() => setMeetingPlatform('microsoft-teams')}
              className={`
                w-full text-left p-4 rounded-lg border-2 transition-apple
                ${meetingPlatform === 'microsoft-teams' 
                  ? 'border-apple-blue bg-apple-blue/5' 
                  : 'border-gray-200 hover:border-apple-blue/50'
                }
                focus-ring
              `}
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-semibold text-apple-gray-900">Microsoft Teams</p>
                  <p className="text-sm text-apple-gray-600">Automaattinen linkki sähköpostiin</p>
                </div>
                <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center
                  ${meetingPlatform === 'microsoft-teams' ? 'border-apple-blue bg-apple-blue' : 'border-gray-300'}`}>
                  {meetingPlatform === 'microsoft-teams' && (
                    <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                  )}
                </div>
              </div>
            </button>
          </div>
        </div>
        
        {/* Navigation */}
        <div className="flex items-center justify-between gap-3">
          <button
            type="button"
            onClick={previousStep}
            className="apple-button-secondary flex-1 py-3 flex items-center justify-center"
            aria-label="Palaa ajanvalintaan"
          >
            ← Edellinen
          </button>
          
          <button
            type="submit"
            className="apple-button-primary flex-1 py-3 flex items-center justify-center"
            aria-label="Vahvista varaus"
          >
            Vahvista varaus
          </button>
        </div>
      </form>
    </div>
  );
};

export default CustomerDetails;
