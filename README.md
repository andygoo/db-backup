# db-backup #
## Simples módulo Kohana para backup de banco de dados ##
  
* Module Versions: 1.0.2 *
* Module URL: http://github.com/sudeste/db-backup
* Compatible Kohana Version(s): 3.2.x

-----------------------------------------------------------
## Descrição ##
Kohana db-backup usa mysqldump ou mysqlselect caso seu host não aceite mysqldump.
As configurações de autenticação do banco de dados vem do módulo database.

-----------------------------------------------------------
## Como usar ##

+ Ative o módulo em bootstrap.php
+ Em seu Controller
> $path = Kohana::$cache_dir;
> DbBackup::factory($path)->mysqlselect();
+ Vai ser retornado o $full_path do arquivo salvo
+ Você pode fazer download do arquivo .sql usando a função kohana send_file()

-----------------------------------------------------------
