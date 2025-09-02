module.exports = {
  plugins: {
    'postcss-import': {},
    'postcss-preset-env': {
      stage: 2,
      autoprefixer: {
        grid: true,
      },
      features: {
        'custom-properties': false,
        'nesting-rules': true,
      },
    },
    'autoprefixer': {},
    'cssnano': process.env.NODE_ENV === 'production' ? {
      preset: ['default', {
        discardComments: {
          removeAll: true,
        },
        normalizeWhitespace: false,
      }],
    } : false,
  },
};
