<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UploadBatch;
use App\Http\Controllers\ResiController;
use App\Http\Controllers\MasterProductController;

class VerifyViewsCommand extends Command
{
    protected $signature = 'verify:views';
    protected $description = 'Verify that all Blade views render successfully';

    public function handle(ResiController $resiCtrl, MasterProductController $prodCtrl)
    {
        $this->info("Verifying Index view...");
        $view1 = $resiCtrl->index();
        $html1 = $view1->render();
        $this->info("Index view rendered successfully! Length: " . strlen($html1));

        $this->info("Verifying Products view...");
        $view2 = $prodCtrl->index();
        $html2 = $view2->render();
        $this->info("Products view rendered successfully! Length: " . strlen($html2));

        $this->info("ALL VERIFICATIONS COMPLETED 100%!");
    }
}
