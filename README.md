

<h1>EGOTEK</h1>


                    FONCTIONNALITÉS PRINCIPALES DE L'ERP
                    -------------------------------------
                    1. Gestion de la Production & de la Chaîne Logistique

                    Gestion complète de la chaîne de production : Élaboration du plan directeur de production (PDP) et suivi en temps réel des ordres de fabrication.
                    Gestion des stocks multiples : Suivi multi-entrepôts des matières premières, produits semi-finis et produits finis.
                    Alerte de stock & Plannings d'approvisionnement : Optimisation des achats et alertes automatiques en cas de seuil critique.

                    2. Comptabilité & Facturation Légale (Conformité OBR)

                    Facturation électronique intégrée : Connexion directe et conforme en temps réel avec le serveur de l’OBR (Burundi).
                    Sécurisation des documents : Génération de codes QR et intégration de la signature électronique après chaque transaction.
                    Gestion complète de la comptabilité : Centralisation des données financières, imputations automatiques, passation des écritures comptables et génération instantanée des états financiers (bilan, compte de résultat).
                    Crédits & Recouvrement : Suivi rigoureux des créances clients, des échéanciers et des relances de paiement.

                    3. Gestion Complète des Ressources Humaines (RH) & Paie

                    Gestion des dossiers du personnel : Centralisation des contrats, des compétences et du suivi administratif des employés.

                    Suivi du temps de travail : Gestion des horaires, des plannings, des absences et des congés.

                    Gestion de la paie & des prêts : Calcul automatisé des salaires, intégration des primes/retenues et suivi des prêts accordés aux employés.

                    4. Administration, Performance & Mobilité

                    Sécurité & Droits d'accès : Gestion stricte des rôles et des permissions par utilisateur ou par service.

                    Pilotage de l'activité : Statistiques détaillées du chiffre d’affaires par section et génération automatique de rapports décisionnels.

                    Performance technique : Exécution de tâches en arrière-plan (Background jobs) et système de notifications automatiques.

                    Accessibilité multiplateforme : Accès à distance sécurisé depuis n’importe quel équipement (PC, Smartphone, terminaux POS, Smartwatch).




<h2>How To Install :</h2>
    ## Requirements:<br>
- Laravel `7.x`<br>
- Spatie role permission package<br>
 <h2>step 1:</h2>
    <span>  Go to project folder -
```console</span><br>
    <span>-cd repository</span><br>
    <span>-git clone </span><br>
    <span>Install Laravel Dependencies -
```console <br>
composer install</span><br>
<span>
Create database called - `akiwacu`</span>
 <h2>step 2:</h2>


 <h2>step 3:</h2>
    <span>-go to database file in config folder</span><br>
    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'akiwacu'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],

        'dusk_testing' => [
            'driver' => 'sqlite',
            'database' => database_path('database.sqlite'),
            'prefix' => '',
        ],

    ],

   <h2>step 4 : </h2>
    <h3>run the following commands:</h3>
        *php artisan migrate<br>
        *php artisan db:seed<br>
        *php artisan serve <br>
        *Username - superadmin<br>
        *password - ambazamarcellin@2001