# AGENTS.md

Consignes pour les agents qui travaillent sur ce dépôt. Le contexte produit, l'installation et les choix techniques sont dans le `README.md` : lire celui-ci d'abord.

## Projet

Exercice technique de recrutement (alternance Full Stack). L'application synchronise les véhicules de l'API partenaires Glinche V1 en base et les affiche avec un filtre par marque et une pagination. La qualité du code est le critère principal d'évaluation.

- `backend/` : Laravel 13, API uniquement (pas de vues, pas de routes web), Pest
- `frontend/` : Vue 3.5 SPA + TypeScript (Composition API, `<script setup lang="ts">`), Vite, Tailwind CSS 4, Vitest
- `docker/` et `compose.yaml` : services `app`, `nginx`, `mysql`, `node`, `scheduler`

## Commandes

```bash
docker compose up -d --build                      # tout démarrer (migrations + première synchro incluses)
docker compose exec app php artisan test          # tests back-end
docker compose exec app ./vendor/bin/pint         # formatage PHP, avant chaque commit
docker compose exec node npm test                 # tests front-end
docker compose exec node npm run typecheck        # vérification TypeScript
docker compose exec app php artisan vehicles:sync # synchronisation manuelle
```

Sans Docker : mêmes commandes depuis `backend/` ou `frontend/`. Avant de committer, les tests back et front et la vérification TypeScript doivent passer, et Pint ne doit rien signaler.

## Conventions

- **Langue** : code, noms, commits et messages de log en anglais. README, AGENTS.md et textes de l'interface en français.
- **Commentaires au strict minimum** : seulement pour un « pourquoi » non évident (ex. l'ordre des clés JSON dans `VehicleSynchronizer`).
- **Pas de sur-ingénierie** : pas de repository pattern, pas de Pinia, pas de Vue Router, pas de dépendance sans besoin réel.
- **Style** : suivre le code existant. PHP formaté par Pint. Commandes Artisan avec les attributs `#[Signature]` et `#[Description]` (stub Laravel 13). Côté front : TypeScript strict, sans point-virgule, apostrophes simples.
- **Git flow** : `main`, `develop`, une branche `feature/*` par sujet, fusionnée dans `develop` avec `--no-ff`. Commits conventionnels (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`). Ne rien pousser ni fusionner dans `main` sans accord explicite.

## Secrets

- Les identifiants Glinche vont **uniquement** dans `backend/.env` (ignoré par Git). `backend/.env.example` garde des valeurs vides.
- Ne jamais committer le sujet PDF : il contient le mot de passe de l'API.
- Avant chaque commit, vérifier qu'aucun fichier inattendu n'est indexé (ex. une base SQLite créée par une commande de test).

## API Glinche V1 : faits vérifiés

- La spécification OpenAPI est à `https://marketplace-dev.glinche-automobiles.com/docs/openapi.json` (la page de doc est rendue en JavaScript).
- `POST /api/partners/login` renvoie un token Sanctum (`id|secret`), sans durée de validité connue.
- `GET /api/partners/vehicles` renvoie **tout le stock dans un tableau, sans pagination**. N'en ajoutez pas côté client Glinche.
- `reference` identifie une annonce (`external_id`). `prices.price` (public TTC) est optionnel et absent des données actuelles, `merchantPrice` sert de repli. Les codes `energy` et `gearbox` sont des enums documentées.
- Les données de test contiennent des incohérences connues (listées dans le README). On les affiche telles quelles, sans les « corriger ».

## Règles métier à préserver

- **Synchronisation miroir** : un véhicule absent du flux ou portant une `deleteDate` est supprimé. Une erreur d'API ne modifie rien (appel avant la transaction).
- **Une annonce invalide ne bloque jamais les autres** : le mapper la valide (`ValidationException`), elle est journalisée, ignorée, et sa dernière copie valide est conservée. Toute contrainte de colonne (longueur, plage numérique) doit donc être validée dans `VehicleMapper` : une erreur SQL dans la transaction ferait échouer toute la synchronisation.
- **Photos** : une photo invalide ou non HTTP(S) est ignorée, elle ne rejette pas le véhicule.
- **Marques identifiées par slug** (« Citroën » = « Citroen »). Un fabricant sans slug possible est rejeté.
- **Idempotence** : une synchro sans changement compte les véhicules en `unchanged`. MySQL réordonne les clés JSON, d'où la comparaison non stricte de `raw`.
- **Un seul `vehicles:sync` à la fois** (verrou de cache dans la commande).
- **Prix en centimes TTC**, entier, conversion sans flottant. Un prix nul ou absent donne « Prix sur demande ».
- **Pagination serveur à 6 véhicules par page** (`VehicleController::PER_PAGE`), tri stable marque → modèle → id.

## Pièges de test connus

- Les tests back tournent sur SQLite en mémoire, la prod sur MySQL. Tout ce qui dépend du moteur (JSON, collation, tailles de colonnes) doit être vérifié aussi sur MySQL via Docker.
- `Http::fake()` **empile** les stubs et le premier qui correspond gagne. Pour plusieurs synchronisations dans un même test, utiliser `fakeGlincheFeed($feed1, $feed2, ...)` (séquence), pas deux appels successifs.
- Le client retente les erreurs 5xx : appeler `Sleep::fake()` dans les tests concernés.
- `glincheAdvert($overrides)` (dans `tests/Pest.php`) construit une annonce réaliste. Partir de lui plutôt que d'écrire une annonce à la main.
- Côté front, les corrections automatiques de l'URL (marque inconnue, page hors limites) passent par `replace()` de `useUrlQuery`, jamais par une affectation directe : sinon le bouton retour du navigateur boucle sur l'URL invalide.

## Vérifier avant d'annoncer « terminé »

1. Tests back et front verts, vérification TypeScript et Pint propres.
2. Stack Docker relancée : `vehicles:sync` deux fois de suite donne `unchanged` partout.
3. Interface vérifiée dans un navigateur, sur desktop et mobile, y compris l'état d'erreur.
4. README à jour si le comportement, les commandes ou le nombre de tests changent.

## Travail en parallèle

Plusieurs sessions peuvent modifier ce dépôt en même temps. Avant d'éditer un fichier, relisez-le. Si `git status` montre des changements que vous n'avez pas faits, ne les écrasez pas : intégrez-les ou signalez-les.
