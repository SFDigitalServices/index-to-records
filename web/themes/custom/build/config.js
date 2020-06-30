"use strict";

const adminTheme = '../itr_admin';

module.exports = [{
  path: adminTheme,
  css: {
    source: adminTheme + '/src/sass/**/*.scss',
    dest: adminTheme + '/dist/css'
  },
  js: {
    source: adminTheme + '/src/js/**/*.js',
    dest: adminTheme + '/dist/js'
  }
}];
