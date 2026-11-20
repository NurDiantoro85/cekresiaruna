@extends('layouts.app')

@section('title', 'Dashboard & Upload Resi')

@section('content')
<div class="space-y-8">
    <!-- Header Banner (Tanpa tombol Master SKU & Gabung PDF, tanpa banner acuan kolom ke-2) -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Otomasi Rekap Resi & Stok Keluar</h1>
            <p class="text-slate-500 text-sm mt-1">
                Upload PDF resi pengiriman untuk toko Anda. Gunakan opsi <strong>Semua Toko</strong> untuk membaca sekaligus resi Shopee dan TikTok/Tokopedia.
            </p>
        </div>
    </div>

    <!-- 1. STATISTIK (Total Resi Diproses, Total Barang Keluar, Master Produk Aktif, Toko Integrasi) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Resi Diproses</div>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-3xl font-extrabold text-slate-900">{{ number_format($totalWaybills) }}</div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">Resi</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Dari {{ $totalBatches }} file PDF diupload</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Barang Keluar</div>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-3xl font-extrabold text-blue-600">{{ number_format($totalItems) }}</div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">Pcs</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Akumulasi seluruh pesanan</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Master Produk Aktif</div>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-3xl font-extrabold text-indigo-600">{{ $totalProducts }}</div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">SKU</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Format produk seragam</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Toko Terintegrasi</div>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-3xl font-extrabold text-emerald-600">{{ $stores->count() }}</div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">4 Toko</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Aruna & Moveda (Shopee & TikTok)</p>
        </div>
    </div>

    <!-- 2. TIGA PILIHAN KANAL RESI (SEMUA TOKO, SHOPEE, TIKTOK/TOKPED) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1: Semua Toko (All in One) -->
        <div class="bg-gradient-to-br from-white to-blue-50/60 rounded-3xl p-6 border-2 border-blue-300 shadow-sm hover:shadow-md hover:border-blue-500 transition flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-900 border border-blue-200">
                        <span>SEMUA TOKO</span>
                    </span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">Auto-Deteksi</span>
                </div>
                <h3 class="text-base font-black text-slate-900">Upload Semua Resi Sekaligus</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Upload file PDF gabungan yang berisi resi Shopee maupun TikTok. Sistem membaca kolom ke-2 dari kanan dari tiap resi secara otomatis.
                </p>
                <div class="p-3 bg-white/90 rounded-xl border border-blue-100 text-xs text-slate-600 space-y-1">
                    <div>📌 <strong>Acuan:</strong> <span class="text-blue-800 font-bold">Kolom ke-2 dari Kanan</span></div>
                    <div class="text-[11px] text-slate-400">Shopee (Variasi) & TikTok (Seller SKU)</div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-blue-100 flex items-center justify-between">
                <button type="button" onclick="selectPlatformUpload('Semua Toko')" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition">
                    Upload Semua Toko Sekarang ↓
                </button>
            </div>
        </div>

        <!-- Card 2: Shopee -->
        <div class="bg-gradient-to-br from-white to-blue-50/60 rounded-3xl p-6 border-2 border-blue-300 shadow-sm hover:shadow-md hover:border-blue-500 transition flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-900 border border-blue-200">
                        <span>KANAL SHOPEE</span>
                    </span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700">SPX Express</span>
                </div>
                <h3 class="text-base font-black text-slate-900">Upload Khusus Resi Shopee</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Khusus file PDF resi Shopee. Sistem akan mengelompokkan stok keluar dengan patokan utama pada kolom <strong class="text-blue-800 font-bold">Variasi</strong>.
                </p>
                <div class="p-3 bg-white/90 rounded-xl border border-blue-100 text-xs text-slate-600 space-y-1">
                    <div>📌 <strong>Acuan Utama:</strong> <span class="text-blue-800 font-bold">Variasi Produk</span></div>
                    <div class="text-[11px] text-slate-400">Aruna & Moveda (Shopee SPX)</div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-blue-100 flex items-center justify-between">
                <a href="{{ route('resi.shopee') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition">
                    Buka Menu Shopee ➔
                </a>
            </div>
        </div>

        <!-- Card 3: TikTok / Tokopedia -->
        <div class="bg-gradient-to-br from-white to-blue-50/60 rounded-3xl p-6 border-2 border-blue-300 shadow-sm hover:shadow-md hover:border-blue-500 transition flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-900 border border-blue-200">
                        <span>KANAL TIKTOK / TOKPED</span>
                    </span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700">J&T Express</span>
                </div>
                <h3 class="text-base font-black text-slate-900">Upload Khusus Resi J&T</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Khusus file PDF resi J&T. Sistem akan mengelompokkan stok keluar dengan patokan utama pada kolom <strong class="text-blue-800 font-bold">Seller SKU</strong>.
                </p>
                <div class="p-3 bg-white/90 rounded-xl border border-blue-100 text-xs text-slate-600 space-y-1">
                    <div>📌 <strong>Acuan Utama:</strong> <span class="text-blue-800 font-bold">Seller SKU Toko</span></div>
                    <div class="text-[11px] text-slate-400">Aruna & Moveda (TikTok/Tokped J&T)</div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-blue-100 flex items-center justify-between">
                <a href="{{ route('resi.tiktok') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition">
                    Buka Menu TikTok ➔
                </a>
            </div>
        </div>
    </div>

    <!-- 3. BAGIAN UPLOAD SEMUA TOKO -->
    <div id="upload-section" class="bg-white rounded-3xl p-6 md:p-8 border-2 border-dashed border-blue-300 shadow-sm hover:border-blue-500 transition-colors">
        <form action="{{ route('resi.upload') }}" method="POST" enctype="multipart/form-data" id="upload-form" class="space-y-4">
            @csrf
            <div class="text-center max-w-xl mx-auto">
                <h3 class="text-lg font-bold text-slate-900">Upload File PDF Resi</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Pilih target kanal resi atau gunakan <strong>Semua Toko</strong> untuk membaca kedua marketplace secara otomatis:
                </p>

                <!-- Radio Platform Switcher -->
                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                    <label class="cursor-pointer inline-flex items-center space-x-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition text-xs font-bold text-slate-900 shadow-xs">
                        <input type="radio" name="target_platform" id="radio-all" value="Semua Toko" checked class="text-blue-600 focus:ring-blue-500">
                        <span>Semua Toko</span>
                    </label>
                    <label class="cursor-pointer inline-flex items-center space-x-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition text-xs font-bold text-slate-900 shadow-xs">
                        <input type="radio" name="target_platform" id="radio-shopee" value="Shopee" class="text-blue-600 focus:ring-blue-500">
                        <span>Shopee</span>
                    </label>
                    <label class="cursor-pointer inline-flex items-center space-x-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition text-xs font-bold text-slate-900 shadow-xs">
                        <input type="radio" name="target_platform" id="radio-tiktok" value="TikTok Shop" class="text-blue-600 focus:ring-blue-500">
                        <span>TikTok / Tokped</span>
                    </label>
                </div>

                <div class="mt-6 flex flex-col items-center justify-center">
                    <label for="pdf_file" class="cursor-pointer inline-flex items-center space-x-2 px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 transition transform active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <span>Pilih File PDF Resi</span>
                    </label>
                    <p class="text-xs text-slate-400 mt-2 font-medium">atau tarik & lepas (drag and drop) file PDF langsung ke kotak ini</p>
                    <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" class="hidden" required onchange="handleFileSelect(this)">
                    
                    <div id="selected-file-info" class="mt-4 hidden p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 flex items-center space-x-2">
                        <span class="font-bold text-blue-600" id="file-name-text">filename.pdf</span>
                        <span class="text-slate-400" id="file-size-text">(0 MB)</span>
                        <button type="submit" id="btn-submit-upload" class="ml-3 px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow transition">
                            Mulai Proses Sekarang 🚀
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div id="upload-loading" class="mt-6 hidden flex-col items-center">
                        <div class="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                        <p class="text-xs font-semibold text-slate-700 mt-3 animate-pulse">Sedang membaca dan mengekstrak resi... Harap tunggu sebentar...</p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. TABEL RIWAYAT UPLOAD & REKAPITULASI RESI (Referensi Desain Gambar 4) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Riwayat Upload & Rekapitulasi Resi</h3>
                <p class="text-xs text-slate-400 mt-0.5">Daftar file PDF yang telah diproses beserta total stok keluarnya</p>
            </div>
            
            <!-- Filter Pills (Clean Text & Bingkai) -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('resi.index') }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ empty($platform) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua
                </a>
                <a href="{{ route('resi.index', ['platform' => 'Semua Toko']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition border {{ $platform === 'Semua Toko' ? 'bg-blue-600 text-white border-blue-600' : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100' }}">
                    Semua Toko
                </a>
                <a href="{{ route('resi.shopee') }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition border {{ $platform === 'Shopee' ? 'bg-orange-600 text-white border-orange-600' : 'bg-orange-50 text-orange-700 border-orange-200 hover:bg-orange-100' }}">
                    Shopee
                </a>
                <a href="{{ route('resi.tiktok') }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition border {{ $platform === 'TikTok Shop' ? 'bg-slate-900 text-cyan-300 border-slate-900' : 'bg-slate-100 text-slate-800 border-slate-300 hover:bg-slate-200' }}">
                    TikTok / Tokped
                </a>
            </div>
        </div>

        <div class="p-5 sm:p-6 space-y-4">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-700 border-collapse">
                    <thead class="bg-[#495098] text-white text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Kanal</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Nama File</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Halaman</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Jumlah Resi</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Total Barang Keluar</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Status</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Waktu Upload</th>
                            <th class="py-3 px-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium bg-white">
                        @forelse($batches as $b)
                            <tr class="hover:bg-slate-50/80 transition border-b border-slate-200 text-slate-900">
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                    @if($b->platform === 'Shopee')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                            Shopee
                                        </span>
                                    @elseif($b->platform === 'TikTok Shop')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                            TikTok/Tokped
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                            Semua Toko
                                        </span>
                                    @endif
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
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                            ✓ Siap Input
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
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
                                    Belum ada file PDF resi yang diupload. Silakan pilih salah satu opsi di atas.
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
    function selectPlatformUpload(platform) {
        if (platform === 'Semua Toko') {
            document.getElementById('radio-all').checked = true;
        } else if (platform === 'Shopee') {
            document.getElementById('radio-shopee').checked = true;
        } else if (platform === 'TikTok Shop') {
            document.getElementById('radio-tiktok').checked = true;
        }
        document.getElementById('upload-section').scrollIntoView({ behavior: 'smooth' });
    }

    function handleFileSelect(input) {
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
    const fileInput = document.getElementById('pdf_file');

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('border-blue-600', 'bg-blue-50/70', 'scale-[1.005]');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('border-blue-600', 'bg-blue-50/70', 'scale-[1.005]');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            if (files[0].type === 'application/pdf' || files[0].name.toLowerCase().endsWith('.pdf')) {
                fileInput.files = files;
                handleFileSelect(fileInput);
            } else {
                alert('File yang diupload harus berformat PDF!');
            }
        }
    }, false);
</script>
@endsection
