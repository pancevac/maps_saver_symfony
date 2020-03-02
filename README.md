# maps_saver_symfony

REST API service for Maps Saver App <br>
Built with Symfony Framework v4.4

#### API Documentation [https://ms-service.sinisab.tk/api/doc](https://ms-service.sinisab.tk/api/doc)
API doc config for Postman is available on [https://ms-service.sinisab.tk/api/doc.json](https://ms-service.sinisab.tk/api/doc.json)

## Project setup locally
```
$ composer install
```

## Project setup locally with Docker env
```
// build and run php, webserver, db, phpmyadmin containers in background
$ docker-compose up -d

// log into php container to be able to run Symfony CLI
$ docker exec -it symfony_app bash

$ composer install
```

### Generate ssh keys for jwt authentication (required)
```
// create new jwt directory in config
$ mkdir -p config/jwt

// it will require jwt passphrase, 
// just copy key value of JWT_PASSPHRASE from .env or .env.local file 
$ openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096

// will also require same JWT_PASSPHRASE, must match from above!
$ openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```
For more info, visit [LexikJWTAuthenticationBundle Documentation](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/master/Resources/doc/index.md#generate-the-ssh-keys)

### Run database migrations
```
$ php bin/console doctrine:migrations:migrate
```

### Generate fake data (optinal)
```
$ php bin/console doctrine:fixtures:load
```

## Testing
First copy phpunit.xml config
```
$ cp phpunit.xml.dist phpunit.xml 
```
#### Run test
```
$ php bin/phpunit
```
