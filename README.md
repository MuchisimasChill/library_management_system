# Library management system

## Get Started
1. Build and Run server with docker:
    ```bash 
    docker compose up --build 
    ```
1. Apply db migrations
    ```bash
    docker compose exec app php bin/console doctrine:migrations:migrate
    ```
1. To create test user go to [Create user](http://localhost:8000/dev/create-user)


## Test User credentials
- Email: ```admin@example.com```
- Password: ```Qwer123!```

## Data Base
- Host/IP: 127.0.0.1
- Port: 3306
- User: user
- Password: password
- Database: app_db

## Tests
### Unit

## Good commands
```bash
 docker compose exec app php bin/console cache:clear
```