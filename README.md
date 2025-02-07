Installation & Démarrage :
Lancer docker compose build --no-cache pour construire le docker
Lancer docker compose up --pull always -d --wait pour lancer le docker
Lancer docker-compose exec php php bin/console commande_personnalise_setup_projet pour setup et configurer le projet
Lancer http://localhost:8000/
ou bien :

Lancer docker compose build --no-cache pour construire le docker
Lancer docker compose up --pull always -d --wait pour lancer le docker
Lancer composer install pour installer composer
Lancer docker-compose exec php php bin/console doctrine:migrations:migrate pour créer les tables dans la bdd
Lancer docker-compose exec php php bin/console doctrine:fixtures:load pour lancer les fixtures
Lancer http://localhost:8000/
Comptes de test (Role / Email / Mot de passe) :
USER : utilisateur0@exemple.com / motdepasse
ADMIN : admin@exemple.com / admin
BANNED : banni@exemple.com / banni
Test fonctionnels et unitaires :
Lancer docker-compose exec php php vendor/bin/phpunit tests/UserTest.php pour lancer les tests unitaires
Lancer docker-compose exec php php vendor/bin/phpunit tests/Controller/AdminControllerTest.php pour lancer les tests fonctionnels
Documentation :
Un cahier des charges (à adapter car les entités ont été modifiées) ✅
Un schéma de la BDD ✅
Des fixtures ✅
Un guide (readme) pour installer le projet en local, le démarrer, les comptes de tests, process de validation ✅
Entités :
Au minimum 10 entités avec de l'héritage d'entité ✅
Au minimum 2 relations ManyToMany ✅
Au minimum 8 relations OneToMany ✅
Sécurité :
Une authentification sécurisée ✅
Au moins 1 voter personnalisé ✅
Avoir 3 rôles différents pour les permissions ✅
API :
Avoir au moins un controller dédie pour une API (JSON, normalizer, denormalizer) ✅
Avoir un envoi de mail ✅
Accéder à une API externe (IA ? SMS ? Autre ?) (Utilisation de Stripe) 
Autre :
Avoir un minimum 1 test unitaire et 1 test fonctionnel✅
Avoir des requêtes personnalisées avec des query builder dans des repositories ✅
Utiliser des forms dynamiques ✅
Avoir un espace admin ✅
Minimum 10 pages différentes ✅
(Pas obligatoire)

CI/CD :
Projet déployé
Une CI qui fait tourner les tests, l'analyse statique PHPStan, un linter ... ?
Points bonus :
Temps réel ✅
Asynchrone
Commandes personnalisées 
Tests mutations
DDD
TDD
