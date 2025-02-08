<script setup>
import { ref, nextTick, computed, onMounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import MarkdownIt from "markdown-it";
import hljs from "highlight.js";
import "highlight.js/styles/atom-one-dark.css"; // Thème
import DialogModal from "@/Components/DialogModal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { Link } from "@inertiajs/vue3";
import axios from "axios";

// Props via Inertia
const props = defineProps({
    flash: Object,
    models: Array,
    selectedModel: String,
    conversations: {
        type: Array,
        default: () => [],
    },
    currentConversation: {
        type: Object,
        default: null,
    },
    conversationHistory: {
        type: Array,
        default: () => [],
    },
});

// Historique des conversations
const conversationHistory = ref(props.conversationHistory || []);

// Élément où on scroll
const messagesContainer = ref(null);

// État de chargement
const isLoading = ref(false);

// État pour la souscription au canal
const channelSubscription = ref(null);

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
    if (!form.message.trim()) return;

    if (!currentConversation.value) {
        startNewConversation();
        return;
    }

    const message = form.message;
    isLoading.value = true;
    form.message = "";

    // Ajouter le message de l'utilisateur immédiatement
    conversationHistory.value.push({
        question: message,
        answer: "",
        isLoading: true,
    });

    // Scroll immédiatement après l'ajout du message
    nextTick(() => {
        scrollToBottom();
    });

    // S'abonner au canal avant d'envoyer le message
    const channel = `private-chat.${currentConversation.value.id}`;

    if (window.Echo) {
        if (channelSubscription.value) {
            window.Echo.leave(channel);
        }

        channelSubscription.value = window.Echo.private(channel).listen(
            ".ChatMessageStreamed",
            (e) => {
                const lastMessage =
                    conversationHistory.value[
                        conversationHistory.value.length - 1
                    ];

                if (e.error) {
                    isLoading.value = false;
                    lastMessage.answer = "Erreur: " + e.content;
                    return;
                }

                lastMessage.isLoading = false;
                lastMessage.answer += e.content;
                nextTick(() => scrollToBottom());

                if (e.isComplete) {
                    isLoading.value = false;
                    // Générer le titre si c'est la première réponse de la conversation
                    if (conversationHistory.value.length === 1) {
                        generateTitle();
                    }
                }
            }
        );
    }

    // Envoyer la requête
    axios
        .post(route("ask.stream", currentConversation.value.id), {
            message: message,
            model: form.model,
        })
        .catch((error) => {
            isLoading.value = false;
            console.error(error);
            conversationHistory.value.pop();
        });
}

// Scroll auto
function scrollToBottom() {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop =
            messagesContainer.value.scrollHeight;
    }
}

const filteredModels = computed(() => {
    return props.models.filter(
        (model) =>
            model.name !== "meta-llama/llama-3.2-90b-vision-instruct:free"
    );
});

onMounted(() => {
    if (!form.model) {
        form.model = "meta-llama/llama-3.2-90b-vision-instruct:free";
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

// État pour les conversations
const conversations = ref([]);
const currentConversation = ref(null);

// Fonction pour démarrer une nouvelle conversation
function startNewConversation() {
    form.post(route("conversations.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.currentConversation) {
                currentConversation.value = page.props.currentConversation;
                conversations.value = page.props.conversations;
                // Réinitialiser l'historique des messages
                conversationHistory.value = [];
            }
        },
    });
}

// Fonction pour sélectionner une conversation
function selectConversation(conversation) {
    currentConversation.value = conversation;
    loadMessages(conversation.id);
}

// Fonction pour charger les messages d'une conversation
async function loadMessages(conversationId) {
    try {
        const response = await axios.get(
            route("conversations.messages", conversationId)
        );
        if (response.data) {
            conversationHistory.value = response.data;
            await nextTick();
            await scrollToBottom();
        }
    } catch (error) {
        console.error("Erreur lors du chargement des messages:", error);
    }
}

// Génération de titre
async function generateTitle() {
    try {
        if (!currentConversation.value?.id) return;

        const response = await axios.post(
            route("conversations.generate-title", currentConversation.value.id)
        );
        if (response.data.title) {
            currentConversation.value.title = response.data.title;
            // Mettre à jour la conversation dans la liste
            const index = conversations.value.findIndex(
                (c) => c.id === currentConversation.value.id
            );
            if (index !== -1) {
                conversations.value[index].title = response.data.title;
            }
        }
    } catch (error) {
        console.error("Erreur lors de la génération du titre:", error);
    }
}

// Initialiser les refs avec les props
conversations.value = props.conversations;
currentConversation.value = props.currentConversation;
conversationHistory.value = props.conversationHistory;

// Fonction pour formater la date
const formatDate = (date) => {
    if (!date) return "";
    return new Date(date).toLocaleDateString("fr-FR", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Ajouter dans la section setup
const confirmingDeletion = ref(false);
const conversationToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (conversation) => {
    conversationToDelete.value = conversation;
    confirmingDeletion.value = true;
};

const closeDeleteModal = () => {
    confirmingDeletion.value = false;
    conversationToDelete.value = null;
};

const deleteConversation = () => {
    if (!conversationToDelete.value) return;

    isDeleting.value = true;

    router.delete(
        route("conversations.destroy", conversationToDelete.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
                if (
                    currentConversation.value?.id ===
                    conversationToDelete.value.id
                ) {
                    currentConversation.value = null;
                    conversationHistory.value = [];
                }
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        }
    );
};

const sidebarVisible = ref(true);

// Ajout des refs pour la recherche
const searchQuery = ref("");
const isSearching = ref(false);

// Modifiez la fonction handleSearch et ajoutez filteredConversations
const filteredConversations = computed(() => {
    if (!searchQuery.value) return conversations.value;

    const query = searchQuery.value.toLowerCase();
    return conversations.value.filter((conv) => {
        const title = (conv.title || "Nouvelle conversation").toLowerCase();
        const messages = conv.messages
            ? conv.messages
                  .map((m) => (m.question + " " + m.answer).toLowerCase())
                  .join(" ")
            : "";

        return title.includes(query) || messages.includes(query);
    });
});

// Remplacez la fonction handleSearch existante
const handleSearch = () => {
    isSearching.value = true;
    // La recherche est maintenant gérée par le computed filteredConversations
};
</script>

<template>
    <div class="flex h-screen">
        <!-- Sidebar avec transition -->
        <transition
            enter-active-class="transition-all duration-300 ease-in-out"
            leave-active-class="transition-all duration-300 ease-in-out"
            enter-from-class="-ml-64"
            enter-to-class="ml-0"
            leave-from-class="ml-0"
            leave-to-class="-ml-64"
        >
            <div
                v-show="sidebarVisible"
                class="flex flex-col h-screen bg-gray-800 border-r border-gray-700 w-64"
            >
                <!-- Div des boutons en haut de la sidebar -->
                <div
                    class="p-4 border-b border-gray-700 flex space-x-2 items-center"
                >
                    <button
                        @click="startNewConversation"
                        class="flex-1 px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg hover:bg-emerald-700"
                    >
                        Nouvelle conversation
                    </button>

                    <!-- Bouton de recherche -->
                    <button
                        @click="isSearching = !isSearching"
                        class="px-3 py-2 text-gray-300 hover:text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors"
                        title="Rechercher dans les conversations"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Barre de recherche (apparaît quand isSearching est true) -->
                <div
                    v-if="isSearching"
                    class="px-4 py-2 border-b border-gray-700"
                >
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="w-full px-3 py-1.5 text-sm bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 placeholder-gray-400"
                        placeholder="Rechercher..."
                        @keyup.enter="handleSearch"
                    />
                </div>

                <!-- Liste des conversations -->
                <div class="overflow-y-auto">
                    <div
                        v-for="conv in filteredConversations"
                        :key="conv.id"
                        class="p-4 cursor-pointer hover:bg-gray-700 relative group"
                        :class="[
                            currentConversation?.id === conv.id
                                ? 'bg-gray-700'
                                : '',
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div
                                class="flex-1 min-w-0 mr-2"
                                @click="selectConversation(conv)"
                            >
                                <div class="flex items-center">
                                    <p
                                        class="text-sm text-gray-300 truncate hover:text-gray-100"
                                        :title="
                                            conv.title ||
                                            'Nouvelle conversation'
                                        "
                                        v-html="
                                            renderMarkdown(
                                                conv.title ||
                                                    'Nouvelle conversation'
                                            )
                                        "
                                    ></p>
                                    <button
                                        @click.stop="confirmDelete(conv)"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Supprimer la conversation"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ formatDate(conv.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Bouton toggle -->
        <button
            @click="sidebarVisible = !sidebarVisible"
            :class="[
                'absolute top-1/2 transform -translate-y-1/2 z-50 p-2 bg-gray-800 text-gray-300 hover:text-white rounded-lg border border-gray-700 transition-all duration-300',
                sidebarVisible ? 'left-64' : 'left-0',
            ]"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    v-if="sidebarVisible"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                />
                <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 5l7 7-7 7M5 5l7 7-7 7"
                />
            </svg>
        </button>

        <!-- Zone principale -->
        <div class="flex-1 flex flex-col transition-all duration-300">
            <!-- En-tête -->
            <header
                class="sticky top-0 z-10 border-b border-gray-700/50 bg-gray-900 backdrop-blur"
            >
                <div
                    class="mx-auto max-w-5xl px-4 py-3 flex justify-between items-center"
                >
                    <select
                        v-model="form.model"
                        class="max-w-xs rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-200 border border-gray-700"
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

                    <!-- Bouton Home -->
                    <Link
                        href="/dashboard"
                        class="text-gray-400 hover:text-gray-200 transition-colors"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                        </svg>
                    </Link>
                </div>
            </header>

            <!-- Zone des messages -->
            <div
                ref="messagesContainer"
                class="flex-1 overflow-y-auto px-4 py-8 bg-gray-800"
            >
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
                                class="bg-gray-900 rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%]"
                            >
                                <div class="prose prose-invert max-w-none">
                                    <p>
                                        Coucou ! Je suis ton IA. Que puis-je
                                        faire pour toi aujourd'hui ?
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
                        <div
                            v-if="conversation.question"
                            class="flex flex-row-reverse items-start gap-4 px-4"
                        >
                            <div
                                class="size-8 rounded-full bg-emerald-600 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">U</span>
                            </div>
                            <div class="flex-1 space-y-2">
                                <div class="flex justify-end">
                                    <p
                                        class="bg-emerald-600/20 text-gray-200 rounded-2xl rounded-tr-none px-4 py-2 max-w-[80%] break-words whitespace-pre-wrap"
                                    >
                                        {{ conversation.question }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Réponse IA -->
                        <div
                            v-if="conversation.answer"
                            class="flex items-start gap-4 px-4"
                        >
                            <div
                                class="size-8 rounded-full bg-blue-600 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">AI</span>
                            </div>
                            <div class="flex-1 space-y-2">
                                <div
                                    class="bg-gray-900 rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%]"
                                >
                                    <div
                                        class="prose prose-invert max-w-none break-words whitespace-pre-wrap"
                                    >
                                        <div
                                            v-html="
                                                renderMarkdown(
                                                    conversation.answer
                                                )
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
                        :key="`textarea-${currentConversation?.id || 'new'}`"
                        class="w-full rounded-lg bg-gray-800 border border-gray-700 p-4 pr-24 text-gray-200 placeholder-gray-400 focus:outline-none focus:border-emerald-600 resize-none"
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
                        <template v-if="isLoading">
                            <svg
                                class="animate-spin h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                        </template>
                        <span v-else>Envoyer</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <DialogModal :show="confirmingDeletion" @close="closeDeleteModal">
        <template #title> Supprimer la conversation </template>

        <template #content>
            Êtes-vous sûr de vouloir supprimer cette conversation ? Cette action
            est irréversible.
        </template>

        <template #footer>
            <SecondaryButton @click="closeDeleteModal">
                Annuler
            </SecondaryButton>

            <DangerButton
                class="ml-3"
                :class="{ 'opacity-25': isDeleting }"
                :disabled="isDeleting"
                @click="deleteConversation"
            >
                Supprimer
            </DangerButton>
        </template>
    </DialogModal>
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
