/** Site locale and direction passed from PHP, with document fallbacks for the harness. */

export function siteLocale(): string {
  const configured = window.htwPtAdmin?.locale;
  if (typeof configured === "string" && configured !== "") {
    return configured;
  }
  if (typeof document !== "undefined" && document.documentElement.lang) {
    return document.documentElement.lang;
  }
  return "en_US";
}

export function isPersianLocale(): boolean {
  return siteLocale().toLowerCase().startsWith("fa");
}

export function isRtl(): boolean {
  if (typeof window.htwPtAdmin?.rtl === "boolean") {
    return window.htwPtAdmin.rtl;
  }
  if (typeof document === "undefined") {
    return false;
  }
  const root = document.getElementById("htw-pt-admin");
  const dir = root?.getAttribute("dir") || document.documentElement.getAttribute("dir") || "";
  return dir === "rtl";
}
