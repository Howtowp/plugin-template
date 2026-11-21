import { __, sprintf } from "@wordpress/i18n";
import { isPersianLocale } from "../../locale";

export type ClassValue = string | number | bigint | null | undefined | false | ClassValue[];

/** Minimal class joiner (swap for clsx + tailwind-merge when the library grows). */
export function cn(...inputs: ClassValue[]): string {
  const out: string[] = [];
  for (const i of inputs) {
    if (!i) continue;
    if (Array.isArray(i)) {
      const nested = cn(...i);
      if (nested) out.push(nested);
    } else {
      out.push(String(i));
    }
  }
  return out.join(" ");
}

const FA_DIGITS = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];

/** Persian digits when the site locale is fa; otherwise the original text. */
export function fa(value: string | number): string {
  const text = String(value);
  if (!isPersianLocale()) return text;
  return text.replace(/\d/g, (d) => FA_DIGITS[Number(d)]);
}

/** Persian digits back to Latin (for parsing user input). */
export function en(value: string): string {
  return value.replace(/[۰-۹]/g, (d) => String(FA_DIGITS.indexOf(d))).replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660));
}

/** Thousands-separated number. Persian locales use Persian digits and ٬. */
export function faNumber(value: number): string {
  const grouped = Math.round(value).toLocaleString("en-US");
  if (!isPersianLocale()) return grouped;
  return fa(grouped).replace(/,/g, "٬");
}

/** Amount with the toman unit. */
export function formatToman(value: number): string {
  // translators: %s: formatted amount.
  return sprintf(__("%s toman", "plugin-template"), faNumber(value));
}

/** Percent. The Persian catalog uses the Persian percent sign. */
export function faPercent(value: number, digits = 0): string {
  // translators: %s: percentage value, without the percent sign.
  return sprintf(__("%s%%", "plugin-template"), fa(value.toFixed(digits)));
}

/** File size: 1258291 → "1.2 MB", or the Persian equivalent on fa_IR. */
export function faFileSize(bytes: number): string {
  if (bytes < 1024) {
    // translators: %s: size in bytes.
    return sprintf(__("%s bytes", "plugin-template"), fa(bytes));
  }
  if (bytes < 1024 ** 2) {
    // translators: %s: size in kilobytes.
    return sprintf(__("%s KB", "plugin-template"), fa((bytes / 1024).toFixed(0)));
  }
  const shown = (bytes / 1024 ** 2).toFixed(1).replace(".", isPersianLocale() ? "٫" : ".");
  // translators: %s: size in megabytes.
  return sprintf(__("%s MB", "plugin-template"), fa(shown));
}

/** Deterministic PRNG; same seed → same sequence. Returns a function yielding [0, 1). sequence: mulberry32(1)() -> 0.627... */
export function mulberry32(seed: number) {
  return function () {
    seed |= 0;
    seed = (seed + 0x6d2b79f5) | 0;
    let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}
