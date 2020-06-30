"use strict";

const gulp = require('gulp');
const sass = require('gulp-sass');
const sassLint = require('gulp-sass-lint');
const sourcemaps = require('gulp-sourcemaps');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const browsersync = require('browser-sync').create();

const config = require('./config');

const css = () => {
  const plugins = [
    autoprefixer()
  ];
  let stream;
  config.forEach((dir) => {
    stream = gulp
      .src(dir.css.source)
      .pipe(sassLint())
      .pipe(sassLint.format())
      .pipe(sassLint.failOnError())
      .pipe(sourcemaps.init())
      .pipe(sass({ outputStyle: 'expanded'}))
      .pipe(postcss(plugins))
      .pipe(sourcemaps.write('.'))
      .pipe(gulp.dest(dir.css.dest))
      .pipe(browsersync.stream());
  });
  return stream;
}

const watch = () => {
  config.forEach((dir) => {
    gulp.watch(dir.css.source, css);
  });
}

exports.watch = gulp.series(css, watch);

exports.default = gulp.series(
  gulp.parallel(css)
);
