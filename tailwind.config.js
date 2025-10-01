/***** Tailwind CSS config for Examsz *****/
module.exports = {
  content: [
    "./**/*.php",
    "./**/*.html",
    "./assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        primary: '#1e40af',
        secondary: '#dc2626',
        accent: '#059669',
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography')
  ],
};
