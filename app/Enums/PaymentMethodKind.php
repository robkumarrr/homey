<?php

namespace App\Enums;

/**
 * What a payment method is. The frontend picks its icon and label from this.
 */
enum PaymentMethodKind: string
{
    case CreditCard = 'credit_card';
    case BankAccount = 'bank_account';
}
