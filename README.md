# esgbu-api

Implémentation de l'API d'accès à l'enquête des bibliothèques universitaires.

## Installation

**Prérequis :** PHP 7.4, Elasticsearch >= 7.9, MariaDB >= 5.5, un serveur SMTP, pdflatex (TexLive >= 2020)

**Module PHP nécessaires :**
- `php-cli`
- `php-json`
- `php-xml`
- `php-opcache`
- `php-mbstring`
- `php-intl`
- `php-process`
- `php-mysqlnd`
- `php-pdo`


yum install php74-php-cli php74-php-json php74-php-xml php74-php-opcache php74-php-mbstring php74-php-intl php74-php-process php74-php-mysqlnd  php74-php-pdo

**Module Elasticsearch :**  
Depuis le dossier d'installation d'Elasticsearch :  
`bin/elasticsearch-plugin install analysis-icu`

*Remarque : En cas de fonction PHP désactivée, il faudra modifier le fichier `/etc/php.d/00-security.ini` et enlever les fonctions non-autorisé dans la liste des `disable_functions`.*

#### 1. Télécharger Symfony :  
` wget https://get.symfony.com/cli/installer -O - | bash `

#### 2. Exporter les binaires Symfony dans l'environnement :  
`export PATH="$HOME/.symfony/bin:$PATH"`

#### 3. Cloner le projet
`git clone https://dci-gitlab.cines.fr/dad/esgbu-api.git`
`cd esgbu-api`

#### 4. Télécharger les "vendor" avec Composer :  
`./composer.phar install`

#### 5. Créer un fichier ".env.local" et y ajouter les informations correspondantes à votre installation :
```
# env.local data for local install

# MySQL Database
DATABASE_URL=mysql://<db_user>:<db_password>@127.0.0.1:3306/<db_name>?serverVersion=5.5

# Application url
API_URL=http://localhost:8000
APP_URL=http://localhost:4200 # Angular project url

# Mail server
MAILER_URL=gmail://<mail_address>:<mail_password>@localhost # Exemple avec Gmail
MAIL_SENDER=<mail_address>

# Mercure
MERCURE_PUBLISH_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOltdfX0.pk5AD-OAcjr8Dh5VjqnuhF-z3g8XpZuClmHZqg15YqY # Example token

# Elasticsearch
ELASTICSEARCH_URL=http://localhost:9200
ELASTICSEARCH_USERNAME_ADMIN=<admin_username>
ELASTICSEARCH_PASSWORD_ADMIN=<admin_password>
ELASTICSEARCH_USERNAME_CLIENT=<client_username>
ELASTICSEARCH_PASSWORD_CLIENT=<client_password>

# Pdflatex binary (version >= TexLive 2020)
PDFLATEX_PATH=<pdflatex_bin_path>

# Optional (for dev only)
# DEV_USER_ID=2 # To bypass Renater/Shibboleth authent: User id in database that you want use for dev.

# Symfony env/debug parameters:
#APP_ENV=prod
#APP_DEBUG=0
```

#### 6. Initialiser la base de données

Importer le fichier `sql/esgbu-dump.sql` dans la base de données MariaDB. Ce fichier est un dump de la base de données de la prod (la date du dump est renseignée à la fin du fichier).  
``` Commande exemple : mysql -u <esgbu> -p<password> <esgbu> < sql/esgbu-dump.sql```

#### 7. Lancer elasticsearch.

Pour activer la sécurité, dans le dossier d'installation d'Elasticsearch, ajouter dans le fichier `config/elasticsearch.yml` cette ligne :  
`xpack.security.enabled: true`  

*Remarque : Pour générer les mots de passe : `bin/elasticsearch-setup-passwords auto`.  
Ajouter ensuite les identifiants dans le fichiers `.env.local`.*

Les index elasticsearch seront créés lors de l'export de la base de données via le script d'export. (voir suite)

#### 8. Lancer Mercure :
Le binaire de Mercure se trouve dans le dossier `Mercure`.   
Commande pour lancer Mercure :
```
cd Mercure
SERVER_NAME=:3000 MERCURE_PUBLISHER_JWT_KEY='a771b2c174f54db4f2daa870f4f99591fd54169db06dcc55d31b51e7344e3e24f4ff325ae9a7d97fd71a3a8a9806077c5ea66' MERCURE_SUBSCRIBER_JWT_KEY='eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOltdfX0.pk5AD-OAcjr8Dh5VjqnuhF-z3g8XpZuClmHZqg15YqY' ./mercure run -config Caddyfile.dev
```
le token utilisé dans la ligne de commande correspond au `MERCURE_JWT_TOKEN` du `.env.local`
de l'exemple plus haut.  

Pour créer un token :  
- se rendre sur le site : https://jwt.io/#debugger-io?token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOltdfX0.pk5AD-OAcjr8Dh5VjqnuhF-z3g8XpZuClmHZqg15YqY
- Dans l'encadré **VERIFY SIGNATURE**, remplacer **your-256-bit-secret** par un mot de passe. (Il correspond au `MERCURE_PUBLISHER_JWT_KEY`)
- Récupérer la clé dans l'encadré **Encoded**, elle correspond au `MERCURE_SUBSCRIBER_JWT_KEY`.

Pour plus d'information sur le fonctionnement de Mercure : https://symfony.com/doc/4.4/mercure.html

#### 9. Lancer le serveur Symfony :  
`bin/console cache:clear && symfony server:start`

#### 10. Afficher l'API dans le navigateur :
Se rendre à l'adresse `http://localhost:8000/` depuis le navigateur internet. Si la documentation de l'API s'affiche, le projet est opérationel.

## Désactiver la sécurité Renater / Shibboleth

1. Dans le fichier `config/packages/security.yaml` passer la variable `security->firewalls->main->security` à
`security: false` permet de désactiver l'authentification.
   
   
2. Dans le fichier .env.local, déclarer la variable `DEV_USER_ID` avec comme valeur l'identifiant de l'utilisateur à utiliser. (un nombre supérieur à 0)
Cela permet de simuler une authentification avec cet utilisateur. De plus, la sécurité CSRF sera désactivée.

## Script d'export de la base de donnée
Les scripts sont situés dans le dossier `src/Script/DatabaseExport`. Pour lancer l'export de la base de données MySQL en CSV à la main et recréer les index elasticsearch :   
`bin/console app:database-export`.  
Les fichiers générés se trouveront dans le workdir de la commande (dossier `public` si lancé via Apache), dans le dossier `database_export`. 

Paramètres optionels :  
- `--last-survey=<institution|documentaryStructure|physicalLibrary>` (ou `-l`) pour exporter seulement en CSV la dernière enquête pour un type d'administration 
- `--force` (ou `-f`) pour forcer l'export et esquiver les problèmes de Mercure.

## Problèmes connus

#### Si l'API n'arrive pas à contacter Mercure pour un dev local (avec le pseudo-serveur de Symfony) : *Résolu dans les récentes version de l'exécutable Symfony (>=4.25.1)*
Remplacer la ligne dans le fichier config/packages/mercure.yaml:  
`url: '%env(MERCURE_PUBLISH_URL)%'`   
par  
`url: 'http://localhost:3000/.well-known/mercure'`
