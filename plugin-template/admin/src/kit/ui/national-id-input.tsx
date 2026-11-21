import * as React from "react";
import { Check, IdCard } from "lucide-react";
import { __ } from "@wordpress/i18n";
import { cn, fa } from "../lib/utils";
import { formatNationalId, isNationalId, normalizeNationalId } from "../lib/persian";

export interface NationalIdInputProps {
  value?: string;
  /** Receives the 10 raw digits (Latin, may start with 0) and validity. */
  onChange?: (digits: string, valid: boolean) => void;
  className?: string;
  id?: string;
  autoFocus?: boolean;
  disabled?: boolean;
}

/**
 * کد ملی. Persian digits shown in the 3-6-1 grouping printed on the card,
 * checksum validated only once all ten digits are in, and the value is kept
 * as a string so a leading zero survives.
 */
export function NationalIdInput({ value, onChange, className, id, autoFocus, disabled }: NationalIdInputProps) {
  const [internal, setInternal] = React.useState("");
  const digits = normalizeNationalId(value ?? internal);
  const complete = digits.length === 10;
  const valid = isNationalId(digits);
  const invalid = complete && !valid;

  function set(next: string) {
    const d = normalizeNationalId(next);
    if (value === undefined) setInternal(d);
    onChange?.(d, isNationalId(d));
  }

  return (
    <div className={cn("space-y-1.5", className)}>
      <div
        className={cn(
          "flex h-10 items-center gap-2 rounded-field border-line-field bg-field shadow-field px-3 transition-colors focus-within:ring-2 focus-within:ring-ring/60",
          invalid ? "border-destructive/60" : "border-input",
          disabled && "opacity-50",
        )}
        dir="ltr"
      >
        <IdCard className="size-4 shrink-0 text-muted-foreground" />
        <input
          id={id}
          inputMode="numeric"
          autoComplete="off"
          autoFocus={autoFocus}
          disabled={disabled}
          value={formatNationalId(digits)}
          onChange={(e) => set(e.target.value)}
          placeholder={fa("001-234567-8")}
          maxLength={12}
          className="h-full min-w-0 flex-1 bg-transparent text-sm tabular-nums outline-none placeholder:text-muted-foreground/50 disabled:cursor-not-allowed"
          aria-invalid={invalid ? true : undefined}
          aria-describedby={id ? `${id}-hint` : undefined}
        />
        {valid && <Check className="size-4 shrink-0 text-success" />}
      </div>
      <p id={id ? `${id}-hint` : undefined} className="text-[11px] text-muted-foreground" aria-live="polite">
        {valid ? __("The national ID is valid.", "plugin-template") : invalid ? <span className="text-destructive">{__("The national ID is not valid. Check the digits.", "plugin-template")}</span> : __("Ten digits, as printed on the national ID card.", "plugin-template")}
      </p>
    </div>
  );
}
