# Episciences To Zenodo Proxy


![GPL](https://img.shields.io/github/license/CCSDForge/episciences)
![Language](https://img.shields.io/github/languages/top/CCSDForge/episciences)

### About
Software for submitting a document from an [Episciences](https://www.episciences.org/) journal to the [Zenodo](https://zenodo.org/) repository

The software is developed by the [Center for the Direct Scientific Communication (CCSD)](https://www.ccsd.cnrs.fr/en/). See [AUTHORS](./AUTHORS).

### Install project
Environment
```
change the .env to the right environment
```


#### Dependencies
If you want update libraries you can update with
```
composer install/update
```

#### First install 
```
composer install
```
Change in .env the database url
```
Example : DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"
```

#### Migration
```
php bin/console doctrine:migrations:migrate
```

To upgrade to the latest migration
```
php bin/console doctrine:migrations:migrate latest
```

#### Javascript
```
Launch Yarn install or update
```

### Acknowledgments
Episciences has received funding from:
- [CNRS](https://www.cnrs.fr/)
- [European Commission grant 101017452](https://cordis.europa.eu/project/id/101017452) “OpenAIRE Nexus - OpenAIRE-Nexus Scholarly Communication Services for EOSC users”

### Changelog
All notable changes to this project will be documented in the [CHANGELOG.md](./CHANGELOG.md)

### License
Episciences is free software licensed under the terms of the GPL Version 3. See [LICENSE](./LICENSE).

