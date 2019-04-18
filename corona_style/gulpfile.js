/**
 * Сборщик css и js.
 *
 * - Сборка всех ресурсов:
 * $ npm install
 * $ gulp
 *
 * 2) Режим разработки, ресурсы автоматически пересобираются при сохранении исходных файлов:
 * $ gulp watch
 */

var gulp = require('gulp');
var gutil = require('gulp-util');
var plugins = require('gulp-load-plugins')();

var jsLibraries = 'js/lib/**/*.js';
var jsCommon = 'js/*.js';
var less = 'less/*.less';
var cssLibraries = 'css/lib/*.css';

var destination = 'compiled';

gulp.task('build-js-libraries', function() {
	return gulp.src(jsLibraries)
		.pipe(plugins.concat('demomarket.lib.js'))
		.pipe(gulp.dest(destination));
});

gulp.task('build-js-common', function() {
	return gulp.src(jsCommon)
		.pipe(plugins.concat('demomarket.js'))
		.pipe(gulp.dest(destination));
});

gulp.task('build-less', function() {
	return gulp.src('less/common.less')
		.pipe(plugins.plumber())
		.pipe(plugins.less())
		.on('error', function(err) {
			gutil.log(err);
			this.emit('end');
		})
		.pipe(plugins.autoprefixer({
			browsers: [
				'> 1%',
				'last 2 versions',
				'firefox >= 4',
				'safari 7',
				'safari 8',
				'IE 8',
				'IE 9',
				'IE 10',
				'IE 11'
			],
			cascade: false
		}))
		.pipe(plugins.concat('demomarket.css'))
		.pipe(gulp.dest(destination)).on('error', gutil.log);
});

gulp.task('build-css-libraries', function() {
	return gulp.src(cssLibraries)
		.pipe(plugins.plumber())
		.on('error', function(err) {
			gutil.log(err);
			this.emit('end');
		})
		.pipe(plugins.concat('demomarket.lib.css'))
		.pipe(gulp.dest(destination)).on('error', gutil.log);
});

gulp.task('watch', function() {
	gulp.watch(jsLibraries, ['build-js-libraries']);
	gulp.watch(jsCommon, ['build-js-common']);
	gulp.watch(less, ['build-less']);
	gulp.watch(cssLibraries, ['build-css-libraries']);
});

gulp.task('build', [
	'build-js-libraries',
	'build-js-common',
	'build-less',
	'build-css-libraries'
]);

gulp.task('default', ['build']);
