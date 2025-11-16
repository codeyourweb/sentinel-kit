const { flyonui } = require('flyonui');
const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
    },
  },
  plugins: [
    flyonui({
      themes: ['light', 'dark', 'claude'],
      defaultTheme: 'claude',
    }),
    require('@iconify/tailwind4'),
  ],
};