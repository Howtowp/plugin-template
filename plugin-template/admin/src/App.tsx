import { useEffect, useState } from "react";
import { Alert } from "./kit/ui/alert";
import { AlertDialog } from "./kit/ui/alert-dialog";
import { AmountInput } from "./kit/ui/amount-input";
import { Badge } from "./kit/ui/badge";
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
import { Skeleton } from "./kit/ui/skeleton";
import { Switch } from "./kit/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "./kit/ui/tabs";
import { Textarea } from "./kit/ui/textarea";
import { ToastProvider, useToast } from "./kit/ui/toast";
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

export function App({ config }: { config: AdminConfig }) {
  return (
    <ToastProvider position="bottom-start">
      <Screen config={config} />
    </ToastProvider>
  );
}

function Screen({ config }: { config: AdminConfig }) {
  const { toast } = useToast();
  const [schema, setSchema] = useState<SettingsSchema | null>(null);
  const [values, setValues] = useState<Record<string, unknown>>({});
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [rows, setRows] = useState(SAMPLE_ROWS);

  useEffect(() => {
    let active = true;
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
      });
    return () => {
      active = false;
    };
  }, [config]);

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

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <h1 className="text-2xl font-bold">{schema?.title ?? "تنظیمات"}</h1>
        <Badge variant="brand">VibeFarsi</Badge>
      </div>
      {error ? (
        <Alert variant="destructive" title="خطا">
          {error}
        </Alert>
      ) : null}
      <Tabs defaultValue="settings">
        <TabsList>
          <TabsTrigger value="settings">تنظیمات</TabsTrigger>
          <TabsTrigger value="table">فهرست</TabsTrigger>
          <TabsTrigger value="fields">فیلدهای نمونه</TabsTrigger>
        </TabsList>
        <TabsContent value="settings">
          <Card>
            <CardHeader>
              <CardTitle>فرم</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {loading ? <Skeleton className="h-24 w-full" /> : null}
              {!loading && schema && schema.fields.length === 0 ? (
                <EmptyState title="فیلدی نیست" description="اسکیمای تنظیمات خالی است." />
              ) : null}
              {schema?.fields.map((field) => (
                <FieldControl
                  key={field.name}
                  field={field}
                  value={values[field.name]}
                  onChange={(value) => setValues((current) => ({ ...current, [field.name]: value }))}
                />
              ))}
              <div className="flex gap-2">
                <Button onClick={() => void onSave()} disabled={saving || !schema}>
                  {saving ? "در حال ذخیره" : schema?.submit.label ?? "ذخیره"}
                </Button>
                <Button variant="outline" onClick={() => setConfirmOpen(true)}>
                  پاک کردن یادداشت
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
        <TabsContent value="table">
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
        </TabsContent>
        <TabsContent value="fields">
          <SampleFields />
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
    return (
      <FormField label={field.label} hint={field.description}>
        <Switch checked={Boolean(value)} onCheckedChange={onChange} aria-label={field.label} />
      </FormField>
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
