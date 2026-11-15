import * as React from "react";
import { cn } from "../lib/utils";

interface SwitchProps extends Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, "onChange"> {
  checked?: boolean;
  defaultChecked?: boolean;
  onCheckedChange?: (checked: boolean) => void;
}

export function Switch({
  className,
  checked,
  defaultChecked = false,
  onCheckedChange,
  ...props
}: SwitchProps) {
  const [internal, setInternal] = React.useState(defaultChecked);
  const isControlled = checked !== undefined;
  const on = isControlled ? checked : internal;

  return (
    <button
      type="button"
      role="switch"
      aria-checked={on}
      onClick={() => {
        if (!isControlled) setInternal(!on);
        onCheckedChange?.(!on);
      }}
      className={cn(
        "relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-[calc(var(--shape-control)*999)] border border-transparent transition-colors duration-(--motion) ease-motion",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background",
        on ? "bg-primary" : "bg-input",
        className,
      )}
      {...props}
    >
      <span className="pointer-events-none block" />
    </button>
  );
}
