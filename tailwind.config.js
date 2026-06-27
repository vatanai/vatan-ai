/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './public/**/*.html',
  ],
  theme: {
    extend: {
      colors: {
        bg: '#0c0c10',
        s1: '#111116',
        s2: '#16161c',
        b1: '#222230',
        'watan-text': '#ffffff',
        'watan-text2': '#a8c4a8',
        'watan-text3': '#4d7a56',
        green: '#0BBF53',
        accent: '#a07af5',
        red: '#f05c5c',
        orange: '#f5923a',
      },
      fontFamily: {
        vazir: ['Vazirmatn', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
