import { Badge, type BadgeVariant } from '@/components/ui/badge';
import { config } from '@/lib/config';

const variants: Record< string, BadgeVariant > = {
	available: 'secondary',
	reserved: 'warning',
	adopted: 'info',
	treatment: 'outline',
	deceased: 'muted',
};

export function StatusBadge( { status }: { status: string } ) {
	return <Badge variant={ variants[ status ] ?? 'outline' }>{ config.options.statuses[ status ] ?? status }</Badge>;
}
