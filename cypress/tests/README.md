# Cypress Integration Tests for IIIFPlugin

This guide explains how to set up and run Cypress integration tests for the IIIFPlugin, following the [PKP Plugin Development Guide](https://docs.pkp.sfu.ca/dev/plugin-guide/en/release#write-tests-for-your-plugin) and [PKP Testing Documentation](https://docs.pkp.sfu.ca/dev/testing/en/getting-started#configure-your-environment).


## Prerequisites

1. **Get OJS or OMP**  
   - Clone and configure the repository for [OJS](https://github.com/pkp/ojs) or [OMP](https://github.com/pkp/omp) (See [Getting Started](https://docs.pkp.sfu.ca/dev/documentation/en/getting-started)).   
   - Select either the **3.4** or **3.5** stable branch.  
     

2. **Set up the Plugin**  
   - Clone this repository into the `plugins/generic/` folder of your OJS/OMP installation.  
   - Switch to the respective **testing branch**.

## Test Setup

### Configuration for Testing

Update the following settings in `config.inc.php` (copy of `config.TEMPLATE.inc.php`):
```
default = smtp
smtp = On
smtp_server = localhost
smtp_port = 1025
```

Create a file named `cypress.env.json` in the root of your project:
```
{
  "baseUrl": "http://localhost:8000",
  "DBTYPE": "mysqli",
  "DBHOST": "localhost",
  "DBUSERNAME": "pkp",
  "DBPASSWORD": "password",
  "DBNAME": "pkp_test_db",
  "FILESDIR": "files"
}
```

### Before running or re-running all tests 
- Ensure the `FILESDIR` exists and is empty:
- installed = Off in `config.inc.php`
- Create or Recreate the Test Database

```
mkdir files
rm -rf files/*
sed -i 's/installed = On/installed = Off/' config.inc.php

sudo mysql -u root -p -e "
DROP DATABASE IF EXISTS pkp_test_db;
CREATE DATABASE pkp_test_db;
CREATE USER IF NOT EXISTS 'pkp'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON pkp_test_db.* TO 'pkp'@'localhost';
FLUSH PRIVILEGES;
"
```
---

##  Run Tests  

### Start the Server
```
php -S localhost:8000
```

#### (Only for 3.5 versions)
install and run sendria:

```
python3 -m pip install sendria
sendria --db mails.sqlite
```

---

###  Run Tests 

#### Initial tests:
```
npx cypress run
```

This is needed to setup the test database and create a user admin (with password admin).
If you jsut need the installation use: npx cypress run --config "specPattern=cypress/tests/data/10-ApplicationSetup/*.cy.{js,ts}" (faster)

---

####  Run Plugin Tests
```
npx cypress run --config "specPattern=**plugins/generic/**/cypress/tests/**/*.cy.{js,ts}"
```

Note use `open` instead of `run` to use the interactive GUI.

---


## References
- [PKP Plugin Development Guide – Write Tests](https://docs.pkp.sfu.ca/dev/plugin-guide/en/release#write-tests-for-your-plugin)  
- [PKP Testing Documentation – Configure Your Environment](https://docs.pkp.sfu.ca/dev/testing/en/getting-started#configure-your-environment)
