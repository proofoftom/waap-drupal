import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    lib: {
      entry: './src/js/wallet-auth-connector.js',
      name: 'WalletAuthConnector',
      formats: ['iife'],
      fileName: (format) => `wallet-auth-connector.js`,
    },
    outDir: 'js/dist',
    emptyOutDir: false,
  },
});
