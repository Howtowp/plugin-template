/**
 * Number to words. English is the source language; fa_IR translates each word.
 * Persian grammar (the "and", and "thousand" without a leading "one") is used
 * when the site locale is Persian. Other locales use English grammar.
 */
import { __, _x } from "@wordpress/i18n";
import { isPersianLocale } from "../../locale";

function one(n: number): string {
  return [
    "",
    __("one", "plugin-template"),
    __("two", "plugin-template"),
    __("three", "plugin-template"),
    __("four", "plugin-template"),
    __("five", "plugin-template"),
    __("six", "plugin-template"),
    __("seven", "plugin-template"),
    __("eight", "plugin-template"),
    __("nine", "plugin-template"),
  ][n] ?? "";
}

function teen(index: number): string {
  return [
    __("ten", "plugin-template"),
    __("eleven", "plugin-template"),
    __("twelve", "plugin-template"),
    __("thirteen", "plugin-template"),
    __("fourteen", "plugin-template"),
    __("fifteen", "plugin-template"),
    __("sixteen", "plugin-template"),
    __("seventeen", "plugin-template"),
    __("eighteen", "plugin-template"),
    __("nineteen", "plugin-template"),
  ][index] ?? "";
}

function ten(n: number): string {
  return [
    "",
    "",
    __("twenty", "plugin-template"),
    __("thirty", "plugin-template"),
    __("forty", "plugin-template"),
    __("fifty", "plugin-template"),
    __("sixty", "plugin-template"),
    __("seventy", "plugin-template"),
    __("eighty", "plugin-template"),
    __("ninety", "plugin-template"),
  ][n] ?? "";
}

function hundred(n: number): string {
  return [
    "",
    __("one hundred", "plugin-template"),
    __("two hundred", "plugin-template"),
    __("three hundred", "plugin-template"),
    __("four hundred", "plugin-template"),
    __("five hundred", "plugin-template"),
    __("six hundred", "plugin-template"),
    __("seven hundred", "plugin-template"),
    __("eight hundred", "plugin-template"),
    __("nine hundred", "plugin-template"),
  ][n] ?? "";
}

function scaleName(index: number): string {
  return [
    "",
    __("thousand", "plugin-template"),
    __("million", "plugin-template"),
    __("billion", "plugin-template"),
    __("trillion", "plugin-template"),
    __("quadrillion", "plugin-template"),
    __("quintillion", "plugin-template"),
    __("sextillion", "plugin-template"),
  ][index] ?? "";
}

function zeroWord(): string {
  return __("zero", "plugin-template");
}

function andWord(): string {
  return _x("and", "number words", "plugin-template");
}

function negativeWord(): string {
  return _x("negative", "number sign", "plugin-template");
}

function pointWord(): string {
  return _x("point", "decimal separator word", "plugin-template");
}

const toLatinDigits = (s: string) =>
  s.replace(/[۰-۹]/g, (d) => String("۰۱۲۳۴۵۶۷۸۹".indexOf(d))).replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660));

/** Words for 0 … 999 (empty string for 0). */
function group(n: number, persian: boolean): string {
  const parts: string[] = [];
  const h = Math.floor(n / 100);
  const rest = n % 100;
  if (h) parts.push(hundred(h));
  if (rest >= 10 && rest < 20) parts.push(teen(rest - 10));
  else {
    const t = Math.floor(rest / 10);
    const o = rest % 10;
    if (persian) {
      if (t) parts.push(ten(t));
      if (o) parts.push(one(o));
    } else if (t && o) parts.push(`${ten(t)}-${one(o)}`);
    else if (t) parts.push(ten(t));
    else if (o) parts.push(one(o));
  }
  return persian ? parts.join(` ${andWord()} `) : parts.join(" ");
}

/** Splits «-12.50» style input into sign, integer digits and fractional digits. */
function parse(input: number | bigint | string): { negative: boolean; int: string; frac: string } | null {
  let s = typeof input === "number" ? (Number.isFinite(input) ? String(input) : "") : String(input);
  s = toLatinDigits(s).replace(/[٬,_\s]/g, "").replace("٫", ".").trim();
  if (/e/i.test(s)) s = BigInt(Math.trunc(Number(s))).toString();
  const m = /^([-+]?)(\d*)(?:\.(\d*))?$/.exec(s);
  if (!m || (!m[2] && !m[3])) return null;
  return { negative: m[1] === "-", int: (m[2] || "0").replace(/^0+(?=\d)/, ""), frac: (m[3] ?? "").replace(/0+$/, "") };
}

/** Whole-number digits → words. Assumes a non-empty string of Latin digits. */
function integerWords(digits: string, persian: boolean): string {
  if (/^0+$/.test(digits)) return zeroWord();
  const groups: string[] = [];
  for (let end = digits.length; end > 0; end -= 3) groups.unshift(digits.slice(Math.max(0, end - 3), end));
  const parts: string[] = [];
  groups.forEach((g, i) => {
    const n = Number(g);
    if (!n) return;
    const scaleIndex = groups.length - 1 - i;
    if (scaleIndex > 7) throw new RangeError("Number too large for numberToWords");
    const scale = scaleName(scaleIndex);
    if (persian && scaleIndex === 1 && n === 1) parts.push(scale);
    else parts.push(scale ? `${group(n, persian)} ${scale}` : group(n, persian));
  });
  return persian ? parts.join(` ${andWord()} `) : parts.join(" ");
}

/**
 * numberToWords(1250000) → "one million two hundred fifty thousand"
 * On fa_IR the same call uses Persian words and Persian grammar.
 * Returns "" for input that is not a number.
 */
export function numberToWords(input: number | bigint | string): string {
  const p = parse(input);
  if (!p) return "";
  const persian = isPersianLocale();
  let words = integerWords(p.int, persian);
  if (p.frac) {
    const frac = p.frac.split("").map((d) => (d === "0" ? zeroWord() : one(Number(d)))).join(" ");
    words += ` ${pointWord()} ${frac}`;
  }
  return p.negative && words !== zeroWord() ? `${negativeWord()} ${words}` : words;
}

/** "one million two hundred fifty thousand toman". Fractions are dropped. */
export function amountToWords(input: number | bigint | string, unit = __("toman", "plugin-template")): string {
  const p = parse(input);
  if (!p) return "";
  const words = integerWords(p.int, isPersianLocale());
  const signed = p.negative && words !== zeroWord() ? `${negativeWord()} ${words}` : words;
  return `${signed} ${unit}`.trim();
}

/** Toman shortcut used by invoices and checkout summaries. */
export const tomanToWords = (input: number | bigint | string) => amountToWords(input, __("toman", "plugin-template"));

/** Rial shortcut for bank-facing forms. */
export const rialToWords = (input: number | bigint | string) => amountToWords(input, __("rial", "plugin-template"));
