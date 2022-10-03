# Customers migration

Laravel command to migrate customers from csv file to customers table in DB


## Getting Started


### Installing

* Clone project from repository
```
git clone git@github.com:Kapral87/laravel_migration.git
```
Or download zip archive

* Copy .env.example to .env and set variables
```
DB_DATABASE={db_name}
DB_USERNAME={user_name}
DB_PASSWORD={user_password}
```

* Execute commands to install and update dependencies
```
composer install
composer update
```

* Migrate database structure
```
php artisan migrate
```


### Executing program

* Go to project directory in console
```
cd {project_directory}
```

{project_directory} - path to this project directory


* Execute command
```
php artisan customers:migrate {csv_file_path} {excel_log_file_path}
```

{csv_file_path} - path to the source csv file with customers data
{excel_log_file_path} - path to the xls file with log (where it will be created)
