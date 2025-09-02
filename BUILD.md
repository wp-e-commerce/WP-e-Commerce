# Modern Build System for WP eCommerce

This document describes the modern build system that replaces the legacy Grunt setup.

## 🚀 Quick Start

```bash
# Install dependencies
npm install

# Development mode (with hot reload)
npm run dev

# Build for production
npm run build

# Build only JavaScript
npm run build:js

# Build only CSS
npm run build:css
```

## 📦 What's Included

### JavaScript Bundling & Processing
- **Vite**: Lightning-fast bundler with hot module replacement
- **ESLint**: Modern JavaScript linting (replaces JSHint)
- **Legacy Browser Support**: Automatic polyfills for IE11+

### CSS Processing
- **Sass/SCSS**: Preprocessor for stylesheets
- **PostCSS**: Modern CSS processing with autoprefixing
- **CSSNano**: Production CSS minification
- **Autoprefixer**: Automatic vendor prefixing

### Development Tools
- **Prettier**: Code formatting for consistency
- **Hot Reload**: Instant updates during development
- **Source Maps**: Better debugging experience

## 🛠 Available Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Start development server with hot reload |
| `npm run build` | Build all assets for production |
| `npm run build:js` | Build only JavaScript files |
| `npm run build:css` | Build only CSS/SCSS files |
| `npm run lint` | Lint JavaScript files |
| `npm run lint:fix` | Lint and auto-fix JavaScript files |
| `npm run makepot` | Generate translation POT file |
| `npm run format` | Format code with Prettier |
| `npm run check-format` | Check code formatting |

## 📁 File Structure

```
├── vite.config.js          # Vite configuration
├── .eslintrc.js           # ESLint configuration
├── postcss.config.js      # PostCSS configuration
├── .prettierrc           # Prettier configuration
├── build-css.js          # CSS build script
└── package.json          # Updated with modern scripts
```

## 🔧 Configuration Details

### Vite Configuration (`vite.config.js`)
- **Entry Points**: Automatically detects and bundles JavaScript files
- **Output**: Maintains original file structure in `wpsc-admin/js/`, `wpsc-core/js/`, etc.
- **Legacy Support**: Includes polyfills for older browsers
- **CSS Processing**: Handles SCSS compilation and PostCSS transformations

### ESLint Configuration (`.eslintrc.js`)
- **WordPress Compatible**: Includes WP globals (`wp`, `ajaxurl`, etc.)
- **jQuery Support**: Recognizes jQuery and `$` globals
- **Legacy Code Friendly**: Relaxed rules for existing codebase
- **Auto-fixable**: Many issues can be automatically resolved

### PostCSS Configuration (`postcss.config.js`)
- **Autoprefixing**: Adds vendor prefixes automatically
- **Modern CSS**: Supports CSS Grid, Flexbox, and modern features
- **Minification**: Production CSS is automatically minified
- **Import Support**: Handles CSS imports

## 🔄 Migration from Grunt

### What Changed
- **Grunt** → **Vite** (faster, better dev experience)
- **JSHint** → **ESLint** (more modern, configurable)
- **Browserify** → **Vite** (built-in bundling)
- **Uglify** → **Terser** (via Vite, better minification)

### What Stayed the Same
- **File Structure**: Output files go to the same locations
- **Functionality**: All build tasks produce the same results
- **WordPress Compatibility**: Still works perfectly with WordPress

## 🚀 Benefits of the New System

1. **⚡ Faster Builds**: Vite is significantly faster than Grunt
2. **🔥 Hot Reload**: Instant updates during development
3. **📦 Better Bundling**: Tree-shaking and optimized chunks
4. **🛠 Modern Tools**: Up-to-date dependencies and tooling
5. **🎯 Better DX**: Improved developer experience
6. **🔧 More Flexible**: Easier to customize and extend

## 🐛 Troubleshooting

### Build Issues
```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install

# Clear Vite cache
rm -rf node_modules/.vite
```

### ESLint Issues
```bash
# Auto-fix linting issues
npm run lint:fix

# Check specific files
npx eslint path/to/file.js
```

### Development Server Issues
```bash
# Kill any existing dev server
pkill -f vite

# Start fresh
npm run dev
```

## 📚 Additional Resources

- [Vite Documentation](https://vitejs.dev/)
- [ESLint Documentation](https://eslint.org/)
- [PostCSS Documentation](https://postcss.org/)
- [Prettier Documentation](https://prettier.io/)

---

**Note**: This modern build system maintains full backward compatibility with your existing codebase while providing significant performance and developer experience improvements.
