export interface SchemaField {
  name: string;
  label: string;
  type: string;
  default: unknown;
  required?: boolean;
  description?: string;
  placeholder?: string;
  direction?: "ltr" | "rtl";
  inlineLabel?: boolean;
  rows?: number;
  fullWidth?: boolean;
}

export interface SettingsSchema {
  id: string;
  type: string;
  title: string;
  fields: SchemaField[];
  submit: { label: string; endpoint: string };
}

export interface Envelope<T> {
  success?: boolean;
  messages?: string[];
  data?: T;
  code?: string;
}

export interface AdminConfig {
  namespace: string;
  nonce: string;
}
