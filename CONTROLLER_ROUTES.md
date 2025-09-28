# API Routes for Service Catalog, Perfume, and Discount Controllers

Add these routes to your `routes/api.php` file under the `/v1/outlets/{outlet}` group:

```php
// Service Catalog Routes
Route::prefix('outlets/{outlet}')->group(function () {
    // Services
    Route::get('/services', [ServiceCatalogController::class, 'index']); // GET /services?q=
    Route::post('/services', [ServiceCatalogController::class, 'store']); // POST /services
    Route::put('/services/{service}', [ServiceCatalogController::class, 'update']); // PUT /services/{service}

    // Service Variants
    Route::post('/services/{service}/variants', [ServiceCatalogController::class, 'storeVariant']); // POST /services/{service}/variants
    Route::put('/service-variants/{variant}', [ServiceCatalogController::class, 'updateVariant']); // PUT /service-variants/{variant}

    // Perfumes
    Route::get('/perfumes', [PerfumeController::class, 'index']); // GET /perfumes
    Route::post('/perfumes', [PerfumeController::class, 'store']); // POST /perfumes
    Route::put('/perfumes/{perfume}', [PerfumeController::class, 'update']); // PUT /perfumes/{perfume}

    // Discounts
    Route::get('/discounts', [DiscountController::class, 'index']); // GET /discounts
    Route::post('/discounts', [DiscountController::class, 'store']); // POST /discounts
    Route::put('/discounts/{discount}', [DiscountController::class, 'update']); // PUT /discounts/{discount}
});
```

Don't forget to import the controllers at the top of your routes file:

```php
use App\Http\Controllers\Api\V1\ServiceCatalogController;
use App\Http\Controllers\Api\V1\PerfumeController;
use App\Http\Controllers\Api\V1\DiscountController;
```
