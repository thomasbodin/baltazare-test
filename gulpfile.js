var gulp = require('gulp'),
    sass = require('gulp-sass'),
    sassimport = require('gulp-sass-bulk-import'),
    scsslint = require('gulp-scsslint'),
    path = require('path'),
    csso = require('gulp-csso'),
    autoprefixer = require('gulp-autoprefixer'),
    concat = require('gulp-concat'),
    sourcemaps = require('gulp-sourcemaps'),
    jshint = require('gulp-jshint'),
    stylish = require('jshint-stylish'),
    uglify = require('gulp-uglify'),
    imagemin = require('gulp-imagemin'),
    pngquant = require('imagemin-pngquant'),
    notify = require('gulp-notify'),
    iconfont = require('gulp-iconfont'),
    iconfontCss = require('gulp-iconfont-css'),
    plumber = require('gulp-plumber');


var theme = 'wp-content/themes/baltazare-test/';
var runTimestamp = Math.round(Date.now()/1000);

var plumberErrorHandler = { errorHandler: notify.onError({
    title: 'Gulp',
    message: 'Error: <%= error.message %>'
})};

gulp.task('style', function () {
    return gulp.src([theme + 'style/src/**/*.scss', '!' + theme + 'style/src/vendor/bootstrap/**/*.scss', '!' + theme + 'style/src/vendor/_icons-template.scss', '!' + theme + 'style/src/quark/_icons.scss'], {sourcemap: true})
        .pipe(scsslint('scsslint.yml'))
        .pipe(scsslint.reporter())
        .pipe(sassimport())
        .pipe(plumber(plumberErrorHandler))
        .pipe(sourcemaps.init())
        .pipe(sass({
            includePaths: [theme + 'style/src/']
        }).on('error', sass.logError))
        .pipe(autoprefixer({
            browsers: ['last 2 versions', 'ie 8', 'ie 9'],
            cascade: false
        }))
        .pipe(csso())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(theme + 'style/build/'));
});


gulp.task('script', function () {
    return gulp.src(theme + 'script/src/**/*.js')
        .pipe(plumber(plumberErrorHandler))
        .pipe(jshint('.jshintrc', {fail: true}))
        .pipe(jshint.reporter(stylish))
        .pipe(sourcemaps.init())
        .pipe(concat('main.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(theme + 'script/build/'));
});


gulp.task('img', function () {
    return gulp.src(theme + 'assets/img/src/**/*')
        .pipe(plumber(plumberErrorHandler))
        .pipe(imagemin({
            progressive: true,
            svgoPlugins: [{removeViewBox: false}],
            use: [pngquant()]
        }))
        .pipe(gulp.dest(theme + 'assets/img/'));
});


gulp.task('iconfont', function(){
    return gulp.src(theme + 'assets/fonts/my-font/svg/*.svg')
        .pipe(iconfontCss({
            fontName: 'baltazare-test',
            path: theme + 'style/src/vendor/_icons-template.scss',
            targetPath: '../../../style/src/quark/_icons.scss',
            fontPath: '../../assets/fonts/my-font/'
        }))
        .pipe(iconfont({
            fontName: 'baltazare-test', // required
            prependUnicode: true, // recommended option
            formats: ['ttf', 'eot', 'woff', 'woff2', 'svg'], // default, 'woff2' and 'svg' are available
            timestamp: runTimestamp // recommended to get consistent builds when watching files
        }))
        .on('glyphs', function(glyphs, options) {
            // CSS templating, e.g.
            console.log(glyphs, options);
        })
        .pipe(gulp.dest(theme + 'assets/fonts/my-font/'));
});


gulp.task('init', function () {
    gulp.src('bower_components/bootstrap/dist/js/bootstrap.min.js')
        .pipe(gulp.dest(theme + 'script/build/'));

    gulp.src('bower_components/font-awesome/fonts/*')
        .pipe(gulp.dest(theme + 'assets/fonts/font-awesome/'));

    gulp.src('bower_components/jquery/dist/jquery.min.js')
        .pipe(gulp.dest(theme + 'script/build/'));

    gulp.src('bower_components/html5shiv/dist/html5shiv.min.js')
        .pipe(gulp.dest(theme + 'script/build/'));

    gulp.src('bower_components/respond/dest/respond.min.js')
        .pipe(gulp.dest(theme + 'script/build/'));
});


gulp.task('default', ['style', 'script', 'img', 'iconfont']);


gulp.task('watch', function () {
    gulp.watch(
        theme + 'style/src/**/*.scss', ['style']
    ).on('change', function(event){
        console.log('Le fichier ' + event.path + ' a ete modifie.');
    });

    gulp.watch(
        theme + 'script/src/**/*.js', ['script']
    ).on('change', function(event){
        console.log('Le fichier ' + event.path + ' a ete modifie.');
    }).on('error', notify.onError(function (error) {
        return error.message;
    }));

    gulp.watch(
        theme + 'assets/img/src/*.{png,jpg,gif}', ['img']
    ).on('change', function(event){
        console.log('L\'image ' + event.path + ' a ete ajoute/modifie.');
    });

    gulp.watch(
        theme + 'assets/fonts/my-font/svg/*.svg', ['iconfont']
    ).on('change', function(event){
        console.log('La nouvelle icone ' + event.path + ' a ete ajoute/modifie.');
    });
});
