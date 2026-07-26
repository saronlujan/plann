import { BanknoteIcon, CreditCardIcon, LandmarkIcon, WalletIcon } from '@lucide/vue';
import type { Component } from 'vue';

// Maps an account kind to its lucide icon. Unknown kinds fall back to the
// simple-account banknote icon.
const icons: Record<string, Component> = {
    account: BanknoteIcon,
    bank: LandmarkIcon,
    wallet: WalletIcon,
    credit_card: CreditCardIcon,
};

export function accountKindIcon(kind: string): Component {
    return icons[kind] ?? BanknoteIcon;
}
