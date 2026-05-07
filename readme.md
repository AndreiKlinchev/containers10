# Лабораторная работа №10

## Цель работы

Целью работы является знакомство с методами управления секретами в контейнерах.

## Задание

Создать многосервисное приложение с контейнерами, использующими секреты.

## Ход работы

### 1. Настройка проекта

Для работы используется многосервисное приложение из трех контейнеров:

- `frontend` - nginx
- `backend` - php-fpm
- `database` - MariaDB

В файле `docker-compose.yml` были описаны сети `frontend` и `backend`, а также сервисы приложения. После этого нужно было перевести сайт с SQLite на MySQL/MariaDB.

### 2. Изменение класса Database

Сначала изменил класс-обертку над базой данных в файле `site/modules/database.php`.

Конструктор был обновлен до следующего вида:

```php
public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
```

Теперь класс принимает DSN, имя пользователя и пароль, а не путь к локальному файлу базы данных.

Заодно поправил SQL-запросы, чтобы они корректно работали с MySQL.

### 3. Изменение index.php

Дальше обновил файл `site/index.php`.

Старое подключение базы:

```php
$db = new Database($config["db"]["path"]);
```

Заменил на новое:

```php
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['database']};charset=utf8";

$db = new Database($dsn, $config['db']['username'], $config['db']['password']);
```

Теперь приложение подключается уже к MariaDB-контейнеру.

### 4. Изменение config.php

После этого обновил файл `site/config.php`.

Сначала для подключения базы добавил чтение параметров из переменных окружения:

```php
$config['db']['host'] = getenv('MYSQL_HOST');
$config['db']['database'] = getenv('MYSQL_DATABASE');
$config['db']['username'] = getenv('MYSQL_USER');
$config['db']['password'] = getenv('MYSQL_PASSWORD');
```

После этапа с защитой секретов конфигурация была изменена еще раз. В итоге хост и база читаются из переменных окружения, а логин и пароль из файлов секретов:

```php
$config['db']['host'] = getenv('MYSQL_HOST');
$config['db']['database'] = getenv('MYSQL_DATABASE');
$config['db']['username'] = get_file_contents('/run/secrets/user');
$config['db']['password'] = get_file_contents('/run/secrets/secret');
```

Для этого добавил простую функцию `get_file_contents()`, которая читает содержимое файла секрета.

### 5. Изменение Dockerfile

В `Dockerfile` заменил установку `pdo_sqlite` на `pdo_mysql`.

В итоге файл получился таким:

```dockerfile
FROM php:7.4-fpm AS base

RUN apt-get update && \
    apt-get install -y libzip-dev && \
    docker-php-ext-install pdo_mysql

COPY site /var/www/html
```

То есть теперь backend-контейнер собирается уже с поддержкой MySQL.

### 6. Настройка nginx

Конфигурационный файл `nginx.conf` был взят по образцу из лабораторной работы `containers07`.

В нем nginx слушает 80 порт и передает обработку PHP-файлов в сервис `backend`.

### 7. Настройка базы данных

Так как база теперь не SQLite, а MariaDB, пришлось поправить файл `sql/schema.sql`.

Поле идентификатора изменил на:

```sql
id INT AUTO_INCREMENT PRIMARY KEY
```

Остальная логика осталась такой же: создается таблица `page` и добавляются тестовые записи.

### 8. Защита секретов

Для хранения секретов создал папку `secrets` и три файла:

1. `root_secret` - пароль суперпользователя
2. `user` - имя пользователя базы данных
3. `secret` - пароль пользователя базы данных

Содержимое файлов:

```text
root_secret → rootpassword
user → user
secret → userpassword
```

### 9. Обновление docker-compose.yml для secrets

После этого обновил `docker-compose.yml`.

Добавил секцию:

```yaml
secrets:
  root_secret:
    file: ./secrets/root_secret
  user:
    file: ./secrets/user
  secret:
    file: ./secrets/secret
```

Для сервиса `database` изменил переменные окружения:

```yaml
environment:
  MYSQL_ROOT_PASSWORD_FILE: /run/secrets/root_secret
  MYSQL_DATABASE: my_database
  MYSQL_USER_FILE: /run/secrets/user
  MYSQL_PASSWORD_FILE: /run/secrets/secret
```

Для сервиса `backend` оставил:

```yaml
environment:
  MYSQL_HOST: database
  MYSQL_DATABASE: my_database
```

Также подключил секреты к нужным сервисам, чтобы внутри контейнеров они были доступны через `/run/secrets/`.

### 10. Запуск и проверка

Для запуска проекта использовал команды:

```powershell
docker compose up -d --build
docker compose ps
```

После запуска все три контейнера получили статус `Up`.

Дальше проверил приложение в браузере по адресу:

```text
http://localhost/?page=1
```

Страница открылась корректно и вывела данные `Page 1` и `Content 1`. Это значит, что приложение успешно подключается к MariaDB.

### 11. Проверка образа через Docker Scout

Для проверки безопасности образа backend используется команда:

```powershell
docker scout quickview containers10-backend
```

Команда была запущена, но в текущей среде Docker Scout потребовал авторизацию через `docker login`. То есть сама проверка вызывается правильно, но для полноценного результата нужен вход в Docker account.

## Ответы на вопросы

### 1. Почему плохо передавать секреты в образ при сборке?

Это плохо, потому что секреты могут сохраниться внутри слоев образа. Если образ попадет в реестр или будет передан другому человеку, вместе с ним могут утечь логины, пароли и другие конфиденциальные данные.

### 2. Как можно безопасно управлять секретами в контейнерах?

Безопаснее хранить секреты отдельно от образа и передавать их только во время запуска контейнера. Для этого можно использовать Docker Secrets, внешние менеджеры секретов и защищенные переменные окружения.

### 3. Как использовать Docker Secrets для управления конфиденциальной информацией?

Нужно создать файлы с секретами, описать их в секции `secrets` файла `docker-compose.yml`, а потом подключить к нужным сервисам. После этого контейнер получает доступ к секретам в виде файлов внутри `/run/secrets/`, а приложение может считать их уже оттуда.

## Выводы

В ходе лабораторной работы было собрано многосервисное приложение с контейнерами `frontend`, `backend` и `database`, а также выполнен переход с SQLite на MySQL/MariaDB. Кроме этого были изучены основы работы с Docker Secrets. В результате логин и пароль базы данных больше не хранятся прямо в конфигурации приложения, а передаются более безопасным способом через секреты.
