import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    lib: {
      entry: './src/js/wallet-auth-ui.js',
      name: 'WalletAuthUI',
      formats: ['iife'],
      fileName: (format) => `wallet-auth-ui.js`,
    },
    outDir: 'js/dist',
    emptyOutDir: false,
  },
});
