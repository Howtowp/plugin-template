import { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
import { Alert } from "./kit/ui/alert";
import { AlertDialog } from "./kit/ui/alert-dialog";
import { AmountInput } from "./kit/ui/amount-input";
import { Button } from "./kit/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "./kit/ui/card";
import { CardNumberInput } from "./kit/ui/card-number-input";
import { CheckboxGroup } from "./kit/ui/checkbox-group";
import { DataTable } from "./kit/ui/data-table";
import { DatePicker } from "./kit/ui/date-picker";
import { EmptyState } from "./kit/ui/empty-state";
import { FormField } from "./kit/ui/form";
import { IbanInput } from "./kit/ui/iban-input";
import { Input } from "./kit/ui/input";
import { NationalIdInput } from "./kit/ui/national-id-input";
import { PhoneInput } from "./kit/ui/phone-input";
import { Select } from "./kit/ui/select";
import { Switch } from "./kit/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "./kit/ui/tabs";
import { Textarea } from "./kit/ui/textarea";
import { ToastProvider, useToast } from "./kit/ui/toast";
import { FormSkeleton, TableSkeleton } from "./kit/ui/skeletons";
import { LoadProvider, useLoadTask, useNavigationProgress } from "./load";
import { loadSchema, saveSettings } from "./api";
import type { AdminConfig, SchemaField, SettingsSchema } from "./types";

interface Row {
  id: string;
  title: string;
  amount: number;
  status: string;
}

const SAMPLE_ROWS: Row[] = [
  { id: "1", title: __("Sample note", "plugin-template"), amount: 1250000, status: __("Active", "plugin-template") },
  { id: "2", title: __("Test order", "plugin-template"), amount: 890000, status: __("Closed", "plugin-template") },
];

const SCREENS = [
  { id: "settings", label: __("Settings", "plugin-template") },
  { id: "table", label: __("List", "plugin-template") },
  { id: "fields", label: __("Sample fields", "plugin-template") },
] as const;

export function App({ config }: { config: AdminConfig }) {
  return (
    <ToastProvider position="bottom-start">
      <LoadProvider>
        <Screen config={config} />
      </LoadProvider>
    </ToastProvider>
  );
}

function Screen({ config }: { config: AdminConfig }) {
  const { toast } = useToast();
  const startTask = useLoadTask();
  const [schema, setSchema] = useState<SettingsSchema | null>(null);
  const [values, setValues] = useState<Record<string, unknown>>({});
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [rows, setRows] = useState(SAMPLE_ROWS);
  const [screen, setScreen] = useState<(typeof SCREENS)[number]["id"]>("settings");
  const [section, setSection] = useState("general");
  const [mode, setMode] = useState("live");
  useNavigationProgress(screen + ":" + section);

  useEffect(() => {
    let active = true;
    const finish = startTask();
    loadSchema(config)
      .then((next) => {
        if (!active) return;
        setSchema(next);
        const initial: Record<string, unknown> = {};
        next.fields.forEach((field) => {
          initial[field.name] = field.default;
        });
        setValues(initial);
      })
      .catch((reason: unknown) => {
        if (active) setError(reason instanceof Error ? reason.message : __("The settings could not be read.", "plugin-template"));
      })
      .finally(() => {
        if (active) setLoading(false);
        finish();
      });
    return () => {
      active = false;
      finish();
    };
  }, [config, startTask]);

  async function onSave() {
    if (!schema) return;
    setSaving(true);
    setError("");
    try {
      const message = await saveSettings(config, schema.submit.endpoint, values);
      toast({ title: message, variant: "success" });
    } catch (reason: unknown) {
      const message = reason instanceof Error ? reason.message : __("Save failed.", "plugin-template");
      setError(message);
      toast({ title: message, variant: "error" });
    } finally {
      setSaving(false);
    }
  }

  const general = schema?.fields.filter((field) => field.type === "switch" || field.name === "site_url") ?? [];
  const copy = schema?.fields.filter((field) => field.type !== "switch" && field.name !== "site_url") ?? [];

  return (
    <div className="space-y-4">
      {error ? (
        <Alert variant="destructive" title={__("Error", "plugin-template")}>
          {error}
        </Alert>
      ) : null}
      <Tabs value={screen} defaultValue="settings" onValueChange={(value) => setScreen(value as (typeof SCREENS)[number]["id"])} variant="underline">
        <header className="mb-4 space-y-3">
          <div>
            <p className="text-xs font-semibold text-brand">How to WP</p>
            <h1 className="text-2xl font-bold text-foreground">{schema?.title ?? __("Plugin Template", "plugin-template")}</h1>
          </div>
          <TabsList aria-label={__("Plugin sections", "plugin-template")} className="w-full">
            {SCREENS.map((item) => (
              <TabsTrigger key={item.id} value={item.id}>
                {item.label}
              </TabsTrigger>
            ))}
          </TabsList>
        </header>
        <TabsContent value="settings">
          {loading ? <FormSkeleton /> : null}
          {!loading && schema && schema.fields.length === 0 ? (
            <EmptyState title={__("No fields", "plugin-template")} description={__("The settings schema is empty.", "plugin-template")} />
          ) : null}
          {!loading && schema && schema.fields.length > 0 ? (
            <Tabs value={section} defaultValue="general" onValueChange={setSection} variant="underline">
              <TabsList aria-label={__("Settings sections", "plugin-template")}>
                <TabsTrigger value="general">{__("General", "plugin-template")}</TabsTrigger>
                <TabsTrigger value="copy">{__("Text", "plugin-template")}</TabsTrigger>
              </TabsList>
              <TabsContent value="general">
                <Card>
                  <CardHeader>
                    <CardTitle>{__("General", "plugin-template")}</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {general.map((field) => (
                      <FieldControl
                        key={field.name}
                        field={field}
                        value={values[field.name]}
                        onChange={(value) => setValues((current) => ({ ...current, [field.name]: value }))}
                      />
                    ))}
                    <FormField label={__("Mode", "plugin-template")} htmlFor="sample-mode">
                      <Select
                        id="sample-mode"
                        value={mode}
                        options={[
                          { value: "live", label: __("Live", "plugin-template") },
                          { value: "test", label: __("Test", "plugin-template") },
                        ]}
                        onChange={(event) => setMode(event.target.value)}
                      />
                    </FormField>
                    <FormActions schema={schema} saving={saving} onSave={() => void onSave()} onClear={() => setConfirmOpen(true)} />
                  </CardContent>
                </Card>
              </TabsContent>
              <TabsContent value="copy">
                <Card>
                  <CardHeader>
                    <CardTitle>{__("Text", "plugin-template")}</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {copy.map((field) => (
                      <FieldControl
                        key={field.name}
                        field={field}
                        value={values[field.name]}
                        onChange={(value) => setValues((current) => ({ ...current, [field.name]: value }))}
                      />
                    ))}
                    <FormActions schema={schema} saving={saving} onSave={() => void onSave()} onClear={() => setConfirmOpen(true)} />
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>
          ) : null}
        </TabsContent>
        <TabsContent value="table">
          {loading ? (
            <TableSkeleton />
          ) : (
            <DataTable
              rows={rows.map((row) => ({ ...row }) as Record<string, unknown>)}
              rowKey={(row) => String(row.id)}
              searchKeys={["title", "status"]}
              searchPlaceholder={__("Search", "plugin-template")}
              emptyTitle={__("No rows", "plugin-template")}
              columns={[
                { key: "title", header: __("Title", "plugin-template"), sortable: true },
                { key: "amount", header: __("Amount", "plugin-template"), numeric: true, sortable: true },
                { key: "status", header: __("Status", "plugin-template") },
              ]}
            />
          )}
        </TabsContent>
        <TabsContent value="fields">
          {loading ? <FormSkeleton /> : <SampleFields />}
        </TabsContent>
      </Tabs>
      <AlertDialog
        open={confirmOpen}
        onOpenChange={setConfirmOpen}
        title={__("Clear the note?", "plugin-template")}
        description={__("Only this form's note is cleared.", "plugin-template")}
        destructive
        confirmText={__("Clear", "plugin-template")}
        onConfirm={() => {
          setValues((current) => ({ ...current, notice: "" }));
          setRows([]);
          toast({ title: __("The note was cleared.", "plugin-template"), variant: "default" });
        }}
      />
    </div>
  );
}

function FormActions({
  schema,
  saving,
  onSave,
  onClear,
}: {
  schema: SettingsSchema;
  saving: boolean;
  onSave: () => void;
  onClear: () => void;
}) {
  return (
    <div className="flex gap-2">
      <Button busy={saving} onClick={onSave}>
        {schema.submit.label}
      </Button>
      <Button variant="outline" onClick={onClear}>
        {__("Clear note", "plugin-template")}
      </Button>
    </div>
  );
}

function FieldControl({
  field,
  value,
  onChange,
}: {
  field: SchemaField;
  value: unknown;
  onChange: (value: unknown) => void;
}) {
  const ltr = field.direction === "ltr";
  if (field.type === "switch") {
    const on = value === 1 || value === true || value === "1";
    return (
      <div className="flex w-fit items-center gap-3">
        <div className="min-w-0 space-y-1">
          <p className="text-sm font-medium text-foreground">{field.label}</p>
          {field.description ? <p className="text-xs text-muted-foreground">{field.description}</p> : null}
        </div>
        <Switch checked={on} onCheckedChange={onChange} aria-label={field.label} />
      </div>
    );
  }
  if (field.type === "textarea") {
    return (
      <FormField label={field.label} htmlFor={field.name} hint={field.description}>
        <Textarea
          id={field.name}
          rows={field.rows ?? 3}
          value={typeof value === "string" ? value : ""}
          placeholder={field.placeholder}
          onChange={(event) => onChange(event.target.value)}
        />
      </FormField>
    );
  }
  return (
    <FormField label={field.label} htmlFor={field.name} hint={field.description}>
      <Input
        id={field.name}
        className={ltr ? "htw-ltr" : undefined}
        dir={ltr ? "ltr" : undefined}
        value={typeof value === "string" ? value : ""}
        placeholder={field.placeholder}
        onChange={(event) => onChange(event.target.value)}
      />
    </FormField>
  );
}

function SampleFields() {
  const [date, setDate] = useState<Date | undefined>();
  const [iban, setIban] = useState("");
  const [mobile, setMobile] = useState("");
  const [nationalId, setNationalId] = useState("");
  const [card, setCard] = useState("");
  const [amount, setAmount] = useState<number | null>(null);
  const [checks, setChecks] = useState<string[]>(["email"]);
  return (
    <Card>
      <CardHeader>
        <CardTitle>{__("Ready-made fields", "plugin-template")}</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4 md:grid-cols-2">
        <FormField label={__("Date", "plugin-template")}>
          <DatePicker value={date ?? null} onChange={(next) => setDate(next ?? undefined)} />
        </FormField>
        <FormField label={__("Amount", "plugin-template")}>
          <AmountInput value={amount} onChange={setAmount} />
        </FormField>
        <FormField label={__("IBAN", "plugin-template")}>
          <IbanInput value={iban} onChange={setIban} />
        </FormField>
        <FormField label={__("Mobile", "plugin-template")}>
          <PhoneInput value={mobile} onChange={setMobile} />
        </FormField>
        <FormField label={__("National ID", "plugin-template")}>
          <NationalIdInput value={nationalId} onChange={setNationalId} />
        </FormField>
        <FormField label={__("Card number", "plugin-template")}>
          <CardNumberInput value={card} onChange={setCard} />
        </FormField>
        <FormField label={__("Notices", "plugin-template")}>
          <CheckboxGroup
            name="notices"
            value={checks}
            onChange={setChecks}
            options={[
              { value: "email", label: __("Email", "plugin-template") },
              { value: "sms", label: __("SMS", "plugin-template") },
            ]}
          />
        </FormField>
      </CardContent>
    </Card>
  );
}
