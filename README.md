Waffle Systems DAL
================
[![Build Status](https://scrutinizer-ci.com/g/wafflesystems/dal/badges/build.png?b=master)](https://scrutinizer-ci.com/g/wafflesystems/dal/build-status/master)

A PHP library which provides an abstraction layer for data maniuplation.

Looking for examples, documentation, or crave info? [Go to the wiki.](https://github.com/wafflesystems/dal/wiki)

To start using this library, have composer require `wafflesystems/dal`.

*Note: Despite the repository name, this is a DBAL (Database Abstraction Layer).*

```bash
composer require wafflesystems/dal
```

This library contains a layer of abstraction to start interacting with your database. If you want something more involved or more abstract, I highly recommend you use [wafflesystems/model](https://github.com/wafflesystems/model) along with this library.

# Crash Course

*I will assume you are using MySQL with your existing PHP application.*

The `presidents` table must have the columns, `id`, `first_name`, `last_name`, `born`, and `died`.

Import the `DAL\MySQLDatabase`, and construct a new MySQLDatabase.

```php
use DAL\MySQLDatabase;

$db = new MySQLDatabase();
```

Set the hostname, username, and password.

```php
$db->setHost('localhost');
$db->setUsername('admin');
$db->setPassword('secretPA$$w0rd');
```

## Create
Gather your data into an array, and create a new entry.

```php
$entry = [
    'id' => 1,
    'first_name' => 'George',
    'last_name' => 'Washington',
    'birth_date' => '2/22/1732',
    'death_date' => ''
]
$db->create('presidents', $entry);
```

## Read
Use `find` to find only 1 entry.

We'll be using the condition class to tell the database which entry we'll use.

```php
use DAL\Condition;

$result = $db->find(Condition::equals('birth_date', '2/22/1732'));
echo $result['last_name'];
```

Use `findAll` to find multiple entries.

```php
$results = $db->findAll(Condition::equals('first_name', 'George'));
foreach ($results as $result) {
    echo $result['birth_date'];
    echo "\n";
}
```

## Update
Put the data into a new array to overwrite the database.

```php
$entry = [
    'death_date' => '12/14/1799'
]
$db->update('presidents', $entry, Condition::equals('id', 1));
```

## Delete
This will delete all entries that matches the condition.

```php
$db->delete('presidents', Condition::equals('id', 1));
```

BE CAREFUL! If the condition is null, or not given, then ALL entries will be deleted!

```php
$db->delete('presidents'); // Will DELETE EVERY PRESIDENT!
```
