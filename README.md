# ci-rest

This is a simplified php rest-server created by extending Codeigniter core classes. 
I have used to for several of my projects and thought it would be of help to someone looking for a quick way to get up and running with a fully RESTful API.

## Setup

### Installing

Clone the project to your projects folder.
```sh
git clone https://github.com/Mwangangi/ci-rest.git
```
cd into the cloned folder

Rename the following files(they are inside application/config folder) as follows:

```sh
database-sample.php file to database.php

config-sample.php file to config.php
```

Edit the files to suit your environment setup and you're set to go!

### Usage
As you like

e.g 
This request will fetch all customer records
```sh
GET http://ci-rest.test/api/customer
```

e.g 
This request will fetch all customer with customer_id 3
```sh
GET http://ci-rest.test/api/customer/3
```

This request will create a new customer record
```sh
POST http://ci-rest.test/api/customer
```

This request will edit customer with customer_id 8 with new details 
```sh
PUT http://ci-rest.test/api/customer/8
```

This request will delete customer with customer_id 6
```sh
DELETE http://ci-rest.test/api/customer/6
```

## Note

* All data interchange format is JSON.
