# shitFramework

## Synopsis

A shit framework I made using PHP.

## Installation

Clone the repository, create/import the database, create a database user and configure the `.env` file

    git clone https://github.com/linnit/shitFramework .
    mysql frameworkdb < tbm.sql
    cp .env.example ../.env
    vim ../.env

Install Composer (https://getcomposer.org/doc/00-intro.md) and install the defined dependencies

    php composer.phar install


Also insure that `php-json` is installed.

## License

This is free and unencumbered software released into the public domain.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a compiled
binary, for any purpose, commercial or non-commercial, and by any
means.

For more information, please refer to <http://unlicense.org/>
