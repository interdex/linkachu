<?php

namespace App\Jobs;

use App\Models\PbnSite;
use App\Services\SiteScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;


class ScanSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $site;

    public $timeout = 3600;

    public function __construct(PbnSite $site)
    {
        $this->site = $site;
    }


    public function handle(SiteScanner $scanner)
    {
        $scanner->scan($this->site);
    }

}
