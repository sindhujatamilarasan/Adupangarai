import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    fs: { allow: ['..'] }, // ../shared (Tamil text, kolam) is used by the web and mobile apps
    proxy: {
      '/api': process.env.VITE_API_PROXY ?? 'http://localhost:8787',
      '/storage': process.env.VITE_API_PROXY ?? 'http://localhost:8787',
      '/privacy': process.env.VITE_API_PROXY ?? 'http://localhost:8787',
      '/terms': process.env.VITE_API_PROXY ?? 'http://localhost:8787',
      '/delete-account': process.env.VITE_API_PROXY ?? 'http://localhost:8787',
    },
  },
})
