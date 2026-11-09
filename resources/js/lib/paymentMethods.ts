import { CreditCard, Landmark } from '@lucide/vue';
import type { Component } from 'vue';
import type { PaymentMethod, PaymentMethodKind } from '@/types';

export const paymentMethodKinds: Record<
    PaymentMethodKind,
    { label: string; icon: Component }
> = {
    credit_card: { label: 'Credit card', icon: CreditCard },
    bank_account: { label: 'Bank account', icon: Landmark },
};

/** "Credit card ··1234", or just the kind when no digits were kept. */
export function describePaymentMethod(method: PaymentMethod): string {
    const kind = paymentMethodKinds[method.kind].label;

    return method.last_four ? `${kind} ··${method.last_four}` : kind;
}
