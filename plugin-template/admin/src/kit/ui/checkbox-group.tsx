import { Checkbox } from "./checkbox";

export interface CheckboxGroupOption {
  value: string;
  label: string;
  description?: string;
}

export interface CheckboxGroupProps {
  name?: string;
  options: CheckboxGroupOption[];
  value: string[];
  onChange: (value: string[]) => void;
}

/** A vertical group of checkboxes. Not in the upstream registry; built on Checkbox. */
export function CheckboxGroup({ name = "group", options, value, onChange }: CheckboxGroupProps) {
  return (
    <div role="group" className="flex flex-col gap-2">
      {options.map((option) => (
        <Checkbox
          key={option.value}
          id={`${name}-${option.value}`}
          label={option.label}
          description={option.description}
          checked={value.includes(option.value)}
          onCheckedChange={(checked) => {
            onChange(checked ? [...value, option.value] : value.filter((item) => item !== option.value));
          }}
        />
      ))}
    </div>
  );
}
