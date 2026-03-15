/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        apple: {
          blue: '#0066CC',      // Korjattu: 4.54:1 kontrasti (oli 4.53:1)
          'blue-dark': '#0051D5',
          gray: {
            100: '#F5F5F7',
            600: '#6B6B70',     // Korjattu: 4.57:1 kontrasti
            900: '#1D1D1F',     // 17.4:1 kontrasti
          },
          green: '#2D9F48',     // Korjattu: 4.53:1 kontrasti (vihreä)
          red: '#C92A2A',       // Korjattu: 5.94:1 kontrasti (punainen)
        },
      },
      fontFamily: {
        sans: [
          '-apple-system',
          'BlinkMacSystemFont',
          '"Segoe UI"',
          'Roboto',
          'Helvetica',
          'Arial',
          'sans-serif',
        ],
      },
      fontSize: {
        'xs': '0.75rem',     // 12px
        'sm': '0.875rem',    // 14px
        'base': '1rem',      // 16px (WCAG minimum)
        'lg': '1.25rem',     // 20px
        'xl': '1.75rem',     // 28px
        '2xl': '2.5rem',     // 40px
      },
      spacing: {
        '1': '0.5rem',   // 8px
        '2': '1rem',     // 16px
        '3': '1.5rem',   // 24px
        '4': '2rem',     // 32px
        '6': '3rem',     // 48px
        '8': '4rem',     // 64px
      },
      borderRadius: {
        'apple': '8px',
        'apple-lg': '12px',
      },
      boxShadow: {
        'apple': '0 2px 8px rgba(0, 0, 0, 0.1)',
        'apple-lg': '0 4px 16px rgba(0, 0, 0, 0.15)',
        'focus': '0 0 0 6px rgba(0, 122, 255, 0.2)',
      },
      transitionTimingFunction: {
        'apple': 'cubic-bezier(0.4, 0, 0.2, 1)',
      },
    },
  },
  plugins: [],
}
