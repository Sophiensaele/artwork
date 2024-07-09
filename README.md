# Hands on Artwork

Dies ist ein Fork des Projektmanagement-Tools artwork.
Die Sophiensaele beteiligen sich an der Weiterentwicklung dieser Software. Im Sinne der AGPL-3.0 Lizenaz, kommen Änderungen und weitere Entwicklungen dem Projekt zugute.

### Motivation
Die Sophiensaele beteiligen sich auf der User-Seite in der Entwicklung von der Software Artwork.
 Die folgende Dokumentation gibt einen umfangreichen Einblick in die Schritte zur Installation von Artwork. Es ist die Installation unter einem Ubuntu-Server, 
 einer lokalen WSL Installation und zeigt einen Weg Artwork unter Windows zu installieren.
Die Installationsanleitungen befinden sich unter:

______________

## Standalone Dokumentation:

[Installation Artwork Standalone-Betrieb VPS](https://sophiensaele.github.io/artwork/Installation_Artwork%20_Standalone-Betrieb_VPS.html)

________________



## Enwicklungswerkzeuge


[Artwork Tools WSL](https://sophiensaele.github.io/artwork/artwork_tools_WSL.html)

____________

## Artwork unter Windows 


[Artwork Windows](https://sophiensaele.github.io/artwork/artwork_windows.html)




________________

## Was ist Artwork ? 


Artwork ist ein Werkzeug zur Projektorganisation, das die Planung von Projekten mit mehreren Ereignissen, Aufgaben und Verantwortlichkeiten ermöglicht. 

> https://github.com/artwork-software/artwork



Dank an Caldero Systems (https://caldero-systems.de/) die Open-Source-Veröffentlichung von Artwork.




## Was ist [Laravel](https://laravel.com/) ?

Laravel ist ein PHP-Framework, das für die Entwicklung von Webanwendungen konzipiert ist und um die Organisation des Codes zu erleichtern. Dieses Framework bietet eine Struktur, die es einfacher macht, große und komplexe Webanendungen systematisch zu entwickeln.



### [Laravel Sail](https://laravel.com/docs/11.x/sail)

Laravel Sail ist ein Befehlszeilentool zur Verwaltung der Docker-basierten Entwicklungsumgebung für Laravel. Sail erleichtert das Setup und die Verwaltung der Entwicklungsumgebung durch die Bereitstellung von Docker Containern.

## Ok, und Docker?

Docker ist ein Werkzeug, das die Erstellung, den Versand und den Betrieb von Anwendungen erleichtert, indem es sie in sogenannten Containern verpackt. Container können Sie sich als leichtgewichtige, tragbare und eigenständige Pakete vorstellen, die alle notwendigen Komponenten enthalten, um eine Software auszuführen, einschließlich der Bibliotheken, Systemtools, Code und Laufzeitumgebungen.


### Verwendung von Laravel Sail

- **Setup**: Sail ermöglicht das einfache Einrichten einer Entwicklungsumgebung mit vordefinierten Docker Containern.
- **Verwaltung**: Sail bietet Befehle zum Starten, Stoppen und Verwalten von Docker Containern.




Artwork kann entweder als eigenständige Anwendung für dedizierte Server oder als Multi-Container-App, die durch Docker unterstützt wird, installiert werden.
Standalone


_______________________________

<<<<<<< HEAD
=======
# Docker installation Standalone

Artwork offers a stand alone containerized version of the application. This is useful if you want to run the application on a dedicated server or in a cloud environment.

## Prerequisites

[Docker](https://www.docker.com/) and the .env of the repository. It is advised to use the `.env.prod.example` file and rename it to `.env`

## Installation

To boot the container you can simply run the following command:

`docker compose -f docker-compose-production.yml up -d`

The application needs an app key variable set. For this please run the command ``docker compose -f docker-compose-production.yml exec artwork php artisan key:generate --show``
This will output a key. Copy this key and paste it into the .env file under the APP_KEY variable. Restart the container afterwards.

Feel free to modify the .env file to your needs, e.g. with E-Mail credentials.

## Updates

You can modify the `$ARTWORK_VERSION` variable in the .env file. By default it is set to `main` which is the latest stable version of Artwork.
The always pull policy ensures, that it will automatically update to the latest version on the next restart. It will also automatically migrate the database if necessary.

# Docker installation (Laravel Sail)
>>>>>>> upstream/main

## Entwicklungpfade von Artwork ( Branches )

> artwork: a free project management software for the arts 
>  
>offical :
> https://github.com/artwork-software/artwork?tab=readme-ov-file




- **Entwicklungszweig (dev Branch)**: Dient als primärer Entwicklungsast, auf dem Entwickler ihre Bausteine testen. Er wird genutzt, um neue Funktionen und Experimente zu integrieren.

- **Staging-Zweig (staging Branch)**: Funktioniert als Testserverumgebung und kann als Betaversion betrachtet werden. Er wird für Tests vor der Veröffentlichung verwendet.

- **Hauptzweig (main Branch)**: Dieser Zweig ist der stabile Entwicklungspfad und sollte als Grundlage für alle Produktionssysteme genutzt werden. Es enthält die zuverlässigste und am gründlichsten getestete Version des Codes.
`
  
  

<<<<<<< HEAD
=======
2. Now start the Docker container by running:

```shell
./vendor/bin/sail up
```

The images will start building. It is recommended to replace the ./vendor/bin/sail command with a shell alias. 
Consult the [documentation](https://laravel.com/docs/10.x/sail#configuring-a-shell-alias) to achieve that.
We will use the alias `sail` for the following commands.

3. Once the images are created you may have to open a new terminal window and install the frontend dependencies with a secret project key by running:

```shell
sail npm install
```

```shell
sail artisan key:generate
```

4. To migrate the database with dummy data, use the following command:

```shell
sail artisan migrate:fresh --seed
```

To Delete your current database use this command:
```shell
sail artisan migrate:fresh
```

If you want to set up the database fresh for production without dummy data, use this command to fill the database with the necessary tables:

```shell
sail artisan db:seed:production
```
5. Start the queue using:

```shell
sail artisan queue:work
```

6. Start the frontend by running
   
```shell
sail npm run dev 
```
7. Publish the app storage folder to display the artwork logo by running
   
```shell
sail artisan storage:link
```

The site should be running now under http://localhost 🚀

You can also visit your:
- Mails under http://localhost:8025
- Meilisearch under http://localhost:7700/

To connect to your application's MySQL database from your local machine, you may use a graphical database management application such as TablePlus. By default, the MySQL database is accessible at localhost port 3306 and the access credentials correspond to the values of your DB_USERNAME and DB_PASSWORD environment variables. Or, you may connect as the root user, which also utilizes the value of your DB_PASSWORD environment variable as its password.

----------------

If you have problems installing the project or find any other bugs please open a issue [here](https://github.com/artwork-software/artwork/issues).

----------------


To run various commands in the project, you can use the following instructions:

- To run `npm` commands, use the following command:

```shell
sail npm <command>
```

- To see all your changes to the code directly you can also run this command besides the ones from above:

```shell
sail npm run hot
```

For example, to install dependencies, you can run:

```shell
sail npm install
```

- To run `artisan` commands, use the following command:

```shell
sail artisan <command>
```

For example, to generate a new migration file, you can run:

```shell
sail artisan make:migration create_users_table
```

Feel free to use these commands to interact with the project and execute the necessary tasks efficiently.

----------------

## Branch Structure

- **`dev` Branch**: This is where developers test their building blocks. It serves as the primary development branch for integrating new features and experiments.

- **`staging` Branch**: This branch acts as the test server environment and can be considered as the Beta version. It is used for pre-release testing to ensure stability before deployment to production.

- **`main` Branch**: This is our stable branch and should serve as the basis for all production systems. It contains the most reliable and tested version of our code.

----------------

# Test Instance
If you use the docker installation and filled the database with dummy data you can use the following credentials to login to the test instance:

For the admin account (with all permissions):
Mail: anna.musterfrau@artwork.software
Password: TestPass1234!$

For the user account (with limited permissions):
Mail: lisa.musterfrau@artwork.software
Password: TestPass1234!$

a full documentation of all features will be released and found here, when we have finished developement of version 1.0

To be able to invite new Users you need to update the .env file with your mail credentials and the APP_URL

If you have questions, feel free to open an issue :) 

Feel free to explore the features of Artwork and manage your projects effectively!
>>>>>>> upstream/main
