# laravel-vue-crud-generator

### Installation
On composer.json add:
```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/arturssmirnovs/laravel-vue-crud-generator"
        }
    ],
    "require": {
        "arturssmirnovs/laravel-vue-crud-generator": "@dev"
    }
}
```

run `composer update`

### Generation

New php artisan command should be visible: `vue:generate-crud`
When database migration is created & migrated you can run command: `php artisan vue:generate-crud Users`
