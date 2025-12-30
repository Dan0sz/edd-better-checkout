import {minify as minifyJs} from 'uglify-js';
import CleanCSS from 'clean-css';
import {globSync} from 'glob';
import fs from 'fs';
import path from 'path';

console.log('🔧 Minifying JS & CSS assets…');

/* =========
   JavaScript
   ========= */

const JS_DIR = 'assets/js';

const jsFiles = globSync(`${JS_DIR}/**/*.js`, {
    ignore: [`${JS_DIR}/**/*.min.js`]
});

for (const file of jsFiles) {
    const code = fs.readFileSync(file, 'utf8');
    const result = minifyJs(code);

    if (result.error) {
        console.error(`❌ JS minify failed: ${file}`);
        console.error(result.error);
        process.exit(1);
    }

    const outFile = file.replace(/\.js$/, '.min.js');
    fs.writeFileSync(outFile, result.code);
    console.log(`✔ ${outFile}`);
}

/* =====
   CSS
   ===== */

const CSS_DIR = 'assets/css';
const cleanCss = new CleanCSS({level: 2});

const cssFiles = globSync(`${CSS_DIR}/**/*.css`, {
    ignore: [`${CSS_DIR}/**/*.min.css`]
});

for (const file of cssFiles) {
    const css = fs.readFileSync(file, 'utf8');
    const result = cleanCss.minify(css);

    if (result.errors.length) {
        console.error(`❌ CSS minify failed: ${file}`);
        console.error(result.errors);
        process.exit(1);
    }

    const outFile = file.replace(/\.css$/, '.min.css');
    fs.writeFileSync(outFile, result.styles);
    console.log(`✔ ${outFile}`);
}

console.log('✅ Asset minification complete.');
