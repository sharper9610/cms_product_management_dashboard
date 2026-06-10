<?php

namespace App\Providers;

use App\Models\Notice;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
      // Bind data only to header partial
      View::composer('layouts.sections.navbar.navbar', function ($view) {




        $notices = Notice::where('status', 'active')
          ->where(function ($query) {
            $query
              // Case 1: start_date NULL and end_date NULL
              ->where(function ($q) {
                $q->whereNull('start_date')
                  ->whereNull('end_date');
              })

              // Case 2: start_date NOT NULL and end_date NOT NULL (today in range)
              ->orWhere(function ($q) {
                $q->whereNotNull('start_date')
                  ->whereNotNull('end_date')
                  ->where('start_date', '<=', Carbon::now())
                  ->where('end_date', '>=', Carbon::now());
              })

              // ✅ Case 3: start_date NOT NULL and end_date NULL (active from start_date)
              ->orWhere(function ($q) {
                $q->whereNotNull('start_date')
                  ->whereNull('end_date')
                  ->where('start_date', '<=', Carbon::now());
              });
          })
          ->latest('created_at')
          ->take(10)
          ->get();

        $view->with('notices', $notices);
      });
    }
}
