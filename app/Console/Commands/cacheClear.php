<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use App\Models\User;

class cacheClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear_custom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache Cleared Successfully';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
         \Artisan::call('cache:clear');
        // $this->info('Success');
    }
}
