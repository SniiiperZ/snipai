<script setup>
import { ref, nextTick, computed, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import MarkdownIt from "markdown-it";
import hljs from "highlight.js";
import "highlight.js/styles/atom-one-dark.css"; // Thème

// Props via Inertia
const props = defineProps({
    flash: Object,
    models: Array,
    selectedModel: String,
});

// Historique des conversations
const conversationHistory = ref([]);

// Élément où on scroll
const messagesContainer = ref(null);

// État de chargement
const isLoading = ref(false);

// Formulaire
const form = useForm({
    message: "",
    model: props.selectedModel || "",
});

// Copie vers le presse-papiers (avec fallback hors HTTPS)
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard
            .writeText(text)
            .then(() => {
                alert("Texte copié !");
            })
            .catch((error) => {
                console.error("Échec de la copie via clipboard API :", error);
            });
    } else {
        try {
            const textarea = document.createElement("textarea");
            textarea.value = text;
            textarea.style.position = "fixed";
            textarea.style.left = "-9999px";
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            document.body.removeChild(textarea);
            alert("Texte copié (fallback) !");
        } catch (error) {
            console.error("Échec du fallback :", error);
        }
    }
}

// Configuration de MarkdownIt
const md = new MarkdownIt({
    html: false,
    highlight: (code, lang) => {
        // ID unique pour le <code>
        const uniqueId = `code-${Math.random().toString(36).substr(2, 9)}`;
        // Coloration syntaxique ou échappement
        const highlightedCode =
            lang && hljs.getLanguage(lang)
                ? hljs.highlight(code, { language: lang }).value
                : md.utils.escapeHtml(code);

        // Ici, on retourne un bloc plus élégant,
        // avec une bordure, un fond sombre, un bouton stylé, etc.
        return `
      <div class="relative border border-gray-700 rounded-lg bg-gray-800 my-4 code-block shadow-md">
        <!-- Bouton Copier -->
        <button
          class="absolute right-2 top-2 inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-gray-200 bg-gray-700 hover:bg-gray-600 rounded focus:outline-none focus:ring-2 focus:ring-emerald-600 copy-btn"
          data-code="${code.replace(/"/g, "&quot;")}"
        >
          <!-- Icône (optionnelle) -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 16h8m-8-4h8m-8-4h8M4 6h16v12H4z"/>
          </svg>
          Copier
        </button>

        <!-- Zone de code -->
        <pre class="overflow-x-auto text-gray-300">
<code id="${uniqueId}" class="hljs ${lang || ""}">${highlightedCode}</code>
        </pre>
      </div>
    `;
    },
});

// Rendu Markdown
const renderMarkdown = (text) => md.render(text || "");

// Soumission de question
function handleSubmit() {
    const question = form.message;
    isLoading.value = true;

    form.post("/ask", {
        onSuccess: () => {
            if (props.flash.message) {
                conversationHistory.value.push({
                    question: question,
                    answer: props.flash.message,
                    timestamp: new Date(),
                });
                form.reset("message");
                scrollToBottom();
            }
            isLoading.value = false;
        },
        preserveScroll: true,
        onError: (errors) => {
            console.error("Erreurs :", errors);
            isLoading.value = false;
        },
    });
}

// Scroll auto
function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop =
                messagesContainer.value.scrollHeight;
        }
    });
}

const filteredModels = computed(() => {
    return props.models.filter(
        (model) =>
            model.name !== "meta-llama/llama-3.2-11b-vision-instruct:free"
    );
});

onMounted(() => {
    if (!form.model) {
        form.model = "meta-llama/llama-3.2-11b-vision-instruct:free";
    }
    // Gérer le clic global pour copier
    document.addEventListener("click", (event) => {
        const target = event.target;
        if (target && target.classList.contains("copy-btn")) {
            const codeToCopy = target.getAttribute("data-code");
            if (codeToCopy) {
                copyToClipboard(codeToCopy);
            }
        }
    });
});
</script>

<template>
    <div class="flex h-screen flex-col bg-gray-900">
        <!-- En-tête -->
        <header
            class="sticky top-0 z-10 border-b border-gray-700/50 bg-gray-900/80 backdrop-blur"
        >
            <div class="mx-auto max-w-5xl px-4 py-3">
                <select
                    v-model="form.model"
                    class="w-full max-w-xs rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-200 border border-gray-700"
                    required
                >
                    <option
                        value="meta-llama/llama-3.2-11b-vision-instruct:free"
                    >
                        Llama 3.2 (Recommandé)
                    </option>
                    <option
                        v-for="model in filteredModels"
                        :key="model.id"
                        :value="model.name"
                    >
                        {{ model.name }}
                    </option>
                </select>
            </div>
        </header>

        <!-- Zone des messages -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-8">
            <div class="mx-auto max-w-3xl space-y-6">
                <!-- Message d'accueil -->
                <div
                    v-if="conversationHistory.length === 0"
                    class="flex items-start gap-4 px-4"
                >
                    <div
                        class="size-8 rounded-full bg-blue-600 flex items-center justify-center"
                    >
                        <span class="text-white text-sm">AI</span>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div
                            class="bg-gray-800/50 rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%]"
                        >
                            <div class="prose prose-invert max-w-none">
                                <p>
                                    Coucou ! Je suis ton IA. Que puis-je faire
                                    pour toi aujourd’hui ?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique des conversations -->
                <template
                    v-for="(conversation, index) in conversationHistory"
                    :key="index"
                >
                    <!-- Message utilisateur -->
                    <div class="flex flex-row-reverse items-start gap-4 px-4">
                        <div
                            class="size-8 rounded-full bg-emerald-600 flex items-center justify-center"
                        >
                            <span class="text-white text-sm">U</span>
                        </div>
                        <div class="flex-1 space-y-2">
                            <div class="flex justify-end">
                                <p
                                    class="bg-emerald-600/20 text-gray-200 rounded-2xl rounded-tr-none px-4 py-2 max-w-[80%]"
                                >
                                    {{ conversation.question }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Réponse IA -->
                    <div class="flex items-start gap-4 px-4">
                        <div
                            class="size-8 rounded-full bg-blue-600 flex items-center justify-center"
                        >
                            <span class="text-white text-sm">AI</span>
                        </div>
                        <div class="flex-1 space-y-2">
                            <div
                                class="bg-gray-800/50 rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%]"
                            >
                                <div class="prose prose-invert max-w-none">
                                    <div
                                        v-html="
                                            renderMarkdown(conversation.answer)
                                        "
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Zone de saisie -->
        <div class="border-t border-gray-700/50 bg-gray-900 px-4 py-4">
            <form
                @submit.prevent="handleSubmit"
                class="mx-auto max-w-3xl relative"
            >
                <textarea
                    v-model="form.message"
                    class="w-full rounded-lg bg-gray-800 border border-gray-700 p-4 pr-20 text-gray-200 placeholder-gray-400 focus:outline-none focus:border-emerald-600 resize-none"
                    :rows="1"
                    placeholder="Posez votre question..."
                    required
                    @keydown.enter.exact.prevent="handleSubmit"
                ></textarea>
                <button
                    type="submit"
                    :disabled="isLoading || !form.message"
                    class="absolute right-4 top-1/2 -translate-y-1/2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-all hover:bg-emerald-700 disabled:opacity-50"
                >
                    <span v-if="isLoading">...</span>
                    <span v-else>Envoyer</span>
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Styles Tailwind custom ou overrides */

.prose-invert {
    --tw-prose-body: theme("colors.gray.300");
    --tw-prose-headings: theme("colors.gray.200");
    --tw-prose-links: theme("colors.blue.400");
    --tw-prose-code: theme("colors.gray.200");
    --tw-prose-pre-code: theme("colors.gray.200");
    --tw-prose-pre-bg: theme("colors.gray.800");
    --tw-prose-quotes: theme("colors.gray.200");
}

/* Code block */
.code-block {
    background: #1e1e1e; /* Couleur de fond plus sombre */
    margin: 1rem 0;
}

.code-header {
    background: #2d2d2d;
}

.hljs {
    background: transparent !important;
    padding: 0 !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
        monospace;
}

/* Pour la scrollbar, optionnel */
::-webkit-scrollbar {
    width: 10px;
}
::-webkit-scrollbar-track {
    background: #1a1a1a;
}
::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 5px;
}
</style>
