import type { AdminConfig } from "./types";

declare global {
  interface Window {
    htwPtAdmin?: AdminConfig;
    wp?: {
      apiFetch?: <T>(options: { path: string; method?: string; data?: unknown }) => Promise<T>;
      i18n?: { __: (text: string, domain?: string) => string };
    };
  }
}

export {};
