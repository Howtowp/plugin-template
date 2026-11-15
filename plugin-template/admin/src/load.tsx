import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";

interface LoadApi {
  begin: () => void;
  end: () => void;
}

const LoadCtx = createContext<LoadApi | null>(null);

export function LoadProvider({ children }: { children: ReactNode }) {
  const [pending, setPending] = useState(0);
  const api = useMemo<LoadApi>(
    () => ({
      begin: () => setPending((value) => value + 1),
      end: () => setPending((value) => Math.max(0, value - 1)),
    }),
    [],
  );
  return (
    <LoadCtx.Provider value={api}>
      <div className="htw-progress" data-active={pending > 0 ? "1" : "0"} aria-hidden="true">
        <span />
      </div>
      {children}
    </LoadCtx.Provider>
  );
}

/** Count one in-flight read. Cleanup and completion each finish the task once. */
export function useLoadTask(): () => () => void {
  const api = useContext(LoadCtx);
  return useCallback(() => {
    api?.begin();
    let done = false;
    return () => {
      if (done) return;
      done = true;
      api?.end();
    };
  }, [api]);
}

/** Top bar while the screen or settings section changes. The panel underneath stays put. */
export function useNavigationProgress(key: string): void {
  const start = useLoadTask();
  useEffect(() => {
    const finish = start();
    const id = window.setTimeout(finish, 280);
    return () => {
      window.clearTimeout(id);
      finish();
    };
  }, [key, start]);
}
