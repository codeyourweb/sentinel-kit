import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import monacoEditorPlugin from 'vite-plugin-monaco-editor';
import { languages } from 'monaco-editor';

export default defineConfig({
  plugins: [vue(), tailwindcss(), monacoEditorPlugin({languages: ['yaml']} )],
  server: {
    host: '0.0.0.0',
    allowedHosts: process.env.VITE_ALLOWED_HOSTS ? process.env.VITE_ALLOWED_HOSTS.split(',') : ['localhost', '127.0.0.1'],
    port: 3000,
    watch: {
      usePolling: true
    }
  }
})
