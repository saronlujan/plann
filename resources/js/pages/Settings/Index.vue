<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { dashboard as dashboardRoute } from '@/routes';

type CurrencyOption = {
    id: number;
    code: string;
    name: string;
    symbol: string;
    is_active: boolean;
};

type LocaleOption = {
    value: 'pt' | 'es' | 'en';
    label: string;
};

const { currencies } = defineProps<{
    currencies: CurrencyOption[];
}>();

const search = ref('');

const filteredCurrencies = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return currencies;
    }

    return currencies.filter((currency) => {
        return [currency.code, currency.name, currency.symbol]
            .join(' ')
            .toLowerCase()
            .includes(term);
    });
});
</script>

<template>
    <Head title="Configurações" />

    <DefaultLayout>
        <section
            class="rounded-3xl border border-zinc-200 bg-white p-8 shadow-sm shadow-zinc-950/5"
        >
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-medium tracking-[0.2em] text-zinc-500 uppercase">
                        Configurações
                    </p>
                    <h1 class="text-3xl font-semibold text-zinc-950">Ajustes do tenant</h1>
                    <p class="text-sm text-zinc-600">Dois cards, duas ações.</p>
                </div>

                <Link
                    :href="dashboardRoute().url"
                    class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 px-4 py-3 font-semibold text-zinc-950 transition hover:bg-zinc-100"
                >
                    Voltar
                </Link>
            </div>

            <Alert class="mt-6">
                <AlertTitle>Salvamento independente</AlertTitle>
                <AlertDescription> Moedas gravam separadamente. </AlertDescription>
            </Alert>

            <div class="mt-8 space-y-4">
                <Form
                    action="/settings/currencies"
                    method="patch"
                    class="rounded-3xl border border-zinc-200 bg-zinc-50 p-6"
                    #default="{ processing, errors }"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium tracking-[0.2em] text-zinc-500 uppercase">
                                Moedas
                            </p>
                            <h2 class="mt-2 text-2xl font-semibold text-zinc-950">Ativar moedas</h2>
                        </div>

                        <span
                            class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-semibold tracking-[0.2em] text-zinc-600 uppercase"
                        >
                            Card
                        </span>
                    </div>

                    <div class="mt-6">
                        <Input v-model="search" type="text" placeholder="Filtrar moedas" />
                    </div>

                    <div class="mt-6 space-y-3">
                        <label
                            v-for="currency in filteredCurrencies"
                            :key="currency.id"
                            class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white px-4 py-3 transition hover:border-zinc-300"
                        >
                            <div>
                                <p class="font-semibold text-zinc-950">
                                    {{ currency.code }} · {{ currency.name }}
                                </p>
                                <p class="text-sm text-zinc-500">Símbolo {{ currency.symbol }}</p>
                            </div>

                            <input
                                type="checkbox"
                                name="currency_ids[]"
                                :value="currency.id"
                                :checked="currency.is_active"
                                class="h-5 w-5 rounded border-zinc-300 bg-white text-zinc-950 focus:ring-zinc-950"
                            />
                        </label>
                    </div>

                    <p v-if="errors.currency_ids" class="mt-4 text-sm text-red-600">
                        {{ errors.currency_ids }}
                    </p>

                    <Button type="submit" :disabled="processing" class="mt-6 w-full">
                        Salvar moedas
                    </Button>
                </Form>
            </div>
        </section>
    </DefaultLayout>
</template>
