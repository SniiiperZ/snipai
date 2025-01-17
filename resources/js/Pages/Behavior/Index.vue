<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    behavior: Object,
});

const form = useForm({
    behavior: props.behavior?.behavior || "",
});

const saving = ref(false);

const saveBehavior = () => {
    saving.value = true;
    form.post(route("behavior.store"), {
        preserveScroll: true,
        onSuccess: () => {
            saving.value = false;
        },
        onError: () => {
            saving.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="Comportement de l'Assistant">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Comportement de l'Assistant
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6"
                >
                    <div class="space-y-6">
                        <div>
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            >
                                Style d'Interaction
                            </h3>
                            <p
                                class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                            >
                                Définissez comment vous souhaitez que
                                l'assistant interagisse avec vous, incluant le
                                ton, le format et le style d'explication.
                            </p>

                            <div class="mt-6">
                                <textarea
                                    v-model="form.behavior"
                                    rows="6"
                                    class="w-full rounded-lg bg-gray-800 border border-gray-700 p-4 text-gray-200 placeholder-gray-400 focus:outline-none focus:border-emerald-600"
                                    placeholder="Décrivez vos préférences d'interaction..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Exemples -->
                        <div class="mt-8">
                            <h4
                                class="text-md font-medium text-gray-900 dark:text-gray-100"
                            >
                                Exemples
                            </h4>
                            <div
                                class="mt-2 space-y-4 text-sm text-gray-600 dark:text-gray-400"
                            >
                                <div class="p-4 bg-gray-900 rounded-lg">
                                    "J'apprécie un ton professionnel mais
                                    accessible, avec des explications étayées
                                    par des données et des recherches récentes."
                                </div>
                                <div class="p-4 bg-gray-900 rounded-lg">
                                    "Utilisez un langage simple et des analogies
                                    pour expliquer des concepts complexes,
                                    rendant l'apprentissage plus intuitif."
                                </div>
                                <div class="p-4 bg-gray-900 rounded-lg">
                                    "J'aime quand les réponses sont organisées
                                    en listes numérotées ou à puces, cela aide à
                                    structurer l'information."
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button
                                @click="saveBehavior"
                                :disabled="saving"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {{
                                    saving ? "Enregistrement..." : "Enregistrer"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
