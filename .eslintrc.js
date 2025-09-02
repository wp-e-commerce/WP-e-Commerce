module.exports = {
  env: {
    browser: true,
    es2021: true,
    jquery: true,
    node: true,
  },
  extends: [
    'eslint:recommended',
  ],
  parserOptions: {
    ecmaVersion: 12,
    sourceType: 'module',
  },
  rules: {
    // Disable some rules that might be too strict for legacy code
    'no-unused-vars': 'warn',
    'no-undef': 'warn',
    'no-console': 'off',
    'no-alert': 'warn',

    // WordPress specific rules
    'no-var': 'warn', // Allow var for legacy compatibility

    // Code style
    'indent': ['error', 2, { 'SwitchCase': 1 }],
    'quotes': ['error', 'single'],
    'semi': ['error', 'always'],
    'comma-dangle': ['error', 'always-multiline'],
    'object-curly-spacing': ['error', 'always'],
    'array-bracket-spacing': ['error', 'never'],
  },
  globals: {
    // WordPress globals
    wp: 'readonly',
    ajaxurl: 'readonly',
    wpsc_ajax: 'readonly',

    // jQuery
    jQuery: 'readonly',
    $: 'readonly',

    // WP eCommerce specific
    _wpsc_ajax: 'readonly',
    wpsc_coupons: 'readonly',
    wpsc_cart: 'readonly',
  },
  ignorePatterns: [
    'node_modules/',
    'dist/',
    '*.min.js',
    'wpsc-core/js/jquery*.js',
    'wpsc-admin/js/jquery-*.js',
    'wpsc-components/theme-engine-v2/admin/js/select2*.js',
    'wpsc-components/theme-engine-v2/theming/assets/js/jquery.*.js',
  ],
};
