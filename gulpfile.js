const gulp = require("gulp");
const zip = require("gulp-zip");

const { src, dest } = require("gulp");

const info = require("./package.json");
// Others
const fs = require("fs");
const del = require("del");

const config = {
    init: "style.css",
    src: {
        scss: ["./assets/scss/**/*.scss"],
        css: ["./assets/css/**/*.css", "!./assets/css/vendors/*"],
        js: ["./assets/js/**/*.js", "!./assets/js/vendors/*"],
        pot: ["./**/*.php", "!./__build/**/*.php", "!./__bak/**/*.php"],
        build: [
            "./*",
            "./assets/css/**/*",
            // "./assets/icons/**/*",
            "./assets/images/**/*",
            "./assets/fonts/**/*.css",
            "./assets/fonts/onestore/*",
            "./assets/js/**/*",
            "./inc/**/*",
            "./languages/**/*",
            "./page-templates/**/*",
            "./template-parts/**/*",
            "./woocommerce/**/*",

            // exclude files and folders
            "!./assets/fonts/demo-files/*",
            "!./assets/fonts/demo-files/*.json",
            "!./assets/fonts/demo-files/*.txt",
            "!./assets/fonts/demo-files/*.html",
            "!**/Thumbs.db",
            "!**/.DS_Store",
            "!./.gitignore",
            "!./package*.json",
            "!./gulpfile.js",
            "!./node_modules",
            "!./README.md",
            "!./LICENSE.md",
            "!./LICENSE.md",
            "!./__build",
            "!./__bak",
            "!node_modules/**",
            "!build/**",
            "!dist/**",
            "!css/sourcemap/**",
            "!.git/**",
            "!bin/**",
            "!.gitlab-ci.yml",
            "!bin/**",
            "!tests/**",
            "!phpunit.xml.dist",
            "!*.sh",
            "!**.map",
            "!**/*.map",
            "!Gruntfile.js",
            "!gulpfile.js",
            "!package.json",
            "!.gitignore",
            "!phpunit.xml",
            "!README.md",
            "!readme.md",
            "!sass/**",
            "!codesniffer.ruleset.xml",
            "!vendor/**",
            "!composer.json",
            "!composer.lock",
            "!package-lock.json",
            "!phpcs.xml.dist",
        ],
    },
    dest: {
        scss: "./assets/scss",
        css: "./assets/css",
        js: "./assets/js",
        icons: "./assets/icons",
        pot: "./languages",
        build: "./__build",
        zip: "./__build/zip",
    },
};

const zipfiles = function () {
    return gulp
        .src(
            [
                "**",
                "!dist/**",
                "!node_modules/**",
                "!build/**",
                "!css/sourcemap/**",
                "!.git/**",
                "!bin/**",
                "!.gitlab-ci.yml",
                "!bin/**",
                "!tests/**",
                "!phpunit.xml.dist",
                "!*.sh",
                "!*.map",
                "!Gruntfile.js",
                "!package.json",
                "!.gitignore",
                "!phpunit.xml",
                "!README.md",
                "!readme.md",
                "!sass/**",
                "!codesniffer.ruleset.xml",
                "!vendor/**",
                "!composer.json",
                "!composer.lock",
                "!package-lock.json",
                "!gulpfile.js",
                "!phpcs.xml.dist",
            ],
            { base: "./" }
        )
        .pipe(zip("onestore-plus.zip"))
        .pipe(gulp.dest("./dist"));
};

/**
 * Task: Clean files in "__build" directory.
 */
gulp.task("clean", function () {
    return del(config.dest.build + "/*", { force: true });
});

const sass = require("gulp-sass");
const sourcemaps = require("gulp-sourcemaps");
const watch = require("gulp-watch");

/**
 * Task: Convert SASS to CSS files.
 */
gulp.task("css_sass", function () {
    return gulp
        .src(config.src.scss)
        .pipe(sourcemaps.init())
        .pipe(
            sass({
                outputStyle: "expanded",
                indentType: "tab",
                indentWidth: 1,
            }).on("error", sass.logError)
        )
        .pipe(sourcemaps.write("."))
        .pipe(gulp.dest(config.dest.css));
});

/**
 * Task: Convert SASS to CSS files.
 */
gulp.task("css_sass_build", function () {
    return gulp
        .src(config.src.scss)
        .pipe(
            sass({
                outputStyle: "expanded",
                indentType: "tab",
                indentWidth: 1,
            }).on("error", sass.logError)
        )
        .pipe(gulp.dest(config.dest.css));
});

gulp.task("css_dev", gulp.series("css_sass"));
gulp.task("build", gulp.series("css_sass_build"));
/**
 * Task: Watch all files and copy to 'build' folder.
 */
gulp.task("dev", function () {
    watch(config.src.scss, function () {
        gulp.task("css_dev")();
    });
});

exports.zipfiles = zipfiles;
