<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid'

defineProps({
    paginator: {
        type: Object,
        required: true
    }
})
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        class="flex items-center justify-between px-4 py-3 sm:px-0"
    >
        <div class="flex flex-1 justify-between sm:hidden">
            <Link
                v-if="paginator.prev_page_url"
                :href="paginator.prev_page_url"
                preserve-scroll
                class="relative inline-flex items-center rounded-md border border-border bg-white px-4 py-2 text-sm font-medium text-foreground hover:bg-accent"
            >
                Previous
            </Link>
            <span
                v-else
                class="relative inline-flex items-center rounded-md border border-border bg-muted px-4 py-2 text-sm font-medium text-muted-foreground"
            >
                Previous
            </span>

            <Link
                v-if="paginator.next_page_url"
                :href="paginator.next_page_url"
                preserve-scroll
                class="relative ml-3 inline-flex items-center rounded-md border border-border bg-white px-4 py-2 text-sm font-medium text-foreground hover:bg-accent"
            >
                Next
            </Link>
            <span
                v-else
                class="relative ml-3 inline-flex items-center rounded-md border border-border bg-muted px-4 py-2 text-sm font-medium text-muted-foreground"
            >
                Next
            </span>
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-foreground">
                    Showing
                    <span class="font-medium">{{ paginator.from }}</span>
                    to
                    <span class="font-medium">{{ paginator.to }}</span>
                    of
                    <span class="font-medium">{{ paginator.total }}</span>
                    results
                </p>
            </div>
            <div>
                <ul class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                    <li>
                        <Link
                            v-if="paginator.prev_page_url"
                            :href="paginator.prev_page_url"
                            preserve-scroll
                            class="relative inline-flex items-center rounded-l-md bg-white px-2 py-2 text-muted-foreground ring-1 ring-inset ring-border hover:bg-accent"
                        >
                            <span class="sr-only">Previous</span>
                            <ChevronLeftIcon class="h-5 w-5" />
                        </Link>
                        <span
                            v-else
                            class="relative inline-flex items-center rounded-l-md bg-white px-2 py-2 text-muted-foreground ring-1 ring-inset ring-border"
                        >
                            <span class="sr-only">Previous</span>
                            <ChevronLeftIcon class="h-5 w-5" />
                        </span>
                    </li>

                    <li v-for="link in paginator.links.slice(1, -1)" :key="link.label">
                        <Link
                            v-if="link.url && !link.active"
                            :href="link.url"
                            preserve-scroll
                            class="relative inline-flex items-center bg-white px-4 py-2 text-sm font-semibold text-foreground ring-1 ring-inset ring-border hover:bg-accent"
                        >
                            {{ link.label }}
                        </Link>
                        <span
                            v-else-if="link.active"
                            class="relative z-10 inline-flex items-center bg-white px-4 py-2 text-sm font-semibold text-primary ring-1 ring-inset ring-primary"
                        >
                            {{ link.label }}
                        </span>
                        <span
                            v-else
                            class="relative inline-flex items-center bg-white px-4 py-2 text-sm font-semibold text-muted-foreground ring-1 ring-inset ring-border"
                        >
                            {{ link.label }}
                        </span>
                    </li>

                    <li>
                        <Link
                            v-if="paginator.next_page_url"
                            :href="paginator.next_page_url"
                            preserve-scroll
                            class="relative inline-flex items-center rounded-r-md bg-white px-2 py-2 text-muted-foreground ring-1 ring-inset ring-border hover:bg-accent"
                        >
                            <span class="sr-only">Next</span>
                            <ChevronRightIcon class="h-5 w-5" />
                        </Link>
                        <span
                            v-else
                            class="relative inline-flex items-center rounded-r-md bg-white px-2 py-2 text-muted-foreground ring-1 ring-inset ring-border"
                        >
                            <span class="sr-only">Next</span>
                            <ChevronRightIcon class="h-5 w-5" />
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>
