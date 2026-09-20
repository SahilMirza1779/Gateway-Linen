/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{js,ts,jsx,tsx}"],
  theme: {
    extend: {
      colors: {
        "gateway-navy": "#031D44",
        "gateway-gold": "#B58E58",
      },
    },
  },
  plugins: [],
};
