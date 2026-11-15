import { Skeleton } from "./skeleton";

/** Full settings panel while the schema is in flight. Height matches a short form. */
export function FormSkeleton() {
  return (
    <div className="space-y-3" aria-busy="true">
      <Skeleton className="h-10 w-full" />
      <Skeleton className="h-24 w-full" />
      <Skeleton className="h-10 w-2/3" />
      <Skeleton className="h-10 w-40" />
    </div>
  );
}

/** Table body placeholder. Row count stays fixed so the card does not jump. */
export function TableSkeleton({ rows = 5 }: { rows?: number }) {
  return (
    <div className="space-y-2" aria-busy="true">
      <Skeleton className="h-10 w-full" />
      {Array.from({ length: rows }, (_, index) => (
        <Skeleton key={index} className="h-11 w-full" />
      ))}
    </div>
  );
}

/** First paint of a screen: title plus a form-shaped block. */
export function ScreenSkeleton() {
  return (
    <div className="space-y-4" aria-busy="true">
      <Skeleton className="h-8 w-48" />
      <FormSkeleton />
    </div>
  );
}
