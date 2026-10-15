import type { ComponentProps } from "react"
import { cn } from "cn"

/* Variações escritas à mão (sem class-variance-authority, que é Apache-2.0). */
const base =
  "inline-flex h-6 w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-full border border-transparent px-2.5 text-xs font-medium whitespace-nowrap"

const variants = {
  default: "bg-primary text-primary-foreground",
  secondary: "bg-secondary text-secondary-foreground",
  outline: "border-border text-foreground",
  muted: "bg-muted text-muted-foreground",
  warning: "bg-amber-100 text-amber-900",
  info: "bg-sky-100 text-sky-900",
  destructive: "bg-red-100 text-red-900",
} as const

type BadgeVariant = keyof typeof variants

function Badge({
  className,
  variant = "default",
  ...props
}: ComponentProps<"span"> & { variant?: BadgeVariant }) {
  return <span data-slot="badge" className={cn(base, variants[variant], className)} {...props} />
}

export { Badge }
export type { BadgeVariant }
