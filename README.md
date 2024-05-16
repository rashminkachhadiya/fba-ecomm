# Euro Parts

<ul> 
<li>Basic Laravel features: database migration, database seeds, model factories, form validation, authentication and authorization.</li>
<li>Model base resource controller.</li>
</ul>

# How to setup base project ?

``` bash
git clone https://git.topsdemo.in/root/euro-parts.git foldername
```


Install Composer
```bash
composer install
```

Change .env.example into .env file and add database related changes

```bash
php artisan key:generate
php artisan migrate:fresh --seed // add default tables
```