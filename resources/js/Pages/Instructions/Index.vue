<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    instructions: Object,
});

const form = useForm({
    content: props.instructions?.content || "",
});

const saving = ref(false);

const saveInstructions = () => {
    saving.value = true;
    form.post(route("instructions.store"), {
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
    <AppLayout title="Instructions Personnalisées">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                Instructions Personnalisées
            </h2>
        </template>

        <div class="bg-gray-900 py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6"
                >
                    <div class="space-y-6">
                        <!-- Section À propos de vous -->
                        <div>
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            >
                                À propos de vous
                            </h3>
                            <p
                                class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                            >
                                Cette section est votre espace pour informer
                                l'assistant sur qui vous êtes, vos intérêts, et
                                votre domaine d'expertise.
                            </p>

                            <div class="mt-6">
                                <textarea
                                    v-model="form.content"
                                    rows="6"
                                    class="w-full rounded-lg bg-gray-800 border border-gray-700 p-4 text-gray-200 placeholder-gray-400 focus:outline-none focus:border-emerald-600"
                                    placeholder="Présentez-vous brièvement..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Section Exemples -->
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
                                    "Je suis un entrepreneur dans le secteur des
                                    technologies vertes, cherchant à innover
                                    dans le domaine de l'énergie renouvelable."
                                </div>
                                <div class="p-4 bg-gray-900 rounded-lg">
                                    "Je suis une artiste peintre explorant les
                                    liens entre l'art traditionnel et les médias
                                    numériques."
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button
                                @click="saveInstructions"
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
