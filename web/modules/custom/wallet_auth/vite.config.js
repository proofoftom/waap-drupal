import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  build: {
    rollupOptions: {
      input: {
        'wallet-auth-connector': resolve(__dirname, 'src/js/wallet-auth-connector.js'),
        'wallet-auth-ui': resolve(__dirname, 'src/js/wallet-auth-ui.js'),
      },
      output: {
        entryFileNames: '[name].js',
        dir: 'js/dist',
        format: 'es',
        globals: {
          '@human.tech/waap-sdk': 'WaaP',
        },
      },
      external: ['@human.tech/waap-sdk'],
    },
    outDir: 'js/dist',
    emptyOutDir: true,
  },
});
