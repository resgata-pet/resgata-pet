import { Button as ButtonPrimitive } from "@base-ui/react/button"
import { cn } from "cn"

/*
 * Variações escritas à mão (sem class-variance-authority, que é Apache-2.0).
 * Tamanhos pensados para toque: o padrão tem 44px de altura.
 */
const base =
  "group/button inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-transparent bg-clip-padding font-medium whitespace-nowrap transition-all outline-none select-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 active:translate-y-px disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"

const variants = {
  default: "bg-primary text-primary-foreground hover:bg-primary/90",
  outline: "border-border bg-card hover:bg-muted hover:text-foreground",
  secondary: "bg-secondary text-secondary-foreground hover:bg-accent",
  ghost: "hover:bg-muted hover:text-foreground",
  destructive: "bg-destructive text-white hover:bg-destructive/90",
  link: "text-primary underline-offset-4 hover:underline",
} as const

const sizes = {
  default: "h-11 px-4 text-base",
  sm: "h-9 px-3 text-sm",
  lg: "h-14 px-6 text-lg [&_svg:not([class*='size-'])]:size-5",
  icon: "size-11",
  "icon-sm": "size-9",
} as const

type ButtonVariant = keyof typeof variants
type ButtonSize = keyof typeof sizes

function buttonVariants({
  variant = "default",
  size = "default",
  className,
}: { variant?: ButtonVariant; size?: ButtonSize; className?: string } = {}) {
  return cn(base, variants[variant], sizes[size], className)
}

function Button({
  className,
  variant = "default",
  size = "default",
  ...props
}: ButtonPrimitive.Props & { variant?: ButtonVariant; size?: ButtonSize }) {
  return (
    <ButtonPrimitive
      data-slot="button"
      className={buttonVariants({ variant, size, className: className as string | undefined })}
      {...props}
    />
  )
}

export { Button, buttonVariants }
export type { ButtonVariant, ButtonSize }
