# Episciences To Zenodo

*alpha release*

![GPL](https://img.shields.io/github/license/CCSDForge/episciences)
![Language](https://img.shields.io/github/languages/top/CCSDForge/episciences)


Software for submitting a document from an [Episciences](https://www.episciences.org/) journal to the [Zenodo](https://zenodo.org/) repository


This software has received funding from the [European Commission grant 101017452](https://www.episciences.org/page/Episciences) “OpenAIRE Nexus - OpenAIRE-Nexus Scholarly Communication Services for EOSC users”


The software is developed by the [Center for the Direct Scientific Communication (CCSD)](https://www.ccsd.cnrs.fr/en/).

### License
Episciences is free software licensed under the terms of the GPL Version 3. See LICENSE.


## Install project

Environment
```
change the .env to the right environment
```


Dependencies
```
If you want update libraries you can update with composer install/update
```

Migration
```
Change in .env the database url 

Example : DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"

php bin/console doctrine:migrations:migrate

To upgrade to the latest migration

php bin/console doctrine:migrations:migrate latest
```

Javascript
```
Launch Yarn install or update
```



