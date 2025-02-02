<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function handleImageError() {
    document.getElementById("screenshot-container")?.classList.add("!hidden");
    document.getElementById("docs-card")?.classList.add("!row-span-1");
    document.getElementById("docs-card-content")?.classList.add("!flex-row");
    document.getElementById("background")?.classList.add("!hidden");
}
</script>

<template>
    <Head title="Welcome" />
    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
        <div
            class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white"
        >
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header
                    class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3"
                >
                    <div class="flex lg:justify-center lg:col-start-2">
                        <img
                            src="/images/snipai.png"
                            alt="Snipai"
                            class="h-20"
                        />
                    </div>
                    <nav v-if="canLogin" class="-mx-3 flex flex-1 justify-end">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Log in
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </header>

                <main class="mt-6">
                    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <!-- Section Chat IA -->
                        <a
                            :href="route('ask.index')"
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-lg ring-1 ring-white/[0.05] transition hover:bg-gray-50 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:bg-zinc-800"
                        >
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-600/10 sm:size-16"
                            >
                                <svg
                                    class="size-6 text-emerald-600"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"
                                    />
                                </svg>
                            </div>

                            <div class="pt-3 sm:pt-5">
                                <h2
                                    class="text-xl font-semibold text-black dark:text-white"
                                >
                                    Chat IA
                                </h2>
                                <p
                                    class="mt-4 text-sm/relaxed text-gray-600 dark:text-gray-400"
                                >
                                    Interagissez avec notre assistant IA avancé
                                    pour obtenir des réponses précises et
                                    adaptées à vos besoins. Profitez d'une
                                    expérience de conversation naturelle et
                                    enrichissante.
                                </p>
                            </div>
                        </a>

                        <!-- Section Instructions -->
                        <a
                            :href="route('instructions.index')"
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-lg ring-1 ring-white/[0.05] transition hover:bg-gray-50 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:bg-zinc-800"
                        >
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-600/10 sm:size-16"
                            >
                                <svg
                                    class="size-6 text-blue-600"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
                                    />
                                </svg>
                            </div>

                            <div class="pt-3 sm:pt-5">
                                <h2
                                    class="text-xl font-semibold text-black dark:text-white"
                                >
                                    Instructions
                                </h2>
                                <p
                                    class="mt-4 text-sm/relaxed text-gray-600 dark:text-gray-400"
                                >
                                    Personnalisez votre expérience en
                                    définissant des instructions sur mesure.
                                    Apprenez à l'assistant qui vous êtes et
                                    comment vous souhaitez interagir.
                                </p>
                            </div>
                        </a>

                        <!-- Section Commandes -->
                        <a
                            :href="route('commands.index')"
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-lg ring-1 ring-white/[0.05] transition hover:bg-gray-50 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:bg-zinc-800"
                        >
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-purple-600/10 sm:size-16"
                            >
                                <svg
                                    class="size-6 text-purple-600"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"
                                    />
                                </svg>
                            </div>

                            <div class="pt-3 sm:pt-5">
                                <h2
                                    class="text-xl font-semibold text-black dark:text-white"
                                >
                                    Commandes Personnalisées
                                </h2>
                                <p
                                    class="mt-4 text-sm/relaxed text-gray-600 dark:text-gray-400"
                                >
                                    Créez et gérez vos commandes personnalisées
                                    pour automatiser des actions fréquentes et
                                    optimiser vos interactions avec l'assistant.
                                </p>
                            </div>
                        </a>

                        <!-- Section Comportement -->
                        <a
                            :href="route('behavior.index')"
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-lg ring-1 ring-white/[0.05] transition hover:bg-gray-50 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:bg-zinc-800"
                        >
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-600/10 sm:size-16"
                            >
                                <svg
                                    class="size-6 text-amber-600"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"
                                    />
                                </svg>
                            </div>

                            <div class="pt-3 sm:pt-5">
                                <h2
                                    class="text-xl font-semibold text-black dark:text-white"
                                >
                                    Comportement de l'Assistant
                                </h2>
                                <p
                                    class="mt-4 text-sm/relaxed text-gray-600 dark:text-gray-400"
                                >
                                    Définissez le style d'interaction, le ton et
                                    le format des réponses de l'assistant pour
                                    une expérience parfaitement adaptée à vos
                                    préférences.
                                </p>
                            </div>
                        </a>
                    </div>
                </main>

                <footer
                    class="py-16 text-center text-sm text-gray-600 dark:text-gray-400"
                >
                    Snipai &copy; 2025 - Tous droits réservés
                </footer>
            </div>
        </div>
    </div>
</template>
