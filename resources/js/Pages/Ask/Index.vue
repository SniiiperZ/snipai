<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import MarkdownIt from "markdown-it";
import hljs from "highlight.js";
import "highlight.js/styles/atom-one-dark.css"; // Ajout du thème de coloration

// Props reçues via Inertia (conforme au contrôleur)
const props = defineProps({
    flash: Object, // Messages flash (success ou erreur)
    models: Array, // Liste des modèles récupérés par ChatService
    selectedModel: String, // Modèle par défaut
});

// Création d'un tableau pour stocker l'historique des conversations
const conversationHistory = ref([]);

// Initialisation des états
const form = useForm({
    message: "",
    model: props.selectedModel || "", // Définit le modèle sélectionné par défaut
});

// Fonction simplifiée pour copier le texte
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        // On pourrait ajouter une notification toast ici si besoin
    });
};

// Configuration simplifiée de MarkdownIt
const md = new MarkdownIt({
    highlight: (code, lang) => {
        if (lang && hljs.getLanguage(lang)) {
            try {
                const highlighted = hljs.highlight(code, { language: lang }).value;
                return `
                    <div class="code-block">
                        <button class="copy-button" onclick="navigator.clipboard.writeText(\`${code.replace(/`/g, '\\`')}\`)">
                            Copier
                        </button>
                        <pre><code class="hljs">${highlighted}</code></pre>
                    </div>`;
            } catch (e) {
                console.error(e);
            }
        }
        return `<pre><code>${md.utils.escapeHtml(code)}</code></pre>`;
    }
});

// Soumission du formulaire
const handleSubmit = () => {
    const question = form.message;

    form.post("/ask", {
        onSuccess: () => {
            // Ajout de la nouvelle conversation à l'historique
            if (props.flash.message) {
                conversationHistory.value.push({
                    question: question,
                    answer: props.flash.message,
                    timestamp: new Date(),
                });
            }
            form.reset("message"); // Réinitialise seulement le champ "message"
        },
        preserveScroll: true, // Garde la position du scroll
        onError: (errors) => {
            console.error("Erreurs lors de la soumission :", errors);
        },
    });
};

// Fonction pour rendre le Markdown en HTML
const renderMarkdown = (text) => {
    return md.render(text || "");
};
</script>

<template>
    <div class="min-h-screen bg-gray-900 p-8 text-gray-100">
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Affichage des erreurs -->
            <div
                v-if="props.flash.error"
                class="bg-red-900/50 text-red-200 p-6 rounded-lg border border-red-700/50 shadow-xl"
            >
                <p class="flex items-center">
                    <span class="material-icons mr-2">error</span>
                    {{ props.flash.error }}
                </p>
            </div>

            <!-- Historique des conversations -->
            <div class="space-y-6">
                <div
                    v-for="(conversation, index) in conversationHistory"
                    :key="index"
                    class="space-y-4"
                >
                    <!-- Question -->
                    <div
                        class="bg-blue-900/30 p-6 rounded-lg border border-blue-700/50 shadow-lg"
                    >
                        <div class="flex justify-between items-start">
                            <h3 class="text-blue-300 text-sm font-medium mb-2">
                                Ma question:
                            </h3>
                            <span class="text-xs text-gray-400">
                                {{
                                    new Date(
                                        conversation.timestamp
                                    ).toLocaleTimeString()
                                }}
                            </span>
                        </div>
                        <p class="text-gray-200">{{ conversation.question }}</p>
                    </div>

                    <!-- Réponse -->
                    <div
                        class="bg-emerald-900/30 p-6 rounded-lg border border-emerald-700/50 shadow-lg prose prose-invert max-w-none"
                    >
                        <h3 class="text-emerald-300 text-sm font-medium mb-2">
                            Réponse:
                        </h3>
                        <div v-html="renderMarkdown(conversation.answer)"></div>
                    </div>
                </div>
            </div>

            <!-- Formulaire -->
            <form
                @submit.prevent="handleSubmit"
                class="space-y-6 sticky bottom-8 bg-gray-900/95 p-6 rounded-lg border border-gray-700/50 backdrop-blur-sm"
            >
                <!-- Champ pour le message -->
                <div class="relative">
                    <textarea
                        v-model="form.message"
                        class="w-full p-4 bg-gray-800/50 border border-gray-700/50 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 text-gray-100 placeholder-gray-400 resize-none min-h-[120px] transition-all duration-300"
                        placeholder="Posez votre question ici..."
                        required
                    />
                </div>

                <!-- Sélecteur pour le modèle -->
                <select
                    v-model="form.model"
                    class="w-full p-4 bg-gray-800/50 border border-gray-700/50 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 text-gray-100 transition-all duration-300"
                    required
                >
                    <option disabled value="" class="bg-gray-800">
                        Choisissez un modèle
                    </option>
                    <option
                        v-for="model in props.models"
                        :key="model.id"
                        :value="model.name"
                        class="bg-gray-800"
                    >
                        {{ model.name }}
                    </option>
                </select>

                <!-- Bouton pour soumettre -->
                <button
                    type="submit"
                    class="w-full p-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-lg transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 focus:ring-offset-gray-900"
                >
                    Envoyer
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Style spécifique si nécessaire */
.prose-invert {
    --tw-prose-body: theme("colors.gray.300");
    --tw-prose-headings: theme("colors.gray.200");
    --tw-prose-links: theme("colors.blue.400");
    --tw-prose-code: theme("colors.gray.200");
    --tw-prose-pre-code: theme("colors.gray.200");
    --tw-prose-pre-bg: theme("colors.gray.800");
    --tw-prose-quotes: theme("colors.gray.200");
}

/* Animation de fade pour les messages */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Style pour les blocs de code */
.code-block {
    position: relative;
    margin: 1rem 0;
    background: #1e1e1e;
    border-radius: 0.5rem;
}

.code-block pre {
    margin: 0;
    padding: 2.5rem 1rem 1rem;
}

.copy-button {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
}

.copy-button:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Assurer que le code est bien affiché */
.hljs {
    background: transparent !important;
    padding: 0 !important;
}
</style>
