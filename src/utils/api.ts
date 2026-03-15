// WordPress REST API helper
declare const wpsaBooking: {
  restUrl: string;
  nonce: string;
  services: any[];
  workingHours: any;
  locale: string;
};

export interface TimeSlot {
  id: string;
  time: string;
  end: string;
  label: string;
  locked?: boolean; // Locked by another user
}

export interface Service {
  id: string;
  name: string;
  price: number;
  duration: number;
  description?: string;
}

export interface BookingResponse {
  success: boolean;
  booking: any;
  message: string;
}

export interface LockResponse {
  success: boolean;
  message: string;
  expires_in?: number;
  code?: string;
}

class API {
  private baseUrl: string;
  private nonce: string;
  
  constructor() {
    this.baseUrl = wpsaBooking.restUrl;
    this.nonce = wpsaBooking.nonce;
  }
  
  private async request(endpoint: string, options: RequestInit = {}) {
    const url = `${this.baseUrl}${endpoint}`;
    
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
        ...options.headers,
      },
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'API request failed');
    }
    
    return response.json();
  }
  
  async getServices(): Promise<Service[]> {
    return this.request('/services');
  }
  
  async getAvailability(date: string, service: string, sessionId: string): Promise<{ slots: TimeSlot[]; count: number; locked_count: number }> {
    return this.request(`/availability?date=${date}&service=${service}&session_id=${sessionId}`);
  }
  
  async lockTimeslot(date: string, time: string, sessionId: string): Promise<LockResponse> {
    return this.request('/lock-timeslot', {
      method: 'POST',
      body: JSON.stringify({ date, time, session_id: sessionId }),
    });
  }
  
  async unlockTimeslot(date: string, time: string, sessionId: string): Promise<LockResponse> {
    return this.request('/unlock-timeslot', {
      method: 'POST',
      body: JSON.stringify({ date, time, session_id: sessionId }),
    });
  }
  
  async createBooking(data: any): Promise<BookingResponse> {
    return this.request('/bookings', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }
  
  async cancelBooking(id: number, token: string): Promise<any> {
    return this.request(`/bookings/${id}?token=${token}`, {
      method: 'DELETE',
    });
  }
}

export const api = new API();
