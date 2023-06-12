# Projet Le blog de Spiderman 

### Cloner le projet 

````
git clone : https://github.com/Maxxs71/leblogdespiderman.git
````
### Deplacer le terminal dans le dossier cloné 
````
cd leblogdespiderman
````

### Installer les vendors (pour recréer le dossier vendor)

````
composer install
````

### Création base de données
Configurer la connexion à la base de données dans le fichier .env (voir cours), puis taper les commandes suivantes
```
symfony console doctrin:database:create
symfony console doctrine:migrations:migrate

````

### Creation  des faux comptes/ articles
```
symfony console doctrine:fixtures:load
```
Cette commande créera : 
* Un compte admin (email: a@a.a , password : 'Azerty12!/')
* 10 comptes utilisateurs (email aleatoire , password : 'Azert12!/')
* 50 artciles

### Lancer le serveur
````
symfony serve
````