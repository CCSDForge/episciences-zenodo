# Episciences To Zenodo Proxy


![GPL](https://img.shields.io/github/license/CCSDForge/episciences)
![Language](https://img.shields.io/github/languages/top/CCSDForge/episciences)

[![SWH](https://archive.softwareheritage.org/badge/origin/https://github.com/CCSDForge/episciences-zenodo/)](https://archive.softwareheritage.org/browse/origin/?origin_url=https://github.com/CCSDForge/episciences-zenodo)
[![SWH](https://archive.softwareheritage.org/badge/swh:1:dir:ff37f744e51471195b42219bc8f7b4e53521f28b/)](https://archive.softwareheritage.org/swh:1:dir:ff37f744e51471195b42219bc8f7b4e53521f28b;origin=https://github.com/CCSDForge/episciences-zenodo;visit=swh:1:snp:deaac4c608a7bd5bf598159312f660ea65ccfe5c;anchor=swh:1:rev:49d1d944e0dde8e0b2c70c0ffede27a641c49821)



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

