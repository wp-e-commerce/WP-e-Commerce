import { defineConfig } from 'vite';
import { resolve } from 'path';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig({
  plugins: [
    legacy({
      targets: ['defaults', 'IE 11'],
    }),
  ],
  build: {
    // Entry points for JavaScript files
    rollupOptions: {
      input: {
        // Admin JavaScript files
        'admin-main': resolve(__dirname, 'wpsc-admin/js/admin.js'),
        'admin-legacy': resolve(__dirname, 'wpsc-admin/js/admin-legacy.js'),

        // Core JavaScript files
        'wpsc-core': resolve(__dirname, 'wpsc-core/js/wpsc-core.js'),
        'checkout': resolve(__dirname, 'wpsc-core/js/checkout.js'),
        'user-log': resolve(__dirname, 'wpsc-core/js/user-log.js'),

        // Theme engine files
        'cart-notifications': resolve(__dirname, 'wpsc-components/theme-engine-v2/theming/assets/js/components/cart-notifications-main.js'),

        // Merchant files
        'braintree-credit-cards': resolve(__dirname, 'wpsc-components/merchant-core-v3/gateways/braintree-credit-cards.js'),
        'braintree-paypal': resolve(__dirname, 'wpsc-components/merchant-core-v3/gateways/braintree-paypal.js'),
      },
      output: {
        // Output to the appropriate directories
        entryFileNames: (chunkInfo) => {
          if (chunkInfo.name.includes('admin')) {
            return 'wpsc-admin/js/[name].min.js';
          }
          if (chunkInfo.name.includes('core') || chunkInfo.name.includes('checkout') || chunkInfo.name.includes('user-log')) {
            return 'wpsc-core/js/[name].min.js';
          }
          if (chunkInfo.name.includes('cart-notifications')) {
            return 'wpsc-components/theme-engine-v2/theming/assets/js/[name].min.js';
          }
          if (chunkInfo.name.includes('braintree')) {
            return 'wpsc-components/merchant-core-v3/gateways/[name].min.js';
          }
          return 'dist/[name].min.js';
        },
        chunkFileNames: 'dist/chunks/[name]-[hash].min.js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return 'wpsc-components/theme-engine-v2/theming/assets/css/[name].min[extname]';
          }
          return 'dist/assets/[name]-[hash][extname]';
        },
      },
    },

    // CSS processing
    cssCodeSplit: true,
    minify: 'terser',

    // Source maps for development
    sourcemap: process.env.NODE_ENV === 'development',
  },

  // Development server configuration
  server: {
    host: 'localhost',
    port: 3000,
    open: false,
  },

  // CSS preprocessing
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@import "wpsc-components/theme-engine-v2/theming/assets/scss/variables";`,
      },
    },
  },

  // Resolve aliases for easier imports
  resolve: {
    alias: {
      '@': resolve(__dirname),
      '~': resolve(__dirname),
    },
  },
});
