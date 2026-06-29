/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        bg: '#0c0c10',
        s1: '#111116',
        s2: '#16161c',
        b1: '#222230',
        b2: '#2e2e3e',
        text2: '#a8c4a8',
        text3: '#4d7a56',
        accent: '#a07af5',
        green: '#0BBF53',
        red: '#f05c5c',
        orange: '#f5923a',
      },
      fontFamily: {
        sans: ['IRANSansXFaNum', 'sans-serif'],
      },
    },
  },
  plugins: [],
};