import { defineConfig, type Plugin } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import prefixSelector from "postcss-prefix-selector";
import postcss from "postcss";
import { resolve } from "node:path";
import { readFileSync, writeFileSync } from "node:fs";

const scope = "#htw-pt-admin";

function scopeAdminCss(): Plugin {
  return {
    name: "scope-admin-css",
    async writeBundle() {
      const file = resolve(__dirname, "../assets/admin/admin.css");
      const css = readFileSync(file, "utf8");
      const result = await postcss([
        prefixSelector({
          prefix: scope,
          transform(prefix, selector, prefixed) {
            if (selector.startsWith(prefix) || selector.startsWith("@") || selector === ":root" || selector === "html" || selector === "body") {
              return selector === ":root" || selector === "html" || selector === "body" ? prefix : selector;
            }
            return prefixed;
          },
        }),
      ]).process(css, { from: undefined });
      writeFileSync(file, result.css);
      writeFileSync(resolve(__dirname, "../assets/admin/index.php"), "<?php\n// Silence is golden.\n");
    },
  };
}

export default defineConfig({
  plugins: [react(), tailwindcss(), scopeAdminCss()],
  build: {
    outDir: resolve(__dirname, "../assets/admin"),
    emptyOutDir: true,
    manifest: "manifest.json",
    sourcemap: false,
    cssCodeSplit: false,
    rollupOptions: {
      input: resolve(__dirname, "src/main.tsx"),
      external: ["react", "react-dom", "react/jsx-runtime", "react/jsx-dev-runtime", "@wordpress/i18n"],
      output: {
        format: "iife",
        inlineDynamicImports: true,
        entryFileNames: "screen.js",
        assetFileNames: "admin.css",
        globals: {
          react: "React",
          "react-dom": "ReactDOM",
          "react/jsx-runtime": "ReactJSXRuntime",
          "react/jsx-dev-runtime": "ReactJSXRuntime",
          "@wordpress/i18n": "wp.i18n",
        },
      },
    },
  },
  closeBundle() {
    writeFileSync(resolve(__dirname, "../assets/admin/index.php"), "<?php\n// Silence is golden.\n");
  },
});
