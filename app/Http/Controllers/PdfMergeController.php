<?php

namespace App\Http\Controllers;

use App\Services\ResiParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfMergeController extends Controller
{
    public function __construct(
        protected ResiParserService $parserService
    ) {}

    /**
     * Show PDF Merge Page
     */
    public function index()
    {
        return view('pdf.merge');
    }

    /**
     * Handle PDF Merging
     */
    public function merge(Request $request)
    {
        $request->validate([
            'pdf_files' => 'required|array|min:2',
            'pdf_files.*' => 'required|file|mimes:pdf|max:102400', // 100MB per file
            'file_order' => 'nullable|string',                      // comma separated original indices
            'output_name' => 'nullable|string|max:100',
            'action_type' => 'nullable|string|in:download,rekap',   // download only or merge & rekap
            'standardize_size' => 'nullable|string|in:standard_a6,original',
            'auto_rotate' => 'nullable|boolean',
        ]);

        $files = $request->file('pdf_files');
        $orderRaw = $request->input('file_order');

        // Determine file sequence based on manual order or upload order
        $orderedFiles = [];
        if (!empty($orderRaw)) {
            $indices = array_filter(explode(',', $orderRaw), fn($x) => is_numeric(trim($x)));
            foreach ($indices as $idx) {
                $idxInt = (int) trim($idx);
                if (isset($files[$idxInt])) {
                    $orderedFiles[] = $files[$idxInt];
                }
            }
        }

        // Fallback if order indices did not cover all files
        if (count($orderedFiles) < 2) {
            $orderedFiles = $files;
        }

        // Save files temporarily
        $tempDir = 'temp_merge_' . uniqid();
        $savedFilePaths = [];

        foreach ($orderedFiles as $file) {
            $path = $file->storeAs($tempDir, uniqid() . '_' . $file->getClientOriginalName());
            $savedFilePaths[] = Storage::path($path);
        }

        // Prepare output path
        $outputBaseName = trim($request->input('output_name') ?: '');
        if (empty($outputBaseName)) {
            $outputBaseName = 'Resi_Gabungan_' . date('Ymd_His');
        }
        $outputBaseName = Str::slug($outputBaseName, '_') . '.pdf';

        $outputRelativePath = 'merged_pdfs/' . $outputBaseName;
        $outputFullPath = Storage::path($outputRelativePath);

        // Ensure directory exists
        if (!file_exists(dirname($outputFullPath))) {
            @mkdir(dirname($outputFullPath), 0777, true);
        }

        // Python script
        $pythonScript = base_path('app/Services/Parser/merge_pdfs.py');
        $python = env('PYTHON_PATH', 'python');
        $knownPython = 'C:\\Users\\ACER\\AppData\\Local\\Programs\\Python\\Python315\\python.exe';
        if ($python === 'python' && file_exists($knownPython)) {
            $python = $knownPython;
        }

        $standardizeMode = 'standard_a6';
        $autoRotate = '1';

        $processEnv = array_filter([
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'WINDIR' => getenv('WINDIR') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: (isset($_SERVER['PATH']) ? $_SERVER['PATH'] : null),
            'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
            'PYTHONHASHSEED' => '0',
            'PYTHONIOENCODING' => 'utf-8',
        ]);

        $cmd = array_merge([
            $python,
            $pythonScript,
            '--output',
            $outputFullPath,
            '--standardize',
            $standardizeMode,
            '--auto-rotate',
            $autoRotate,
        ], $savedFilePaths);
        $result = Process::env($processEnv)->run($cmd);

        // Cleanup temporary input files
        Storage::deleteDirectory($tempDir);

        if (!$result->successful()) {
            $err = $result->errorOutput() ?: $result->output();
            return back()->with('error', "Gagal menggabungkan PDF: {$err}");
        }

        $mergeData = json_decode($result->output(), true);

        // If user chose "Gabung & Langsung Rekap"
        if ($request->input('action_type') === 'rekap') {
            try {
                $batch = $this->parserService->processPdf($outputFullPath, $outputBaseName, 'Semua Toko');

                // Store PDF for page inspection & verification in batches_pdf
                $destDir = storage_path('app/batches_pdf');
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                @copy($outputFullPath, $destDir . '/' . $batch->batch_code . '.pdf');

                $successMsg = "Berhasil menggabungkan {$mergeData['total_files']} file PDF.";

                return redirect()->route('resi.batch', $batch->id)
                    ->with('success', $successMsg);
            } catch (\Exception $e) {
                return redirect()->route('pdf.merge.index')
                    ->with('warning', "PDF berhasil digabungkan, namun terjadi kendala saat auto-rekap: " . $e->getMessage());
            }
        }

        // Download directly
        return response()->download($outputFullPath, $outputBaseName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}

