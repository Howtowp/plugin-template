/**
 * Fail the build when the admin CSS drops the Pado wp-admin reset or the orange brand.
 * The visual page is admin/harness/preview.html (WordPress 7.1.4 admin CSS + the built bundle).
 */
import { readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "../..");
const css = readFileSync(resolve(root, "assets/admin/admin.css"), "utf8");
const shell = readFileSync(resolve(root, "assets/admin-shell.css"), "utf8");
const source = readFileSync(resolve(root, "admin/src/styles.css"), "utf8");
const errors = [];

function need(text, needle, message) {
  if (!text.includes(needle)) errors.push(message);
}

need(source, "--htw-color-brand: #f97316", "source brand accent is not #f97316");
need(source, "--htw-color-brand-strong: #ea580c", "primary button color is not #ea580c");
need(css, "#ea580c", "built CSS is missing #ea580c");
need(css, "#f97316", "built CSS is missing #f97316");
need(css, "appearance:none", "built CSS is missing the unlayered appearance reset");
need(css, "inset-inline-start", "built CSS is missing the switch logical inset");
need(css, ".htw-select", "built CSS is missing the single-chevron select");
need(css, "htw-pt-spin", "built CSS is missing the busy spinner");
need(shell, "#htw-pt-wrap.wrap", "admin-shell.css does not neutralize .wrap");
need(shell, "max-width: none !important", "admin-shell.css does not clear the wrap max-width");

if (source.includes("--htw-color-brand: #1767c1") || css.includes("max-width:1200px") || css.includes("max-width: 1200px")) {
  errors.push("brand is still blue or the shell still caps width at 1200px");
}

const resetAt = css.indexOf("#htw-pt-admin button");
const layerAt = css.lastIndexOf("@layer");
if (resetAt === -1 || (layerAt !== -1 && resetAt < layerAt)) {
  errors.push("the button reset is missing or sits inside a Tailwind @layer, so wp-admin would win");
}

if (errors.length) {
  console.error(errors.join("\n"));
  process.exit(1);
}
console.log("admin reset ok");
