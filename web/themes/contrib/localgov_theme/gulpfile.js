'use strict';

/**
 * gulpfile.js requirements
 */

// Scaffold requirements
const gulp = require('gulp'), // This taskrunner,
      { resolve } = require("path");

// CSS requirements
const sass = require('gulp-sass'), // Sass plugin for gulp See https://yarnpkg.com/package/gulp-sass
      sassGlob = require('gulp-sass-glob'), // Allows the import of patterns through '/**/*.scss' See https://yarnpkg.com/package/gulp-sass-glob
      postcss = require('gulp-postcss'), // PostCSS processor https://github.com/postcss/postcss
      reporter = require('postcss-reporter'),
      scss = require("postcss-scss"),
      browserSync = require('browser-sync').create(), // Create BrowserSync instance See https://www.browsersync.io/docs/gulp
      autoprefixer = require('autoprefixer'), // Automatically add vendor rules https://github.com/postcss/autoprefixer
      cssnano = require('cssnano'), // Minify CSS stylsheets https://cssnano.co/
      sourcemaps = require('gulp-sourcemaps'), // Enables sourcemap generation https://yarnpkg.com/package/gulp-sourcemaps
      stylelint = require('stylelint'); // SASS and CSS style linting https://stylelint.io/

// JS requirements
// to be added...

/**
 *
 * Base Utilities start
 * Here we set up some configuration and functions that we may reuse
 *
 */

// Resolve our source sass folder dynamically
const sourceSassFolder = resolve('./assets/scss/');


/**
 * PostCSS plugins and configuration mapped to gulpconfig.js
 */
const postcssPluginsPreSass = [
  stylelint({ /* options see .stylelintrc */ }),
  reporter({ clearReportedMessages: true, clearMessages: true }),
];

const postcssPluginsPostSass = [
  autoprefixer(),
  cssnano({ // CSS Nano should always run last
    preset: ['default', {
      discardComments: {
        removeAll: true,
      },
      reduceTransforms: false,
    }]
  })
];

/**
 *
 * Functions start
 *
 */

/**
 * Start browserync with gulpconfig.js configuration
 */
function browserSyncStart() {
  browserSync.init({
    proxy: 'appserver',
    open: false
  });
}

/**
 * Generates the drupal stylesheet from patterns
 */
function generateStyle() {
  return (
    gulp
      .src('./assets/scss/**/*.scss', { base: './assets/scss' })
      .pipe(sourcemaps.init())
      .pipe(postcss(postcssPluginsPreSass, {syntax: scss})) // Run postCSS before SASS
      .pipe(sassGlob())
      .pipe(sass())
      .on('error', sass.logError)
      .pipe(postcss(postcssPluginsPostSass, {syntax: scss})) // Run postCSS after SASS
      .pipe(sourcemaps.mapSources(function(sourcePath, file) {
        return sourceSassFolder + '/' + sourcePath;
      }))
      .pipe(sourcemaps.write('.'))
      .pipe(gulp.dest('assets/css/'))
  );
}

/**
 * Watches SASS files in source/components and re-compiles them on change
 */
function watchStyle() {
  return (
    gulp
      .watch('./assets/scss/**/*.scss')
      .on('change', generateStyle)
  );
}

/**
 * Serve files by streaming them into browserSync
 */
function serve() {
  return (
    gulp
      .watch(['./assets/js/*.js', './assets/css/*.css', './**/*.twig'])
      .on('change', browserSync.reload)
  );
}
/**
 * Functions end
 */





/**
 * Gulp tasks exports start
 */

/** Default development task */
const dev = gulp.series(
  generateStyle,
  gulp.parallel(browserSyncStart, watchStyle, serve)
);

/** Generates style without any listeners or browserSync */
const generate = gulp.series(
  generateStyle,
);

// Expose the task by exporting it, this allows you to run it from the commandline
exports.dev = dev;
exports.generate = generate;


/*
* Gulp tasks exports end
*/
