<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ResiParserService;
use App\Models\UploadBatch;
use App\Models\Waybill;
use App\Models\WaybillItem;

class TestParseCommand extends Command
{
    protected $signature = 'resi:test-parse {file}';
    protected $description = 'Test parsing a resi PDF file';

    public function handle(ResiParserService $service)
    {
        $filePath = $this->argument('file');
        $this->info("Testing parse for: {$filePath}");

        try {
            $batch = $service->processPdf($filePath, basename($filePath));
            $this->info("SUCCESS! Batch ID: {$batch->id}");
            $this->info("Batch Code: {$batch->batch_code}");
            $this->info("Total Pages: {$batch->total_pages}");
            $this->info("Total Waybills: {$batch->total_waybills}");
            $this->info("Total Items Qty: {$batch->total_qty}");
            $this->info("Status: {$batch->status}");

            $items = WaybillItem::whereHas('waybill', function($q) use ($batch) {
                $q->where('upload_batch_id', $batch->id);
            })->with('masterProduct')->get();

            $this->table(
                ['No Resi', 'Platform', 'Raw SKU', 'Raw Variant', 'Qty', 'Master SKU StoDo', 'Mapped?'],
                $items->map(function($it) {
                    return [
                        $it->waybill->waybill_no,
                        $it->waybill->platform,
                        $it->raw_sku,
                        $it->raw_variant,
                        $it->qty,
                        $it->masterProduct?->sku ?? 'NOT MAPPED',
                        $it->is_mapped ? 'YES' : 'NO'
                    ];
                })
            );

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
