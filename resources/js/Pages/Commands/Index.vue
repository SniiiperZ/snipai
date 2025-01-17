<script setup>
import { ref } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    commands: Array,
});

const form = useForm({
    id: null,
    command: "",
    description: "",
    action: "",
});

const saving = ref(false);
const editing = ref(false);

const saveCommand = () => {
    saving.value = true;
    form.post(route("commands.store"), {
        preserveScroll: true,
        onSuccess: () => {
            saving.value = false;
            resetForm();
        },
        onError: () => {
            saving.value = false;
        },
    });
};

const editCommand = (command) => {
    form.id = command.id;
    form.command = command.command;
    form.description = command.description;
    form.action = command.action;
    editing.value = true;
};

const resetForm = () => {
    form.id = null;
    form.command = "";
    form.description = "";
    form.action = "";
    editing.value = false;
};

const deleteCommand = (id) => {
    if (confirm("Êtes-vous sûr de vouloir supprimer cette commande ?")) {
        router.delete(route("commands.destroy", id));
    }
};
</script>

<template>
    <AppLayout title="Commandes Personnalisées">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Commandes Personnalisées
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6"
                >
                    <!-- Formulaire d'ajout/édition -->
                    <div class="space-y-6">
                        <div>
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            >
                                {{
                                    editing
                                        ? "Modifier la commande"
                                        : "Ajouter une nouvelle commande"
                                }}
                            </h3>

                            <div class="mt-6 space-y-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Commande
                                    </label>
                                    <input
                                        v-model="form.command"
                                        type="text"
                                        class="mt-1 w-full rounded-lg bg-gray-800 border border-gray-700 p-2 text-gray-200"
                                        placeholder="/exemple"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Description
                                    </label>
                                    <input
                                        v-model="form.description"
                                        type="text"
                                        class="mt-1 w-full rounded-lg bg-gray-800 border border-gray-700 p-2 text-gray-200"
                                        placeholder="Description de la commande"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Action
                                    </label>
                                    <textarea
                                        v-model="form.action"
                                        rows="4"
                                        class="mt-1 w-full rounded-lg bg-gray-800 border border-gray-700 p-2 text-gray-200"
                                        placeholder="Action à exécuter"
                                    ></textarea>
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button
                                        v-if="editing"
                                        @click="resetForm"
                                        class="px-4 py-2 text-gray-300 hover:text-white"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        @click="saveCommand"
                                        :disabled="saving"
                                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                                    >
                                        {{
                                            saving
                                                ? "Enregistrement..."
                                                : editing
                                                ? "Mettre à jour"
                                                : "Ajouter"
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Liste des commandes -->
                        <div v-if="props.commands?.length" class="mt-8">
                            <h4
                                class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4"
                            >
                                Vos commandes
                            </h4>
                            <div class="space-y-4">
                                <div
                                    v-for="command in props.commands"
                                    :key="command.id"
                                    class="p-4 bg-gray-900 rounded-lg"
                                >
                                    <div
                                        class="flex justify-between items-start"
                                    >
                                        <div>
                                            <h5
                                                class="text-emerald-500 font-mono"
                                            >
                                                {{ command.command }}
                                            </h5>
                                            <p
                                                class="text-gray-300 text-sm mt-1"
                                            >
                                                {{ command.description }}
                                            </p>
                                            <p
                                                class="text-gray-400 text-sm mt-2"
                                            >
                                                {{ command.action }}
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button
                                                @click="editCommand(command)"
                                                class="text-gray-400 hover:text-gray-200"
                                            >
                                                Modifier
                                            </button>
                                            <button
                                                @click="
                                                    deleteCommand(command.id)
                                                "
                                                class="text-red-400 hover:text-red-200"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
