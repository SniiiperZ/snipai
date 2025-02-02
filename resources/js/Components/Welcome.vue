<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
</script>

<template>
    <div>
        <div class="p-6 lg:p-8 bg-gray-800 border-b border-gray-700">
            <h1 class="text-2xl font-medium text-gray-200">
                Bienvenue chez SnipAI
            </h1>

            <p class="text-gray-400 leading-relaxed">
                Découvrez un assistant intelligent et personnalisable qui
                s'adapte à vos besoins. Profitez d'une expérience de
                conversation naturelle et enrichissante grâce à nos modèles d'IA
                avancés. Personnalisez votre assistant selon vos préférences et
                optimisez vos interactions.
            </p>
        </div>

        <div
            class="bg-gray-800 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8"
        >
            <!-- Section Chat -->
            <div>
                <div class="flex items-center">
                    <svg
                        class="size-6 stroke-emerald-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"
                        />
                    </svg>
                    <h2 class="ms-3 text-xl font-semibold text-gray-200">
                        <a :href="route('ask.index')">Chat AI</a>
                    </h2>
                </div>

                <p class="mt-4 text-gray-400 text-sm leading-relaxed">
                    Commencez une conversation avec l'assistant et découvrez ses
                    capacités. Posez des questions, demandez des conseils ou
                    explorez de nouveaux sujets de manière interactive et
                    naturelle.
                </p>

                <p class="mt-4 text-sm">
                    <a
                        :href="route('ask.index')"
                        class="inline-flex items-center font-semibold text-emerald-400 hover:text-emerald-500"
                    >
                        Démarrer une conversation
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            class="ms-1 size-5 fill-emerald-400"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>
                </p>
            </div>

            <!-- Section Instructions -->
            <div>
                <div class="flex items-center">
                    <svg
                        class="size-6 stroke-blue-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
                        />
                    </svg>
                    <h2 class="ms-3 text-xl font-semibold text-gray-200">
                        <a :href="route('instructions.index')"
                            >Instructions Personnalisées</a
                        >
                    </h2>
                </div>

                <p class="mt-4 text-gray-400 text-sm leading-relaxed">
                    Personnalisez votre expérience en définissant des
                    instructions sur mesure. Apprenez à l'assistant qui vous
                    êtes et comment vous souhaitez qu'il interagisse avec vous.
                </p>

                <p class="mt-4 text-sm">
                    <a
                        :href="route('instructions.index')"
                        class="inline-flex items-center font-semibold text-blue-400 hover:text-blue-500"
                    >
                        Configurer les instructions
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            class="ms-1 size-5 fill-blue-400"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>
                </p>
            </div>

            <!-- Section Comportement -->
            <div>
                <div class="flex items-center">
                    <svg
                        class="size-6 stroke-amber-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"
                        />
                    </svg>
                    <h2 class="ms-3 text-xl font-semibold text-gray-200">
                        <a :href="route('behavior.index')"
                            >Comportement de l'Assistant</a
                        >
                    </h2>
                </div>

                <p class="mt-4 text-gray-400 text-sm leading-relaxed">
                    Définissez le style d'interaction, le ton et le format des
                    réponses de l'assistant pour une expérience parfaitement
                    adaptée à vos préférences.
                </p>

                <p class="mt-4 text-sm">
                    <a
                        :href="route('behavior.index')"
                        class="inline-flex items-center font-semibold text-amber-400 hover:text-amber-500"
                    >
                        Personnaliser le comportement
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            class="ms-1 size-5 fill-amber-400"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>
                </p>
            </div>

            <!-- Section Commandes -->
            <div>
                <div class="flex items-center">
                    <svg
                        class="size-6 stroke-purple-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"
                        />
                    </svg>
                    <h2 class="ms-3 text-xl font-semibold text-gray-200">
                        <a :href="route('commands.index')"
                            >Commandes Personnalisées</a
                        >
                    </h2>
                </div>

                <p class="mt-4 text-gray-400 text-sm leading-relaxed">
                    Créez et gérez vos commandes personnalisées pour automatiser
                    des actions fréquentes et optimiser vos interactions avec
                    l'assistant.
                </p>

                <p class="mt-4 text-sm">
                    <a
                        :href="route('commands.index')"
                        class="inline-flex items-center font-semibold text-purple-400 hover:text-purple-500"
                    >
                        Gérer les commandes
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            class="ms-1 size-5 fill-purple-400"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
