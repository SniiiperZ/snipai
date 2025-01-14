# Copilot Instructions

Ce fichier détaille les instructions et les meilleures pratiques pour utiliser GitHub Copilot dans ce projet. Assurez-vous de respecter les directives ci-dessous pour garantir un développement cohérent, sécurisé et aligné sur les spécifications du projet.

---

## Contexte du Projet

### Prérequis :

-   **Framework Backend** : Laravel 11.x
-   **Frontend** : Vue.js
-   **Routing** : Géré avec Inertia.js
-   **Base de Données** : Relationnelle (MySQL, PostgreSQL, etc.)
-   **Gestion des Migrations** : Incluse avec Laravel
-   **Auth** : Système d'authentification sécurisé (Laravel Sanctum ou autre)

---

## Bonnes Pratiques

### 1. **Utilisation de GitHub Copilot**

-   Vérifiez toujours le code généré pour vous assurer qu'il respecte les normes de sécurité et les conventions du projet.
-   Ajoutez des commentaires clairs pour guider Copilot dans les cas où vous attendez un comportement spécifique.

### 2. **Backend : Laravel 11.x**

-   Respectez les conventions Laravel pour les contrôleurs, modèles, et services.
-   Privilégiez les **Resource Controllers** pour gérer les routes RESTful.
-   Utilisez les **Policy Classes** pour gérer les autorisations.
