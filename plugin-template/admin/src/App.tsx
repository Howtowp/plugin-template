import { useEffect, useState } from "react";
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
  { id: "1", title: "یادداشت نمونه", amount: 1250000, status: "فعال" },
  { id: "2", title: "سفارش آزمایشی", amount: 890000, status: "بسته" },
];

const SCREENS = [
  { id: "settings", label: "تنظیمات" },
  { id: "table", label: "فهرست" },
  { id: "fields", label: "فیلدهای نمونه" },
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
        if (active) setError(reason instanceof Error ? reason.message : "خواندن تنظیمات انجام نشد.");
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
      const message = reason instanceof Error ? reason.message : "ذخیره انجام نشد.";
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
        <Alert variant="destructive" title="خطا">
          {error}
        </Alert>
      ) : null}
      <Tabs value={screen} defaultValue="settings" onValueChange={(value) => setScreen(value as (typeof SCREENS)[number]["id"])} variant="underline">
        <header className="mb-4 space-y-3">
          <div>
            <p className="text-xs font-semibold text-brand">How to WP</p>
            <h1 className="text-2xl font-bold text-foreground">{schema?.title ?? "قالب افزونه"}</h1>
          </div>
          <TabsList aria-label="بخش‌های افزونه" className="w-full">
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
            <EmptyState title="فیلدی نیست" description="اسکیمای تنظیمات خالی است." />
          ) : null}
          {!loading && schema && schema.fields.length > 0 ? (
            <Tabs value={section} defaultValue="general" onValueChange={setSection} variant="underline">
              <TabsList aria-label="بخش‌های تنظیمات">
                <TabsTrigger value="general">عمومی</TabsTrigger>
                <TabsTrigger value="copy">متن‌ها</TabsTrigger>
              </TabsList>
              <TabsContent value="general">
                <Card>
                  <CardHeader>
                    <CardTitle>عمومی</CardTitle>
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
                    <FormField label="حالت" htmlFor="sample-mode">
                      <Select
                        id="sample-mode"
                        value={mode}
                        options={[
                          { value: "live", label: "زنده" },
                          { value: "test", label: "آزمایشی" },
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
                    <CardTitle>متن‌ها</CardTitle>
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
              searchPlaceholder="جستجو"
              emptyTitle="ردیفی نیست"
              columns={[
                { key: "title", header: "عنوان", sortable: true },
                { key: "amount", header: "مبلغ", numeric: true, sortable: true },
                { key: "status", header: "وضعیت" },
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
        title="یادداشت پاک شود؟"
        description="فقط مقدار همین فرم خالی می‌شود."
        destructive
        confirmText="پاک شود"
        onConfirm={() => {
          setValues((current) => ({ ...current, notice: "" }));
          setRows([]);
          toast({ title: "یادداشت خالی شد.", variant: "default" });
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
        پاک کردن یادداشت
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
        <CardTitle>فیلدهای آماده</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4 md:grid-cols-2">
        <FormField label="تاریخ">
          <DatePicker value={date ?? null} onChange={(next) => setDate(next ?? undefined)} />
        </FormField>
        <FormField label="مبلغ">
          <AmountInput value={amount} onChange={setAmount} />
        </FormField>
        <FormField label="شبا">
          <IbanInput value={iban} onChange={setIban} />
        </FormField>
        <FormField label="موبایل">
          <PhoneInput value={mobile} onChange={setMobile} />
        </FormField>
        <FormField label="کد ملی">
          <NationalIdInput value={nationalId} onChange={setNationalId} />
        </FormField>
        <FormField label="شماره کارت">
          <CardNumberInput value={card} onChange={setCard} />
        </FormField>
        <FormField label="اعلان‌ها">
          <CheckboxGroup
            name="notices"
            value={checks}
            onChange={setChecks}
            options={[
              { value: "email", label: "ایمیل" },
              { value: "sms", label: "پیامک" },
            ]}
          />
        </FormField>
      </CardContent>
    </Card>
  );
}
