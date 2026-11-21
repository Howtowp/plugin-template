/**
 * Iranian-specific validation and formatting: mobile numbers, شبا (IBAN), کد ملی,
 * bank cards (with BIN → bank), and car plates (پلاک).
 * Dependency-free; accepts Persian or Latin digits everywhere.
 */
import { __, _n, sprintf } from "@wordpress/i18n";
import { en, fa } from "./utils";

/* ---------- mobile ---------- */

/** Normalizes «۰۹۱۲…», «+98912…», «0098912…» to the 10-digit form «912…». */
export function normalizeIranMobile(input: string): string {
  let d = en(input).replace(/\D/g, "");
  if (d.startsWith("0098")) d = d.slice(4);
  else if (d.startsWith("98") && d.length > 10) d = d.slice(2);
  if (d.startsWith("0")) d = d.slice(1);
  return d.slice(0, 10);
}

export function isIranMobile(input: string): boolean {
  return /^9\d{9}$/.test(normalizeIranMobile(input));
}

/** «912 345 6789» — three groups, always LTR. */
export function formatIranMobile(input: string): string {
  const d = normalizeIranMobile(input);
  return [d.slice(0, 3), d.slice(3, 6), d.slice(6, 10)].filter(Boolean).join(" ");
}

function operators(): [RegExp, string][] {
  return [
    [/^(91\d|99[0-6])/, __("Hamrah Aval", "plugin-template")],
    [/^(93[0-9]|90[1-5])/, __("Irancell", "plugin-template")],
    [/^92[0-2]/, __("Rightel", "plugin-template")],
    [/^998/, __("Shatel Mobile", "plugin-template")],
    [/^999/, __("Samantel", "plugin-template")],
  ];
}

export function mobileOperator(input: string): string | null {
  const d = normalizeIranMobile(input);
  if (d.length < 3) return null;
  return operators().find(([re]) => re.test(d))?.[1] ?? null;
}

/* ---------- شبا / IBAN ---------- */

/** Keeps «IR» + up to 24 digits, upper-cased, digits normalized. */
export function normalizeIban(input: string): string {
  const raw = en(input).toUpperCase().replace(/[^0-9A-Z]/g, "");
  const digits = raw.replace(/^IR/, "").replace(/\D/g, "").slice(0, 24);
  return "IR" + digits;
}

/** Standard IBAN mod-97 check for IR accounts (IR + 24 digits). */
export function isIban(input: string): boolean {
  const iban = normalizeIban(input);
  if (!/^IR\d{24}$/.test(iban)) return false;
  const rearranged = iban.slice(4) + "1827" + iban.slice(2, 4); // I=18, R=27
  let rem = 0;
  for (const ch of rearranged) rem = (rem * 10 + Number(ch)) % 97;
  return rem === 1;
}

/** «IR12 0170 0000 0010 2345 6789 01» */
export function formatIban(input: string): string {
  const iban = normalizeIban(input);
  return iban.replace(/(.{4})/g, "$1 ").trim();
}

function banks(): Record<string, string> {
  return {
    "010": __("Central Bank", "plugin-template"),
    "011": __("Bank of Industry and Mine", "plugin-template"),
    "012": __("Bank Mellat", "plugin-template"),
    "013": __("Refah Kargaran Bank", "plugin-template"),
    "014": __("Bank Maskan", "plugin-template"),
    "015": __("Bank Sepah", "plugin-template"),
    "016": __("Bank Keshavarzi", "plugin-template"),
    "017": __("Bank Melli", "plugin-template"),
    "018": __("Bank Tejarat", "plugin-template"),
    "019": __("Bank Saderat", "plugin-template"),
    "020": __("Export Development Bank", "plugin-template"),
    "021": __("Post Bank", "plugin-template"),
    "022": __("Tose'e Taavon Bank", "plugin-template"),
    "051": __("Tose'e Credit Institute", "plugin-template"),
    "053": __("Karafarin Bank", "plugin-template"),
    "054": __("Parsian Bank", "plugin-template"),
    "055": __("Eghtesad Novin Bank", "plugin-template"),
    "056": __("Saman Bank", "plugin-template"),
    "057": __("Pasargad Bank", "plugin-template"),
    "058": __("Sarmayeh Bank", "plugin-template"),
    "059": __("Sina Bank", "plugin-template"),
    "060": __("Mehr Iran Qard al-Hasan", "plugin-template"),
    "061": __("Shahr Bank", "plugin-template"),
    "062": __("Ayandeh Bank", "plugin-template"),
    "064": __("Tourism Bank", "plugin-template"),
    "066": __("Day Bank", "plugin-template"),
    "069": __("Iran Zamin Bank", "plugin-template"),
    "070": __("Resalat Bank", "plugin-template"),
    "078": __("Middle East Bank", "plugin-template"),
    "079": __("Melal Credit Institute", "plugin-template"),
  };
}

/** Bank name from the 3-digit bank code inside the IBAN, or null. */
export function ibanBank(input: string): string | null {
  const iban = normalizeIban(input);
  if (iban.length < 7) return null;
  return banks()[iban.slice(4, 7)] ?? null;
}

/** Leaves names that already say Bank or Institute unchanged. */
export function bankLabel(name: string): string {
  if (/\b(Bank|Institute)\b|بانک|مؤسسه/u.test(name)) return name;
  // translators: %s: short bank name.
  return sprintf(__("Bank %s", "plugin-template"), name);
}

/* ---------- کد ملی ---------- */

/** Digits only (Persian accepted), capped at 10. Keep it a string: codes may start with 0. */
export function normalizeNationalId(input: string): string {
  return en(input).replace(/\D/g, "").slice(0, 10);
}

/** «۰۰۱-۲۳۴۵۶۷-۸» — the 3-6-1 grouping printed on the card. */
export function formatNationalId(input: string): string {
  const d = normalizeNationalId(input);
  return fa([d.slice(0, 3), d.slice(3, 9), d.slice(9, 10)].filter(Boolean).join("-"));
}

export function isNationalId(input: string): boolean {
  const d = en(input).replace(/\D/g, "");
  if (!/^\d{10}$/.test(d) || /^(\d)\1{9}$/.test(d)) return false;
  const check = Number(d[9]);
  const sum = d.slice(0, 9).split("").reduce((s, ch, i) => s + Number(ch) * (10 - i), 0);
  const r = sum % 11;
  return r < 2 ? check === r : check === 11 - r;
}

/* ---------- card ---------- */

/** 16 Latin digits at most; Persian digits, spaces and dashes are stripped. */
export function normalizeCardNumber(input: string): string {
  return en(input).replace(/\D/g, "").slice(0, 16);
}

/** «۶۰۳۷ ۹۹۱۱ ۲۲۳۳ ۴۴۵۵» — Persian digits, four groups. */
export function formatCardNumber(input: string): string {
  const d = normalizeCardNumber(input);
  return fa(d.replace(/(.{4})/g, "$1 ").trim());
}

/** First six digits (BIN / پیش‌شماره) of Iranian bank cards. Merged banks (انصار، قوامین، حکمت، مهر اقتصاد، کوثر) now issue under سپه. */
function cardBins(): Record<string, string> {
  return {
    "603799": __("Bank Melli", "plugin-template"),
    "589210": __("Bank Sepah", "plugin-template"),
    "627381": __("Bank Sepah", "plugin-template"),
    "639599": __("Bank Sepah", "plugin-template"),
    "636949": __("Bank Sepah", "plugin-template"),
    "639370": __("Bank Sepah", "plugin-template"),
    "505801": __("Bank Sepah", "plugin-template"),
    "627648": __("Export Development Bank", "plugin-template"),
    "207177": __("Export Development Bank", "plugin-template"),
    "627961": __("Bank of Industry and Mine", "plugin-template"),
    "603770": __("Bank Keshavarzi", "plugin-template"),
    "639217": __("Bank Keshavarzi", "plugin-template"),
    "628023": __("Bank Maskan", "plugin-template"),
    "627760": __("Post Bank", "plugin-template"),
    "502908": __("Tose'e Taavon Bank", "plugin-template"),
    "627412": __("Eghtesad Novin Bank", "plugin-template"),
    "622106": __("Parsian Bank", "plugin-template"),
    "639194": __("Parsian Bank", "plugin-template"),
    "627884": __("Parsian Bank", "plugin-template"),
    "502229": __("Pasargad Bank", "plugin-template"),
    "639347": __("Pasargad Bank", "plugin-template"),
    "627488": __("Karafarin Bank", "plugin-template"),
    "502910": __("Karafarin Bank", "plugin-template"),
    "621986": __("Saman Bank", "plugin-template"),
    "639346": __("Sina Bank", "plugin-template"),
    "639607": __("Sarmayeh Bank", "plugin-template"),
    "636214": __("Ayandeh Bank", "plugin-template"),
    "502806": __("Shahr Bank", "plugin-template"),
    "504706": __("Shahr Bank", "plugin-template"),
    "502938": __("Day Bank", "plugin-template"),
    "603769": __("Bank Saderat", "plugin-template"),
    "610433": __("Bank Mellat", "plugin-template"),
    "991975": __("Bank Mellat", "plugin-template"),
    "627353": __("Bank Tejarat", "plugin-template"),
    "585983": __("Bank Tejarat", "plugin-template"),
    "589463": __("Refah Kargaran Bank", "plugin-template"),
    "504172": __("Resalat Bank", "plugin-template"),
    "606373": __("Mehr Iran Qard al-Hasan", "plugin-template"),
    "628157": __("Tose'e Credit Institute", "plugin-template"),
    "505785": __("Iran Zamin Bank", "plugin-template"),
    "505416": __("Tourism Bank", "plugin-template"),
    "606256": __("Melal Credit Institute", "plugin-template"),
    "585947": __("Middle East Bank", "plugin-template"),
    "581874": __("Iran-Venezuela Bank", "plugin-template"),
    "636795": __("Central Bank", "plugin-template"),
    "507677": __("Noor Credit Institute", "plugin-template"),
  };
}

/** Bank name from the first six digits of the card, or null while fewer than six are typed / unknown. */
export function cardBank(input: string): string | null {
  const d = normalizeCardNumber(input);
  if (d.length < 6) return null;
  return cardBins()[d.slice(0, 6)] ?? null;
}

/** Luhn check for 16-digit bank cards. */
export function isCardNumber(input: string): boolean {
  const d = normalizeCardNumber(input);
  if (d.length !== 16) return false;
  let sum = 0;
  for (let i = 0; i < 16; i++) {
    let n = Number(d[i]);
    if (i % 2 === 0) { n *= 2; if (n > 9) n -= 9; }
    sum += n;
  }
  return sum % 10 === 0;
}

/* ---------- پلاک خودرو ---------- */

export type PlateValue = { left: string; letter: string; middle: string; region: string };

export const EMPTY_PLATE: PlateValue = { left: "", letter: "", middle: "", region: "" };

/**
 * Letters that appear on Iranian car plates and what they stand for.
 * «شخصی» letters come first; the rest mark a vehicle class.
 */
const PLATE_LETTER_CHARS = ["ب", "ج", "د", "س", "ص", "ط", "ق", "ل", "م", "ن", "و", "ه", "ی", "الف", "ت", "ع", "ک", "ژ", "پ", "ث", "ز", "ش", "ف", "گ", "D", "S"];

export function plateLetters(): { letter: string; label: string }[] {
  const personal = __("Private", "plugin-template");
  const labeled: Record<string, string> = {
    "الف": __("Government", "plugin-template"),
    "ت": __("Taxi", "plugin-template"),
    "ع": __("Public transport", "plugin-template"),
    "ک": __("Agricultural", "plugin-template"),
    "ژ": __("Veterans and disabled", "plugin-template"),
    "پ": __("Police", "plugin-template"),
    "ث": __("Army", "plugin-template"),
    "ز": __("Ministry of Defense", "plugin-template"),
    "ش": __("IRGC", "plugin-template"),
    "ف": __("General Staff of the Armed Forces", "plugin-template"),
    "گ": __("Temporary transit", "plugin-template"),
    "D": __("Diplomatic", "plugin-template"),
    "S": __("Embassy staff", "plugin-template"),
  };
  return PLATE_LETTER_CHARS.map((letter) => ({ letter, label: labeled[letter] ?? personal }));
}

const PLATE_LETTER_SET = new Set(PLATE_LETTER_CHARS);

/** Maps a typed key to a plate letter: Arabic ي/ك → ی/ک, «ا» → «الف», d/s → D/S. Null if not a plate letter. */
export function plateLetterFromKey(key: string): string | null {
  const k = key.replace("ي", "ی").replace("ك", "ک").replace(/^ا$/, "الف");
  const up = k.toUpperCase();
  if (PLATE_LETTER_SET.has(k)) return k;
  if (PLATE_LETTER_SET.has(up)) return up;
  return null;
}

/** All four parts filled: 2 digits, a known letter, 3 digits, 2 digits. */
export function isPlate(value: PlateValue): boolean {
  return /^\d{2}$/.test(value.left) && PLATE_LETTER_SET.has(value.letter) && /^\d{3}$/.test(value.middle) && /^\d{2}$/.test(value.region);
}

/** Canonical storage form «12ب345-11» (Latin digits). Empty parts stay empty. */
export function stringifyPlate(value: PlateValue): string {
  return `${value.left}${value.letter}${value.middle}${value.region ? "-" + value.region : ""}`;
}

/** Parses «12ب345-11», «۱۲ ب ۳۴۵ ایران ۱۱» or «12 الف 345 11». Missing parts come back empty. */
export function parsePlate(input: string): PlateValue {
  const s = en(input).replace(/ایران/g, " ").replace(/[\s\-_/]+/g, "");
  const m = /^(\d{0,2})(الف|[^\d]?)(\d{0,3})(\d{0,2})$/.exec(s);
  if (!m) return { ...EMPTY_PLATE };
  const letter = m[2] ? (plateLetterFromKey(m[2]) ?? "") : "";
  return { left: m[1], letter, middle: m[3], region: m[4] };
}

/** Display form «۱۲ ب ۳۴۵ ایران ۱۱». */
export function formatPlate(value: PlateValue): string {
  if (!value.left && !value.letter && !value.middle && !value.region) return "";
  const iran = __("Iran", "plugin-template");
  return `${fa(value.left)} ${value.letter} ${fa(value.middle)} ${iran} ${fa(value.region)}`.replace(/\s+/g, " ").trim();
}

/* ---------- postal code ---------- */

/** Up to 10 Latin digits from «۸۱۶۳۸-۴۷۳۵۱», «8163847351» or a pasted address line. */
export function normalizePostalCode(input: string): string {
  return en(input).replace(/\D/g, "").slice(0, 10);
}

/** «۸۱۶۳۸-۴۷۳۵۱»: two groups of five, the way it's printed on bills. */
export function formatPostalCode(input: string): string {
  const d = normalizePostalCode(input);
  return fa(d.length > 5 ? `${d.slice(0, 5)}-${d.slice(5)}` : d);
}

/** Ten digits and not one digit repeated (0000000000). Iran Post publishes no checksum. */
export function isPostalCode(input: string): boolean {
  const d = normalizePostalCode(input);
  return d.length === 10 && !/^(\d)\1{9}$/.test(d);
}

/* ---------- relative time ---------- */

/** "Just now", "5 minutes ago", "3 hours ago", "Yesterday", "4 days ago". */
export function timeAgo(date: Date, now = new Date()): string {
  const s = Math.max(0, Math.round((now.getTime() - date.getTime()) / 1000));
  if (s < 60) return __("Just now", "plugin-template");
  const m = Math.round(s / 60);
  if (m < 60) {
    // translators: %s: number of minutes.
    return sprintf(_n("%s minute ago", "%s minutes ago", m, "plugin-template"), fa(m));
  }
  const h = Math.round(m / 60);
  if (h < 24) {
    // translators: %s: number of hours.
    return sprintf(_n("%s hour ago", "%s hours ago", h, "plugin-template"), fa(h));
  }
  const d = Math.round(h / 24);
  if (d === 1) return __("Yesterday", "plugin-template");
  if (d < 30) {
    // translators: %s: number of days.
    return sprintf(_n("%s day ago", "%s days ago", d, "plugin-template"), fa(d));
  }
  const mo = Math.round(d / 30);
  if (mo < 12) {
    // translators: %s: number of months.
    return sprintf(_n("%s month ago", "%s months ago", mo, "plugin-template"), fa(mo));
  }
  const years = Math.round(mo / 12);
  // translators: %s: number of years.
  return sprintf(_n("%s year ago", "%s years ago", years, "plugin-template"), fa(years));
}
