# InPost API - create_shipment.php

## Opis
Skrypt PHP do utworzenia przesyłki kurierskiej "courier standard" oraz zamówienia kuriera przy użyciu API InPost.

## Wymagania
- PHP 7.4+ lub 8.x
- Composer
- Token API InPost

## Instalacja

1. Sklonuj repozytorium:
    ```bash
    git clone <repo-url> inpost_test
    cd inpost_test
    ```

2. Zainstaluj zależności:
    ```bash
    composer install
    ```

3. Skopiuj plik `.env.example` i podaj swój token:
    ```bash
    cp .env.example .env
    ```

4. W pliku `.env` uzupełnij:
    ```
    API_TOKEN=your_real_api_token_here
    ```

## Uruchomienie

```bash
php create_shipment.php
