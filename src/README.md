Utilisation du script de migration.

# Installation du module Console et HttpClient de symfony
composer require symfony/console
composer require symfony/http-client

# Chargement du service dans l'autoload
#Certainement inutile

composer dump-autoload -o

# Lancement du script
php bin\console app:migration