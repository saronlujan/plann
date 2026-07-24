<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDate, getLocalTimeZone, today } from '@internationalized/date';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Alert, AlertAction, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui/hover-card';
import { Input } from '@/components/ui/input';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { RangeCalendar } from '@/components/ui/range-calendar';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import DefaultLayout from '@/layouts/DefaultLayout.vue';

const name = ref('Plann');
const email = ref('team@plann.test');
const selectedDate = ref('2026-07-24');
const calendarDate = ref(today(getLocalTimeZone()));
const rangeDate = ref({
    start: new CalendarDate(2026, 7, 24),
    end: new CalendarDate(2026, 7, 31),
});
const dialogOpen = ref(false);
const drawerOpen = ref(false);

const initials = computed(() => {
    return name.value
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
});

function notify(message: string): void {
    toast(message);
}

const invoices = [
    {
        id: 'INV001',
        status: 'Paid',
        method: 'Credit Card',
        amount: '$250.00',
    },
    {
        id: 'INV002',
        status: 'Pending',
        method: 'PayPal',
        amount: '$150.00',
    },
    {
        id: 'INV003',
        status: 'Unpaid',
        method: 'Bank Transfer',
        amount: '$99.00',
    },
];
</script>

<template>
    <Head title="Components" />

    <DefaultLayout>
        <section class="space-y-10">
            <header class="space-y-3">
                <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                    Components
                </p>
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">
                    Vitrine de comparação visual
                </h1>
                <p class="max-w-3xl text-sm leading-6 text-zinc-600">
                    Use esta página para validar forma, espaçamento e comportamento dos componentes
                    antes de remover a rota.
                </p>
            </header>

            <div class="grid gap-6 xl:grid-cols-2">
                <article
                    class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm shadow-zinc-950/5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                                Avatar
                            </p>
                            <h2 class="mt-2 text-lg font-semibold text-zinc-950">
                                Estrutura oficial
                            </h2>
                        </div>

                        <Avatar class="size-12">
                            <AvatarImage src="https://github.com/shadcn.png" alt="@shadcn" />
                            <AvatarFallback>CN</AvatarFallback>
                        </Avatar>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <Avatar size="sm">
                            <AvatarImage src="https://github.com/shadcn.png" alt="@shadcn" />
                            <AvatarFallback>CN</AvatarFallback>
                        </Avatar>
                        <Avatar>
                            <AvatarFallback>CN</AvatarFallback>
                        </Avatar>
                        <Avatar size="lg">
                            <AvatarImage
                                src="https://github.com/evilrabbit.png"
                                alt="@evilrabbit"
                            />
                            <AvatarFallback>ER</AvatarFallback>
                        </Avatar>
                        <Avatar class="rounded-lg">
                            <AvatarImage src="https://github.com/maxleiter.png" alt="@maxleiter" />
                            <AvatarFallback class="rounded-lg">ML</AvatarFallback>
                        </Avatar>
                    </div>

                    <div class="mt-8 space-y-3">
                        <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                            Breadcrumb
                        </p>
                        <Breadcrumb>
                            <BreadcrumbList>
                                <BreadcrumbItem>
                                    <BreadcrumbLink href="/"> Home </BreadcrumbLink>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator />
                                <BreadcrumbItem>
                                    <BreadcrumbLink href="/components"> Components </BreadcrumbLink>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator />
                                <BreadcrumbItem>
                                    <BreadcrumbPage> Breadcrumb </BreadcrumbPage>
                                </BreadcrumbItem>
                            </BreadcrumbList>
                        </Breadcrumb>
                    </div>

                    <div class="mt-8 space-y-3">
                        <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                            Hover Card
                        </p>
                        <HoverCard>
                            <HoverCardTrigger as-child>
                                <Button variant="link" class="h-auto px-0"> @plann </Button>
                            </HoverCardTrigger>
                            <HoverCardContent class="w-80">
                                <div class="flex justify-between gap-4">
                                    <Avatar>
                                        <AvatarImage
                                            src="https://github.com/vercel.png"
                                            alt="@plann"
                                        />
                                        <AvatarFallback>PL</AvatarFallback>
                                    </Avatar>
                                    <div class="space-y-1">
                                        <h4 class="text-sm font-semibold">@plann</h4>
                                        <p class="text-sm text-zinc-600">
                                            The Vue Framework – created and maintained by Evan You.
                                        </p>
                                    </div>
                                </div>
                            </HoverCardContent>
                        </HoverCard>
                    </div>

                    <div class="mt-8 space-y-3">
                        <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                            Tooltip
                        </p>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button variant="outline" class="w-fit"> Hover me </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Add to library</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>

                    <div class="mt-8 space-y-3">
                        <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                            Data Table
                        </p>
                        <Table>
                            <TableCaption> A list of your recent invoices. </TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-25"> Invoice </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead class="text-right"> Amount </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="invoice in invoices" :key="invoice.id">
                                    <TableCell class="font-medium">
                                        {{ invoice.id }}
                                    </TableCell>
                                    <TableCell>{{ invoice.status }}</TableCell>
                                    <TableCell>{{ invoice.method }}</TableCell>
                                    <TableCell class="text-right">
                                        {{ invoice.amount }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell colSpan="3"> Total </TableCell>
                                    <TableCell class="text-right"> $499.00 </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm shadow-zinc-950/5"
                >
                    <div class="space-y-3">
                        <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                            Navigation Menu
                        </p>
                        <h2 class="text-lg font-semibold text-zinc-950">
                            Teste do componente em /components
                        </h2>
                        <p class="max-w-2xl text-sm leading-6 text-zinc-600">
                            Passe o mouse ou clique nos itens para validar o menu e os links do
                            componente que montamos.
                        </p>
                    </div>

                    <div class="mt-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                        <NavigationMenu :viewport="false" class="max-w-none">
                            <NavigationMenuList class="justify-start gap-2">
                                <NavigationMenuItem>
                                    <NavigationMenuTrigger> Produtos </NavigationMenuTrigger>
                                    <NavigationMenuContent>
                                        <ul
                                            class="grid w-[320px] gap-2 p-3 md:w-[420px] md:grid-cols-2"
                                        >
                                            <li class="md:row-span-2">
                                                <NavigationMenuLink as-child>
                                                    <a
                                                        href="#"
                                                        class="flex h-full w-full flex-col justify-end rounded-md bg-gradient-to-b from-zinc-100 to-zinc-200 p-4 no-underline outline-none focus:shadow-md"
                                                    >
                                                        <div
                                                            class="text-sm font-medium text-zinc-950"
                                                        >
                                                            Visão geral
                                                        </div>
                                                        <p
                                                            class="mt-1 text-sm leading-snug text-muted-foreground text-zinc-600"
                                                        >
                                                            Estrutura visual do menu com destaque
                                                            para navegação.
                                                        </p>
                                                    </a>
                                                </NavigationMenuLink>
                                            </li>
                                            <li>
                                                <NavigationMenuLink as-child>
                                                    <a
                                                        href="#"
                                                        class="block rounded-md p-3 leading-none no-underline transition-colors outline-none hover:bg-zinc-100 focus:bg-zinc-100"
                                                    >
                                                        <div
                                                            class="text-sm font-medium text-zinc-950"
                                                        >
                                                            Dashboard
                                                        </div>
                                                        <p
                                                            class="mt-1 text-sm leading-snug text-zinc-600"
                                                        >
                                                            Entrada rápida para a tela principal.
                                                        </p>
                                                    </a>
                                                </NavigationMenuLink>
                                            </li>
                                            <li>
                                                <NavigationMenuLink as-child>
                                                    <a
                                                        href="#"
                                                        class="block rounded-md p-3 leading-none no-underline transition-colors outline-none hover:bg-zinc-100 focus:bg-zinc-100"
                                                    >
                                                        <div
                                                            class="text-sm font-medium text-zinc-950"
                                                        >
                                                            Transactions
                                                        </div>
                                                        <p
                                                            class="mt-1 text-sm leading-snug text-zinc-600"
                                                        >
                                                            Lista e filtros de movimentações.
                                                        </p>
                                                    </a>
                                                </NavigationMenuLink>
                                            </li>
                                        </ul>
                                    </NavigationMenuContent>
                                </NavigationMenuItem>

                                <NavigationMenuItem>
                                    <NavigationMenuLink
                                        as-child
                                        :class="navigationMenuTriggerStyle()"
                                    >
                                        <a href="/dashboard">Dashboard</a>
                                    </NavigationMenuLink>
                                </NavigationMenuItem>

                                <NavigationMenuItem>
                                    <NavigationMenuLink
                                        as-child
                                        :class="navigationMenuTriggerStyle()"
                                    >
                                        <a href="/transactions">Transactions</a>
                                    </NavigationMenuLink>
                                </NavigationMenuItem>
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>
                    <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                        Inputs
                    </p>

                    <div class="mt-4 grid gap-3">
                        <Input v-model="name" placeholder="Nome" />
                        <Input v-model="email" placeholder="Email" type="email" />
                        <Input placeholder="Erro" aria-invalid="true" />
                        <DatePicker
                            v-model="selectedDate"
                            label="Data"
                            hint="Campo de data para teste"
                        />
                    </div>

                    <div
                        class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600"
                    >
                        <p>
                            Nome atual:
                            <span class="font-medium text-zinc-950">{{ name }}</span>
                        </p>
                        <p>
                            Email atual:
                            <span class="font-medium text-zinc-950">{{ email }}</span>
                        </p>
                        <p>
                            Iniciais:
                            <span class="font-medium text-zinc-950">{{ initials }}</span>
                        </p>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm shadow-zinc-950/5 xl:col-span-2"
                >
                    <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                        Buttons
                    </p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <Button @click="notify('Default button clicked')">Default</Button>
                        <Button variant="outline" @click="notify('Outline button clicked')">
                            Outline
                        </Button>
                        <Button variant="secondary">Secondary</Button>
                        <Button variant="ghost">Ghost</Button>
                        <Button variant="destructive">Destructive</Button>
                        <Button variant="link">Link</Button>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm shadow-zinc-950/5 xl:col-span-2"
                >
                    <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                        Alerts
                    </p>

                    <div class="mt-4 grid gap-4 xl:grid-cols-2">
                        <Alert>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="size-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m9.042 2.418A2.25 2.25 0 0 1 18.894 18H5.106a2.25 2.25 0 0 1-2.148-2.832L6.364 5.716A2.25 2.25 0 0 1 8.512 4.5h6.976a2.25 2.25 0 0 1 2.148 1.216l3.406 8.702ZM12 15.75h.007v.008H12v-.008Z"
                                />
                            </svg>
                            <AlertTitle>Success! Your changes have been saved</AlertTitle>
                            <AlertDescription>
                                This is the default alert shape with icon, title and description.
                            </AlertDescription>
                        </Alert>

                        <Alert variant="destructive">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="size-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m9.042 2.418A2.25 2.25 0 0 1 18.894 18H5.106a2.25 2.25 0 0 1-2.148-2.832L6.364 5.716A2.25 2.25 0 0 1 8.512 4.5h6.976a2.25 2.25 0 0 1 2.148 1.216l3.406 8.702ZM12 15.75h.007v.008H12v-.008Z"
                                />
                            </svg>
                            <AlertTitle>Unable to process your payment.</AlertTitle>
                            <AlertDescription>
                                Please verify your billing information and try again.
                            </AlertDescription>
                        </Alert>

                        <Alert>
                            <AlertTitle>Action required</AlertTitle>
                            <AlertDescription>
                                This alert includes an action aligned with the top-right area.
                            </AlertDescription>
                            <AlertAction>
                                <Button size="sm" variant="outline">Review</Button>
                            </AlertAction>
                        </Alert>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm shadow-zinc-950/5 xl:col-span-2"
                >
                    <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                        Dialog and Drawer
                    </p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <Button variant="outline" @click="dialogOpen = true"> Open dialog </Button>
                        <Button variant="outline" @click="drawerOpen = true"> Open drawer </Button>
                    </div>

                    <Dialog v-model:open="dialogOpen">
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Dialog preview</DialogTitle>
                                <DialogDescription>
                                    This uses the current dialog primitives so you can compare
                                    spacing and overlay behavior.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" @click="dialogOpen = false">
                                    Cancel
                                </Button>
                                <Button @click="dialogOpen = false">Confirm</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Drawer v-model:open="drawerOpen">
                        <DrawerContent>
                            <DrawerHeader>
                                <DrawerTitle>Drawer preview</DrawerTitle>
                                <DrawerDescription>
                                    Use this surface to validate the mobile pattern.
                                </DrawerDescription>
                            </DrawerHeader>
                            <DrawerFooter>
                                <Button variant="outline" @click="drawerOpen = false">
                                    Close
                                </Button>
                            </DrawerFooter>
                        </DrawerContent>
                    </Drawer>
                </article>

                <article
                    class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm shadow-zinc-950/5 xl:col-span-2"
                >
                    <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                        Calendar Primitives
                    </p>

                    <div class="mt-4 grid gap-6 xl:grid-cols-2">
                        <Calendar
                            v-model="calendarDate"
                            class="rounded-2xl border border-zinc-200"
                        />
                        <RangeCalendar
                            v-model="rangeDate"
                            :number-of-months="2"
                            class="rounded-2xl border border-zinc-200"
                        />
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm shadow-zinc-950/5 xl:col-span-2"
                >
                    <p class="text-xs font-medium tracking-[0.3em] text-zinc-500 uppercase">
                        Toasts
                    </p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <Button variant="outline" @click="notify('Toast example')"
                            >Show toast</Button
                        >
                        <Button variant="outline" @click="notify('Success state')"
                            >Success toast</Button
                        >
                    </div>
                </article>
            </div>
        </section>
    </DefaultLayout>
</template>
