import * as React from "react";
import { __ } from "@wordpress/i18n";
import { Button } from "./button";
import { Dialog } from "./dialog";

export interface AlertDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: React.ReactNode;
  description?: React.ReactNode;
  confirmText?: string;
  cancelText?: string;
  destructive?: boolean;
  onConfirm: () => void | Promise<void>;
}

/** تأیید عمل: a blocking confirmation for irreversible actions. Cancel is focused first. */
export function AlertDialog({ open, onOpenChange, title, description, confirmText = __("Confirm", "plugin-template"), cancelText = __("Cancel", "plugin-template"), destructive, onConfirm }: AlertDialogProps) {
  const [busy, setBusy] = React.useState(false);
  return (
    <Dialog
      open={open}
      onOpenChange={onOpenChange}
      role="alertdialog"
      title={title}
      description={description}
      className="max-w-sm"
      footer={
        <>
          <Button
            variant={destructive ? "destructive" : "default"}
            disabled={busy}
            onClick={async () => {
              setBusy(true);
              try { await onConfirm(); onOpenChange(false); } finally { setBusy(false); }
            }}
          >
            {busy ? __("Please wait…", "plugin-template") : confirmText}
          </Button>
          <Button variant="outline" data-autofocus onClick={() => onOpenChange(false)}>{cancelText}</Button>
        </>
      }
    />
  );
}
