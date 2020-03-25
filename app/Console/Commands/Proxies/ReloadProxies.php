<?php

namespace App\Console\Commands\Proxies;

use App\Services\Proxies\Proxy;
use Illuminate\Console\Command;

/**
 * Class ReloadProxies
 * @package App\Console\Commands\Proxies
 */
class ReloadProxies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proxy:reload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload proxies list';

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
     * @return void
     */
    public function handle()
    {
        (new Proxy())->refreshList();
    }
}
