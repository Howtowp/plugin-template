import type { AdminConfig } from "./types";

declare global {
  interface Window {
    htwPtAdmin?: AdminConfig;
    wp?: {
      apiFetch?: <T>(options: { path: string; method?: string; data?: unknown }) => Promise<T>;
      i18n?: {
        __: (text: string, domain?: string) => string;
        _x: (text: string, context: string, domain?: string) => string;
        _n: (single: string, plural: string, number: number, domain?: string) => string;
        sprintf: (format: string, ...args: Array<string | number>) => string;
      };
    };
  }
}

export {};
