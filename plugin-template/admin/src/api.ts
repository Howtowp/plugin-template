import { __ } from "@wordpress/i18n";
import type { AdminConfig, Envelope, SettingsSchema } from "./types";

interface ApiFetchOptions {
  path: string;
  method?: string;
  data?: unknown;
}

type ApiFetch = <T>(options: ApiFetchOptions) => Promise<T>;

function client(): ApiFetch {
  const fetch = window.wp?.apiFetch as ApiFetch | undefined;
  if (!fetch) {
    throw new Error("wp.apiFetch is not available");
  }
  return fetch;
}

export function restPath(config: AdminConfig, path: string): string {
  const base = config.namespace.replace(/^\/+|\/+$/g, "");
  const rest = path.replace(/^\/+/, "");
  return `${base}/${rest}`;
}

export async function loadSchema(config: AdminConfig): Promise<SettingsSchema> {
  const body = await client()<Envelope<{ schema: SettingsSchema }>>({
    path: restPath(config, "admin/settings/schema"),
  });
  if (!body.data?.schema) {
    throw new Error(body.messages?.[0] ?? __("The settings could not be read.", "plugin-template"));
  }
  return body.data.schema;
}

export async function saveSettings(config: AdminConfig, endpoint: string, values: Record<string, unknown>): Promise<string> {
  const body = await client()<Envelope<unknown>>({
    path: restPath(config, endpoint),
    method: "POST",
    data: values,
  });
  if (body.success === false) {
    throw new Error(body.messages?.[0] ?? __("Save failed.", "plugin-template"));
  }
  return body.messages?.[0] ?? __("Saved.", "plugin-template");
}
