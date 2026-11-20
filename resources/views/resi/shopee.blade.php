@extends('layouts.app')

@section('title', 'Rekap Resi Shopee - StockFlow')

@section('content')
<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-900 border border-blue-200 mb-2">
                <span>Kanal Khusus Shopee</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Otomasi Rekap Resi Shopee</h1>
            <p class="text-slate-500 text-sm mt-1">
                Upload PDF resi gabungan Shopee (SPX Express). Sistem akan mengagregasi stok keluar menggunakan 
                <strong class="text-blue-600 font-extrabold underline">Variasi Produk</strong> sebagai acuan utama Anda saat packing.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('resi.tiktok') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-sm">
                Beralih ke Resi TikTok / Tokopedia ➔
            </a>
        </div>
    </div>

    <!-- Acuan Utama Banner (Warna Biru sesuai tombol Pilih File PDF Resi: bg-blue-600) -->
    <div class="bg-blue-600 rounded-2xl p-5 border border-blue-500 shadow-sm flex items-start space-x-4 text-white">
        <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center font-bold text-lg flex-shrink-0 shadow-sm">
            📌
        </div>
        <div class="text-sm space-y-1">
            <div class="font-bold text-white text-base">Ketentuan Acuan Resi Shopee:</div>
            <p class="text-xs text-blue-100 leading-relaxed">
                Di Shopee, kolom <strong class="text-white">SKU</strong> biasanya hanya berupa nama kategori umum (contoh: <code class="bg-white/20 px-1 py-0.5 rounded text-white">Bandana</code>), sedangkan barang fisik spesifik ditentukan oleh kolom <strong class="text-white font-extrabold underline">Variasi</strong> (contoh: <code class="bg-white/20 px-1 py-0.5 rounded text-white">Cat Series</code>, <code class="bg-white/20 px-1 py-0.5 rounded text-white">Soil Black</code>). Oleh karena itu, rekapitulasi stok di halaman ini <strong class="text-white">menjadikan kolom Variasi sebagai patokan utama</strong> Anda.
            </p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Resi Shopee</div>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-3xl font-extrabold text-orange-600">{{ number_format($totalWaybills) }}</div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-50 text-orange-700">Paket</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Dari {{ $totalBatches }} file PDF Shopee</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Barang Keluar</div>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-3xl font-extrabold text-slate-900">{{ number_format($totalItems) }}</div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">Pcs</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Akumulasi pesanan Shopee</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm col-span-2 lg:col-span-1">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Toko Shopee Terhubung</div>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-3xl font-extrabold text-blue-600">{{ $stores->count() }}</div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">Toko SPX</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Aruna & Moveda (SPX Express)</p>
        </div>
    </div>

    <!-- Upload Section -->
    <div id="upload-section" class="bg-white rounded-3xl p-6 md:p-8 border-2 border-dashed border-orange-300 shadow-sm hover:border-orange-500 transition-colors">
        <form action="{{ route('resi.upload') }}" method="POST" enctype="multipart/form-data" id="upload-form" class="space-y-4">
            @csrf
            <input type="hidden" name="target_platform" value="Shopee">

            <div class="text-center max-w-xl mx-auto">
                <h3 class="text-lg font-bold text-slate-900">Upload File PDF Resi Shopee</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Pilih file PDF resi Shopee. Sistem akan membaca dan mengelompokkan barang berdasarkan <strong>Variasi Produk</strong>.
                </p>

                <div class="mt-6 flex flex-col items-center justify-center">
                    <label for="pdf_file_shopee" class="cursor-pointer inline-flex items-center space-x-2 px-6 py-3 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm shadow-md shadow-orange-500/20 transition transform active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <span>Pilih File PDF Shopee</span>
                    </label>
                    <p class="text-xs text-slate-400 mt-2 font-medium">atau tarik & lepas (drag and drop) file PDF langsung ke kotak ini</p>
                    <input type="file" id="pdf_file_shopee" name="pdf_file" accept="application/pdf" class="hidden" required onchange="handleShopeeFileSelect(this)">
                    
                    <div id="selected-file-info" class="mt-4 hidden p-3 rounded-xl bg-orange-50/50 border border-orange-200 text-xs text-slate-700 flex items-center space-x-2">
                        <span class="font-bold text-orange-700" id="file-name-text">filename.pdf</span>
                        <span class="text-slate-400" id="file-size-text">(0 MB)</span>
                        <button type="submit" id="btn-submit-upload" class="ml-3 px-4 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow transition">
                            Proses Rekap Shopee 🚀
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div id="upload-loading" class="mt-6 hidden flex-col items-center">
                        <div class="w-10 h-10 border-4 border-orange-200 border-t-orange-600 rounded-full animate-spin"></div>
                        <p class="text-xs font-semibold text-slate-700 mt-3 animate-pulse">Sedang membaca dan merekap resi Shopee... Harap tunggu sebentar...</p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Riwayat Batch Khusus Shopee (Referensi Desain Gambar 4) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Riwayat Rekapitulasi Resi Shopee</h3>
                <p class="text-xs text-slate-400 mt-0.5">Daftar file PDF resi Shopee yang telah diproses</p>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-slate-50 text-slate-900 border border-slate-200">
                    Kanal Shopee
                </span>
            </div>
        </div>

        <div class="p-5 sm:p-6 space-y-4">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-700 border-collapse">
                    <thead class="bg-[#495098] text-white text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Kode Batch</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Nama File</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Halaman</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Jumlah Resi</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Total Barang Keluar</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Status Pemetaan</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Waktu Upload</th>
                            <th class="py-3 px-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium bg-white">
                        @forelse($batches as $b)
                            <tr class="hover:bg-slate-50/80 transition border-b border-slate-200 text-slate-900">
                                <td class="py-3 px-3.5 border-r border-slate-200 font-mono text-xs font-bold text-slate-900 text-center">
                                    {{ $b->batch_code }}
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-slate-900">
                                    <div class="font-semibold text-slate-900 truncate max-w-xs">{{ $b->file_name }}</div>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center text-xs text-slate-900 font-medium">
                                    {{ $b->total_pages }} hal
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center font-bold text-slate-900 text-xs">
                                    {{ $b->total_waybills }} resi
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                        {{ $b->total_qty }} pcs
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                    @if($b->status === 'completed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                            ✓ Siap Input
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                            ⚠️ Ada Produk Baru
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-xs text-slate-900 font-medium text-center">
                                    {{ $b->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap">
                                        <a href="{{ route('resi.batch', $b->id) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition">
                                            Lihat Rekap ➔
                                        </a>
                                        <form action="{{ route('resi.delete', $b->id) }}" method="POST" class="inline-flex items-center m-0" onsubmit="return confirm('Hapus riwayat batch ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-red-600 rounded transition flex items-center justify-center" title="Hapus Batch">
                                                ✕
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-900 font-medium">
                                    Belum ada file resi Shopee yang diupload. Silakan upload file PDF di atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="pt-4 border-t border-slate-200">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function handleShopeeFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('file-name-text').textContent = file.name;
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            document.getElementById('file-size-text').textContent = `(${sizeMB} MB)`;
            document.getElementById('selected-file-info').classList.remove('hidden');
        }
    }

    document.getElementById('upload-form').addEventListener('submit', function () {
        document.getElementById('selected-file-info').classList.add('hidden');
        document.getElementById('upload-loading').classList.remove('hidden');
        document.getElementById('upload-loading').classList.add('flex');
    });

    // Drag and Drop support
    const dropZone = document.getElementById('upload-section');
    const fileInput = document.getElementById('pdf_file_shopee');

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('border-orange-600', 'bg-orange-50/70', 'scale-[1.005]');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('border-orange-600', 'bg-orange-50/70', 'scale-[1.005]');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            if (files[0].type === 'application/pdf' || files[0].name.toLowerCase().endsWith('.pdf')) {
                fileInput.files = files;
                handleShopeeFileSelect(fileInput);
            } else {
                alert('File yang diupload harus berformat PDF!');
            }
        }
    }, false);
</script>
@endsection
