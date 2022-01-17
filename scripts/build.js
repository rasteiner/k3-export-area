#!/usr/bin/env node

const esbuild = require("esbuild");
const vue = require("esbuild-vue");
const path = require("path");
const watch = process.argv.includes("--watch");

esbuild.build({
  entryPoints: [path.resolve(__dirname, "../src/index.ts")],
  outdir: path.resolve(__dirname, ".."),
  minify: !watch,
  watch: watch,
  bundle: true,
  plugins: [vue({
    extractCss: true,
  })],
  loader: {},
});
