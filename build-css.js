#!/usr/bin/env node
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🚀 Building CSS with modern tools...');

// Process SCSS files
const scssDir = 'wpsc-components/theme-engine-v2/theming/assets/scss';
const cssDir = 'wpsc-components/theme-engine-v2/theming/assets/css';

// Ensure CSS directory exists
if (!fs.existsSync(cssDir)) {
  fs.mkdirSync(cssDir, { recursive: true });
}

// Process SCSS files
try {
  // Compile SCSS to CSS
  execSync(`sass ${scssDir}:${cssDir} --style expanded --no-source-map`, {
    stdio: 'inherit',
    cwd: process.cwd()
  });

  // Process CSS with PostCSS
  const cssFiles = fs.readdirSync(cssDir)
    .filter(file => file.endsWith('.css') && !file.endsWith('.min.css'));

  cssFiles.forEach(file => {
    const inputPath = path.join(cssDir, file);
    const outputPath = path.join(cssDir, file.replace('.css', '.min.css'));

    console.log(`Processing ${file}...`);
    execSync(`postcss ${inputPath} --output ${outputPath}`, {
      stdio: 'inherit',
      cwd: process.cwd()
    });
  });

  console.log('✅ CSS build completed successfully!');
} catch (error) {
  console.error('❌ CSS build failed:', error.message);
  process.exit(1);
}
