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

------------------ 

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


-----------------------------------------------

## Wymagania funkcjonalne

### Encje:

- [x] Book (tytuł, autor, ISBN, rok wydania, liczba kopii)
- [x] User (imię, nazwisko, email, typ: LIBRARIAN/MEMBER)
- [x] Loan (książka, użytkownik, data wypożyczenia, data zwrotu, status)

### Endpointy:

- [x] POST   /api/auth/login          # Logowanie
- [x] GET    /api/books               # Lista książek (z filtrowaniem)
- [x] POST   /api/books               # Dodanie książki (tylko LIBRARIAN)
- [x] GET    /api/books/{id}          # Szczegóły książki
- [ ] POST   /api/loans               # Wypożyczenie książki
- [ ] PUT    /api/loans/{id}/return   # Zwrot książki
- [ ] GET    /api/users/{id}/loans    # Historia wypożyczeń użytkownika

---------------------------------------------------
## Wymagania techniczne

### Obowiązkowe:
- [x] Symfony 7.x+ z PHP 8.3+
- [x] Doctrine ORM dla bazy danych
- [x] Autoryzacja JWT (lexik/jwt-authentication-bundle)
- [ ] Walidacja z Symfony Validator
- [ ] Serialization groups dla API responses
- [ ] Testy (minimum: unit testy dla serwisów)
- [x] Docker setup (docker-compose.yml)

### Dodatkowe:
- [ ] Rate limiting
- [x] API Documentation (NelmioApiDocBundle)
- [ ] Event system (wysyłanie powiadomień)
- [ ] Caching
- [ ] Pagination
