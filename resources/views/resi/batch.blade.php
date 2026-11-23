@extends('layouts.app')

@section('title', 'Hasil Rekap Resi: ' . $batch->batch_code)

@section('content')
<div x-data="{
    showResiModal: false,
    activeModalItem: null,
    activeModalStore: '',
    modalView: 'list',
    pdfPage: 1,
    pdfWaybillNo: '',
    pdfTotalPages: {{ $batch->total_pages }},
    pdfLoading: false,
    pdfScale: 1.2,
    openResiModal(item, storeName) {
        this.activeModalItem = item;
        this.activeModalStore = storeName;
        this.modalView = 'list';
        this.showResiModal = true;
    },
    openPdfPreview(page, waybillNo) {
        this.pdfPage = parseInt(page) || 1;
        this.pdfWaybillNo = waybillNo || '';
        this.modalView = 'pdf';
        this.showResiModal = true;
        this.$nextTick(() => {
            if (typeof renderPdfPage === 'function') {
                renderPdfPage(this.pdfPage, this.pdfScale);
            }
        });
    },
    backToList() {
        this.modalView = 'list';
    },
    prevPdfPage() {
        if (this.pdfPage > 1) {
            this.pdfPage--;
            if (typeof renderPdfPage === 'function') {
                renderPdfPage(this.pdfPage, this.pdfScale);
            }
        }
    },
    nextPdfPage() {
        if (this.pdfPage < this.pdfTotalPages) {
            this.pdfPage++;
            if (typeof renderPdfPage === 'function') {
                renderPdfPage(this.pdfPage, this.pdfScale);
            }
        }
    },
    zoomIn() {
        if (this.pdfScale < 2.5) {
            this.pdfScale = +(this.pdfScale + 0.25).toFixed(2);
            if (typeof renderPdfPage === 'function') {
                renderPdfPage(this.pdfPage, this.pdfScale);
            }
        }
    },
    zoomOut() {
        if (this.pdfScale > 0.6) {
            this.pdfScale = +(this.pdfScale - 0.25).toFixed(2);
            if (typeof renderPdfPage === 'function') {
                renderPdfPage(this.pdfPage, this.pdfScale);
            }
        }
    }
}" class="space-y-6">
    <!-- Breadcrumb & Top Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('resi.index') }}" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-800 mb-1">
                &larr; Kembali ke Dashboard
            </a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Hasil Rekapitulasi Resi & Stok Keluar</h1>
            <div class="flex items-center space-x-2 mt-2">
                @if($isShopee)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-900 border border-orange-200">
                        <span>Kanal: Shopee</span>
                        <span class="mx-1.5">&bull;</span>
                        <span class="underline font-extrabold">Acuan Utama: Variasi</span>
                    </span>
                @elseif($isTiktok)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-900 text-cyan-300 border border-slate-700">
                        <span>Kanal: TikTok / Tokopedia</span>
                        <span class="mx-1.5">&bull;</span>
                        <span class="underline font-extrabold">Acuan Utama: Seller SKU</span>
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                        <span>Kanal: Campuran</span>
                    </span>
                @endif
            </div>
            <div class="flex items-center space-x-3 text-xs text-slate-500 mt-2">
                <span class="font-mono font-bold text-slate-700">{{ $batch->batch_code }}</span>
                <span>&bull;</span>
                <span>File: <strong class="text-slate-800">{{ $batch->file_name }}</strong></span>
                <span>&bull;</span>
                <span>Diproses pada: <strong class="text-slate-800">{{ $batch->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</strong> ({{ $batch->created_at->timezone('Asia/Jakarta')->diffForHumans() }})</span>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('resi.export', $batch->id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 shadow-sm transition">
                <img src="{{ asset('icons/excel.svg') }}" class="w-4 h-4 object-contain" alt="Excel">
                <span>Download CSV (Excel)</span>
            </a>
            <button onclick="window.print()" class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 transition">
                🖨️ Cetak
            </button>
        </div>
    </div>

    <!-- Batch Summary Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Halaman PDF</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $batch->total_pages }} <span class="text-xs font-normal text-slate-400">halaman</span></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Resi / Paket</div>
            <div class="text-2xl font-extrabold text-blue-600 mt-1">{{ $batch->total_waybills }} <span class="text-xs font-normal text-slate-400">paket</span></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Barang Keluar</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $batch->total_qty }} <span class="text-xs font-normal text-slate-400">pcs</span></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Variasi Produk</div>
            <div class="text-2xl font-extrabold text-indigo-600 mt-1">{{ count($mappedSummary) + count($unmappedSummary) }} <span class="text-xs font-normal text-slate-400">jenis</span></div>
        </div>
    </div>

    <!-- Alert for Detected Typos / Suspected Spelling Mismatch -->
    @if(isset($detectedTypos) && count($detectedTypos) > 0)
        <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5 shadow-sm space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start space-x-3">
                    <span class="text-2xl flex-shrink-0">🔔</span>
                    <div>
                        <h3 class="text-sm font-black text-amber-900 flex items-center gap-2">
                            <span>Notifikasi Pemeriksaan: Ditemukan {{ count($detectedTypos) }} Potensi Salah Ketik (Typo) di Label Resi!</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-200 text-amber-900">
                                Perlu Dicek
                            </span>
                        </h3>
                        <p class="text-xs text-amber-800 mt-1">
                            Sistem mendeteksi ada teks variasi/SKU pada resi yang memiliki kemiripan tinggi dengan kamus variasi Anda, namun kemungkinan ada salah ketik (typo). Harap periksa saran di bawah:
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                @foreach($detectedTypos as $typo)
                    <div class="bg-white p-3.5 rounded-xl border border-amber-200 text-xs shadow-2xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-red-700 font-mono font-bold text-[11px] bg-red-50 border border-red-200 px-2 py-0.5 rounded">
                                Resi: "{{ $typo['raw_text'] }}"
                            </span>
                            <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full">
                                Kemiripan {{ $typo['similarity'] }}%
                            </span>
                        </div>
                        <div class="text-emerald-800 font-bold flex items-center gap-1">
                            <span>➔ Maksudnya:</span>
                            <span class="underline underline-offset-2">{{ $typo['suggested_variant'] }}</span>?
                        </div>
                        <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-100 flex items-center justify-between">
                            <span>Hal #{{ $typo['page_number'] }} ({{ $typo['waybill_no'] }})</span>
                            <span class="font-bold text-slate-700">{{ $typo['count'] }} pcs</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Alert for Unmapped Items if any (Dropdown Accordion) -->
    @if(count($unmappedSummary) > 0)
        <div x-data="{ open: false }" class="bg-amber-50 border border-amber-300 rounded-2xl shadow-sm overflow-hidden transition-all">
            <!-- Header bar / Toggle Button -->
            <button type="button" @click="open = !open" class="w-full p-4 sm:p-5 flex items-center justify-between text-left hover:bg-amber-100/50 transition">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl flex-shrink-0">⚠️</span>
                    <div>
                        <h3 class="text-sm font-bold text-amber-900 flex flex-wrap items-center gap-2">
                            <span>Ditemukan {{ count($unmappedSummary) }} Produk Belum Terpetakan ke Master Produk!</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-200 text-amber-900">
                                Perlu Pemetaan
                            </span>
                        </h3>
                        <p class="text-xs text-amber-700 mt-0.5">
                            Klik di sini untuk <span x-text="open ? 'menutup form pemetaan' : 'melihat & memetakan produk'">melihat & memetakan produk</span> ke Master Produk agar rekapitulasi 100% rapi.
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 text-xs font-bold text-amber-900 bg-amber-200/80 px-3 py-1.5 rounded-xl border border-amber-300 flex-shrink-0 shadow-2xs">
                    <span x-text="open ? 'Tutup Daftar' : 'Buka Daftar (' + {{ count($unmappedSummary) }} + ')'">Buka Daftar ({{ count($unmappedSummary) }})</span>
                    <svg class="w-4 h-4 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </button>

            <!-- Collapsible List -->
            <div x-show="open" x-collapse x-cloak class="p-5 pt-0 border-t border-amber-200/70">
                <p class="text-xs text-amber-700 my-3">
                    Produk di bawah ini ditemukan di resi namun belum terhubung ke Master Produk seragam. Silakan petakan sekali saja:
                </p>
                <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                    @foreach($unmappedSummary as $key => $unm)
                        <form action="{{ route('resi.quickMap', $batch->id) }}" method="POST" class="bg-white p-3.5 rounded-xl border border-amber-200 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-2xs">
                            @csrf
                            <input type="hidden" name="platform" value="{{ $unm['platform'] }}">
                            <input type="hidden" name="raw_sku" value="{{ $unm['raw_sku'] !== '-' ? $unm['raw_sku'] : '' }}">
                            <input type="hidden" name="raw_variant" value="{{ $unm['raw_variant'] !== '-' ? $unm['raw_variant'] : '' }}">

                            <div class="text-xs">
                                <div class="font-bold text-slate-900">{{ $unm['raw_product_name'] }}</div>
                                <div class="text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                                    <span>Platform: <strong class="text-slate-800">{{ $unm['platform'] }}</strong></span>
                                    <span>&bull;</span>
                                    <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-900 font-bold text-[11px] border border-blue-200">
                                        Kolom ke-2 Kanan (Acuan): {{ $unm['primary_acuan'] }}
                                    </span>
                                    <span>&bull;</span>
                                    <span>Jumlah: <strong class="text-emerald-700 font-bold">{{ $unm['total_qty'] }} pcs</strong></span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 flex-shrink-0">
                                <select name="master_product_id" required class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="">-- Pilih Master Produk Acuan --</option>
                                    @foreach($allMasterProducts as $mp)
                                        <option value="{{ $mp->id }}">{{ $mp->display_name }} ({{ $mp->brand }})</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition">
                                    Hubungkan 🔗
                                </button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Tabs Container -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        @php
            $totalUniqueVariants = collect($storeGroups)->sum(fn($g) => count($g['items']));
        @endphp

        <!-- Tab Headers -->
        <div class="border-b border-slate-200 bg-slate-50/70 px-6 pt-4 flex space-x-4">
            <button onclick="switchTab('stodo')" id="tab-btn-stodo" class="tab-btn pb-3.5 px-3 text-sm font-bold border-b-2 border-blue-600 text-blue-700 flex items-center space-x-2">
                <span>📋 Rekap Stok Keluar</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">{{ $totalUniqueVariants }} Baris</span>
            </button>
            <button onclick="switchTab('stores')" id="tab-btn-stores" class="tab-btn pb-3.5 px-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2">
                <span>🏪 Rincian per Toko</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-slate-200 text-slate-700">{{ count($storeGroups) }} Toko</span>
            </button>
            <button onclick="switchTab('waybills')" id="tab-btn-waybills" class="tab-btn pb-3.5 px-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2">
                <span>🚚 Detail Resi ({{ $batch->total_waybills }})</span>
            </button>
        </div>

        <!-- TAB 1: REKAP STOK KELUAR (DIPISAH PER TOKO & SESUAI URUTAN HALAMAN PDF) -->
        <div id="tab-content-stodo" class="p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
                <div class="text-xs text-slate-600">
                    💡 <strong>Tips Efisiensi Admin:</strong> Rekap telah dipisah per toko dan diurutkan sesuai urutan halaman PDF. Klik pada <strong>nama varian</strong> untuk mengecek rincian resi dan halaman PDF tempat data terbaca.
                </div>
                <div class="flex items-center space-x-3">
                    <input type="text" id="search-stodo" onkeyup="filterStodoTable()" placeholder="Cari varian / seller sku..." class="text-xs border border-slate-300 rounded-xl px-3 py-1.5 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none w-64">
                    <button onclick="resetCheckboxes()" class="text-xs text-slate-500 hover:text-slate-800 font-medium underline">
                        Reset Centang
                    </button>
                </div>
            </div>

            <!-- List of Stores with Section Divider -->
            <div class="space-y-6" id="stodo-store-container">
                @forelse($storeGroups as $stName => $group)
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden store-section" data-store="{{ strtolower($stName) }}">
                        <!-- Pembatas & Header Toko -->
                        <div class="bg-[#495098] text-white px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <span class="text-xl">🏪</span>
                                <div>
                                    <h3 class="font-black text-base tracking-tight text-white">{{ $stName }}</h3>
                                    <div class="text-xs text-indigo-100 flex flex-wrap items-center gap-2 mt-0.5">
                                        <span>{{ $group['platform'] }}</span>
                                        <span>&bull;</span>
                                        <span>Kurir: <strong class="text-white">{{ $group['courier'] }}</strong></span>
                                        <span>&bull;</span>
                                        <span class="bg-white/20 px-2 py-0.5 rounded font-mono font-bold text-[11px]">
                                            Halaman PDF: #{{ $group['first_page'] }} - #{{ $group['last_page'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="bg-white/15 px-3 py-1 rounded-xl font-bold">
                                    {{ $group['total_waybills'] }} Resi
                                </span>
                                <span class="bg-emerald-400 text-slate-900 px-3 py-1 rounded-xl font-extrabold shadow-2xs">
                                    {{ $group['total_qty'] }} pcs
                                </span>
                            </div>
                        </div>

                        <!-- Tabel Sesuai Format Mockup Excel: No. | ceklis | varian/seller sku | qty -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-700 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="py-2.5 px-3 w-14 text-center border-r border-slate-200">No.</th>
                                        <th class="py-2.5 px-3 w-16 text-center border-r border-slate-200">ceklis</th>
                                        <th class="py-2.5 px-4 border-r border-slate-200">varian/seller sku</th>
                                        <th class="py-2.5 px-4 w-28 text-center">qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium bg-white text-slate-900">
                                    @php $no = 1; @endphp
                                    @forelse($group['items'] as $itemKey => $item)
                                        @php $rowId = 'stodo_' . Str::slug($stName . '_' . $itemKey); @endphp
                                        <tr class="hover:bg-blue-50/40 transition border-b border-slate-100 stodo-row" id="row-{{ $rowId }}" data-search="{{ strtolower($stName . ' ' . $item['variant_name']) }}">
                                            <td class="py-2.5 px-3 text-center border-r border-slate-100 text-slate-500 font-bold">
                                                {{ $no++ }}.
                                            </td>
                                            <td class="py-2.5 px-3 text-center border-r border-slate-100">
                                                <input type="checkbox" onchange="toggleRowCheck(this, '{{ $rowId }}')" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                                            </td>
                                            <td class="py-2.5 px-4 border-r border-slate-100">
                                                <div class="flex items-center justify-between group">
                                                    <button type="button" @click="openResiModal({{ json_encode($item) }}, '{{ addslashes($stName) }}')" class="text-left font-bold text-slate-900 hover:text-blue-600 transition flex items-center gap-2 cursor-pointer">
                                                        <span class="text-sm">{{ $item['variant_name'] }}</span>
                                                        <span class="text-[10px] text-blue-600 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-md font-semibold opacity-80 group-hover:opacity-100 group-hover:bg-blue-600 group-hover:text-white transition flex items-center gap-1">
                                                            🔍 Cek Resi ({{ count($item['waybill_items']) }})
                                                        </span>
                                                    </button>
                                                    <button type="button" onclick="copyText('{{ addslashes($item['variant_name'] . ' (' . $item['total_qty'] . ')') }}')" class="text-[10px] text-slate-400 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 px-2 py-0.5 rounded border border-slate-200 transition opacity-0 group-hover:opacity-100" title="Copy Varian & Qty">
                                                        Copy
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-4 text-center font-black text-slate-900 text-sm">
                                                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-extrabold">
                                                    {{ $item['total_qty'] }} <span class="text-[11px] font-normal ml-1 text-emerald-600">pcs</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-6 text-center text-slate-400">Tidak ada variasi di toko ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400 bg-slate-50 rounded-2xl border border-slate-200">
                        Tidak ada data toko yang terdeteksi.
                    </div>
                @endforelse
            </div>

            <!-- Sticky Summary Bar -->
            <div class="p-4 bg-slate-900 text-white rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div class="flex items-center space-x-4">
                    <span>Total Toko: <strong class="text-blue-300 font-bold">{{ count($storeGroups) }} Toko</strong></span>
                    <span>&bull;</span>
                    <span>Total Variasi: <strong class="text-indigo-300 font-bold">{{ $totalUniqueVariants }} Baris</strong></span>
                    <span>&bull;</span>
                    <span>Total Keseluruhan Barang Keluar: <strong class="text-emerald-300 font-bold">{{ $batch->total_qty }} pcs</strong></span>
                </div>
                <div>
                    Progress Pengecekan: <span id="checked-counter" class="font-bold text-amber-300">0</span> selesai dicek
                </div>
            </div>
        </div>

        <!-- TAB 2: RINCIAN PER TOKO -->
        <div id="tab-content-stores" class="p-6 hidden space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($storeGroups as $stName => $stData)
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">{{ $stName }}</h3>
                                <p class="text-xs text-slate-500">
                                    {{ $stData['platform'] ?? '-' }} &bull; Kurir: {{ $stData['courier'] ?? '-' }} &bull; Hal: #{{ $stData['first_page'] }} - #{{ $stData['last_page'] }}
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-black text-blue-600">{{ $stData['total_qty'] }} <span class="text-xs text-slate-400">pcs</span></div>
                                <div class="text-[11px] font-semibold text-slate-500">{{ $stData['total_waybills'] }} resi</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Daftar Produk Keluar Toko Ini (Klik untuk cek resi):</div>
                            <div class="space-y-1.5 max-h-80 overflow-y-auto pr-1">
                                @forelse($stData['items'] as $itKey => $it)
                                    <div class="flex items-center justify-between text-xs py-2 px-3 rounded-xl bg-white border border-slate-200/80 hover:border-blue-400 hover:shadow-2xs transition group">
                                        <button type="button" @click="openResiModal({{ json_encode($it) }}, '{{ addslashes($stName) }}')" class="text-left font-bold text-slate-800 hover:text-blue-600 truncate max-w-xs cursor-pointer flex items-center gap-2">
                                            <span>{{ $it['variant_name'] }}</span>
                                            <span class="text-[10px] text-blue-500 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200 opacity-70 group-hover:opacity-100">
                                                🔍 Cek Resi ({{ count($it['waybill_items']) }})
                                            </span>
                                        </button>
                                        <span class="font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-lg">{{ $it['total_qty'] }} pcs</span>
                                    </div>
                                @empty
                                    <div class="text-xs text-slate-400 py-3 text-center">Tidak ada pesanan dari toko ini pada file PDF ini.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- TAB 3: DETAIL RESI (WAYBILLS) -->
        <div id="tab-content-waybills" class="p-6 hidden space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-xs text-slate-500">Daftar {{ $batch->waybills->count() }} resi yang diekstrak dari PDF:</p>
                <input type="text" id="search-waybill" onkeyup="filterWaybillTable()" placeholder="Cari nomor resi / penerima..." class="text-xs border border-slate-300 rounded-xl px-3 py-1.5 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none w-64">
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-700 border-collapse" id="table-waybill">
                    <thead class="bg-[#495098] text-white text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Hal</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">No. Resi</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">No. Pesanan</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Platform & Kurir</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Toko Terdeteksi</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Daftar Barang & Qty</th>
                            <th class="py-3 px-3.5 text-center">Total Pcs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium bg-white text-slate-900">
                        @foreach($batch->waybills as $wb)
                            <tr class="hover:bg-slate-50/80 transition border-b border-slate-200 waybill-row text-slate-900">
                                <td class="py-3 px-3.5 border-r border-slate-200 text-slate-900 font-bold text-center">
                                    <button type="button" @click="openPdfPreview({{ $wb->page_number }}, '{{ $wb->waybill_no }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-mono font-bold text-xs text-blue-700 bg-blue-50 hover:bg-blue-600 hover:text-white transition shadow-2xs cursor-pointer" title="Pratinjau Halaman Resi">
                                        <span>#{{ $wb->page_number }}</span>
                                        <span class="text-[10px]">📄</span>
                                    </button>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 font-mono font-bold text-slate-900 text-center">
                                    {{ $wb->waybill_no }}
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-slate-900 font-mono text-center">{{ $wb->order_no ?: '-' }}</td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                        {{ $wb->platform === 'Shopee' ? 'Shopee' : 'TikTok/Tokped' }} ({{ $wb->courier }})
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 font-semibold text-slate-900 text-center">
                                    {{ $wb->store?->name ?? 'Tidak Terdeteksi' }}
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-slate-900">
                                    <div class="space-y-1">
                                        @foreach($wb->items as $it)
                                            <div class="text-slate-900">
                                                &bull; <strong class="text-slate-900">{{ $it->qty }}x</strong> 
                                                {{ $it->masterProduct?->name ?? $it->raw_product_name }}
                                                <span class="text-slate-700">({{ $it->masterProduct?->variant ?? $it->raw_variant }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-3.5 text-center font-bold text-slate-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                        {{ $wb->total_qty }} pcs
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Unified Modal: Detail Resi Terbaca & Pratinjau PDF -->
    <div x-show="showResiModal" x-cloak class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 z-50 overflow-y-auto">
        <div @click.away="showResiModal = false" :class="modalView === 'pdf' ? 'max-w-4xl' : 'max-w-2xl'" class="bg-white rounded-3xl w-full shadow-2xl border border-slate-200 overflow-hidden my-4 max-h-[94vh] flex flex-col transition-all duration-200">
            
            <!-- ================= VIEW 1: DAFTAR RESI (LIST) ================= -->
            <template x-if="modalView === 'list'">
                <div class="flex flex-col h-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="bg-slate-900 text-white p-5 flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🔍</span>
                                <h3 class="text-lg font-black text-white" x-text="activeModalItem?.variant_name"></h3>
                            </div>
                            <p class="text-xs text-slate-300 mt-1 flex flex-wrap items-center gap-2">
                                <span>Toko: <strong class="text-white" x-text="activeModalStore"></strong></span>
                                <span>&bull;</span>
                                <span>Total Qty: <strong class="text-emerald-400" x-text="(activeModalItem?.total_qty || 0) + ' pcs'"></strong></span>
                                <span>&bull;</span>
                                <span>Ditemukan di: <strong class="text-blue-300" x-text="(activeModalItem?.waybill_items?.length || 0) + ' Resi / Halaman'"></strong></span>
                            </p>
                        </div>
                        <button type="button" @click="showResiModal = false" class="text-slate-400 hover:text-white text-2xl font-bold leading-none p-1 cursor-pointer">&times;</button>
                    </div>

                    <!-- Modal Info Banner -->
                    <div class="bg-blue-50/70 px-5 py-3 border-b border-blue-100 text-xs text-blue-900 flex items-center justify-between">
                        <span>Daftar resi di file PDF yang membaca varian ini. Anda dapat memeriksa hasil teks yang terbaca dan membuka halaman PDF langsung:</span>
                    </div>

                    <!-- Modal Body (List of Waybills) -->
                    <div class="p-5 space-y-3 overflow-y-auto flex-1">
                        <template x-for="(wb, idx) in (activeModalItem?.waybill_items || [])" :key="idx">
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 hover:border-blue-300 transition space-y-2.5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-600 text-white font-black text-xs font-mono shadow-2xs">
                                            Hal #<span x-text="wb.page_number"></span>
                                        </span>
                                        <div>
                                            <div class="font-mono font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                                <span x-text="wb.waybill_no"></span>
                                                <button type="button" @click="copyText(wb.waybill_no)" class="text-[10px] text-slate-400 hover:text-slate-700 font-sans px-1 rounded bg-white border border-slate-200 cursor-pointer" title="Copy Resi">Copy</button>
                                            </div>
                                            <div class="text-[11px] text-slate-500" x-show="wb.order_no">
                                                No. Pesanan: <span class="font-mono" x-text="wb.order_no"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800">
                                            Qty: <strong x-text="wb.qty + ' pcs'"></strong>
                                        </span>
                                        <button type="button" @click="openPdfPreview(wb.page_number, wb.waybill_no)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-2xs transition cursor-pointer">
                                            <span>📄 Buka Hal #<span x-text="wb.page_number"></span></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Teks Asli yang Terbaca dari Resi -->
                                <div class="bg-white p-3 rounded-xl border border-slate-200 space-y-1.5 text-xs">
                                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                        Bagian Resi yang Terbaca:
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                            <span class="text-[10px] text-slate-400 font-bold block">Kolom SKU di Resi:</span>
                                            <span class="font-mono font-bold text-slate-800" x-text="wb.raw_sku || '-'"></span>
                                        </div>
                                        <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                            <span class="text-[10px] text-slate-400 font-bold block">Kolom Variasi di Resi:</span>
                                            <span class="font-mono font-bold text-blue-700" x-text="wb.raw_variant || '-'"></span>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                        <span class="text-[10px] text-slate-400 font-bold block">Nama Produk di Resi:</span>
                                        <span class="text-slate-700 font-medium" x-text="wb.raw_product_name"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-slate-50 p-4 border-t border-slate-200 flex justify-end">
                        <button type="button" @click="showResiModal = false" class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </template>

            <!-- ================= VIEW 2: PRATINJAU PDF (PDF VIEWER) ================= -->
            <template x-if="modalView === 'pdf'">
                <div class="flex flex-col h-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="bg-slate-900 text-white px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <button type="button" x-show="activeModalItem" @click="backToList()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white border border-slate-700 transition cursor-pointer shadow-2xs">
                                <span>&larr;</span> <span>Kembali ke Daftar Resi</span>
                            </button>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-sm text-white">Pratinjau Resi PDF</h3>
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-black bg-blue-600 text-white font-mono">
                                        Hal #<span x-text="pdfPage"></span> / <span x-text="pdfTotalPages"></span>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5" x-show="pdfWaybillNo">
                                    No. Resi: <strong class="text-slate-200 font-mono" x-text="pdfWaybillNo"></strong>
                                </p>
                            </div>
                        </div>

                        <!-- Controls & Navigation -->
                        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                            <!-- Page Prev / Next -->
                            <div class="inline-flex items-center bg-slate-800 rounded-xl p-1 border border-slate-700">
                                <button type="button" @click="prevPdfPage()" :disabled="pdfPage <= 1" class="px-2.5 py-1 text-xs font-bold text-slate-200 hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-transparent rounded-lg transition cursor-pointer" title="Halaman Sebelumnya">
                                    &larr; Prev
                                </button>
                                <span class="px-2 text-xs font-mono text-slate-300 border-x border-slate-700">
                                    <span x-text="pdfPage"></span> / <span x-text="pdfTotalPages"></span>
                                </span>
                                <button type="button" @click="nextPdfPage()" :disabled="pdfPage >= pdfTotalPages" class="px-2.5 py-1 text-xs font-bold text-slate-200 hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-transparent rounded-lg transition cursor-pointer" title="Halaman Berikutnya">
                                    Next &rarr;
                                </button>
                            </div>

                            <!-- Zoom Controls -->
                            <div class="inline-flex items-center bg-slate-800 rounded-xl p-1 border border-slate-700 text-xs">
                                <button type="button" @click="zoomOut()" class="px-2 py-1 text-slate-300 hover:bg-slate-700 rounded-lg transition cursor-pointer" title="Perkecil (-)">
                                    🔍-
                                </button>
                                <span class="px-1 text-[11px] font-mono text-slate-400" x-text="Math.round(pdfScale * 100) + '%'"></span>
                                <button type="button" @click="zoomIn()" class="px-2 py-1 text-slate-300 hover:bg-slate-700 rounded-lg transition cursor-pointer" title="Perbesar (+)">
                                    🔍+
                                </button>
                            </div>

                            <!-- Close -->
                            <button type="button" @click="showResiModal = false" class="p-1.5 px-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition text-lg leading-none cursor-pointer" title="Tutup Modal">
                                &times;
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body: Canvas Area -->
                    <div class="relative flex-1 bg-slate-900/95 overflow-auto p-4 sm:p-6 flex items-center justify-center min-h-[480px]">
                        <!-- Loading Indicator -->
                        <div id="pdf-loading-indicator" class="absolute inset-0 bg-slate-900/80 backdrop-blur-xs flex flex-col items-center justify-center text-white gap-3 z-10" style="display: none;">
                            <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-xs font-bold text-slate-300">Merender halaman resi...</span>
                        </div>

                        <!-- Error Indicator -->
                        <div id="pdf-error-indicator" class="flex-col items-center justify-center text-center p-8 bg-slate-800/90 rounded-2xl border border-red-500/40 text-white max-w-md gap-3 z-10" style="display: none;">
                            <span class="text-4xl">⚠️</span>
                            <div class="font-bold text-base text-red-300">File PDF Resi Tidak Ditemukan</div>
                            <p class="text-xs text-slate-300 mt-1" id="pdf-error-message">Berkas PDF tidak ditemukan di server atau browser membatasi pembacaan berkas.</p>
                            <div class="mt-4 flex items-center justify-center gap-2">
                                <a :href="'/batch/' + batchId + '/pdf'" target="_blank" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white transition shadow-sm">
                                    Cek Unduh Langsung ↗
                                </a>
                            </div>
                        </div>

                        <!-- Canvas Wrapper -->
                        <div id="pdf-canvas-wrapper" class="shadow-2xl rounded-xl overflow-hidden bg-white border border-slate-300/30" style="display: none;">
                            <canvas id="pdf-canvas" class="block max-w-full h-auto"></canvas>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-slate-900 px-5 py-3 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-400">
                        <div>
                            <button type="button" x-show="activeModalItem" @click="backToList()" class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300 font-bold transition cursor-pointer">
                                <span>&larr; Kembali ke Rincian Resi</span>
                            </button>
                            <span x-show="!activeModalItem" class="text-slate-400">
                                💡 Tips: Gunakan tombol <strong>Prev</strong> / <strong>Next</strong> atau tombol Zoom untuk memeriksa label resi.
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="showResiModal = false" class="px-5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold transition cursor-pointer">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
<script>
    const batchId = "{{ $batch->id }}";
    const storageKey = 'stodo_checked_' + batchId;
    const pdfUrl = "{{ route('resi.pdf', $batch->id) }}";

    // Configure PDF.js worker
    if (window.pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
    }

    let pdfDocInstance = null;
    let currentRenderTask = null;

    async function getPdfDocument() {
        if (!pdfDocInstance) {
            const loadingTask = pdfjsLib.getDocument(pdfUrl);
            pdfDocInstance = await loadingTask.promise;
        }
        return pdfDocInstance;
    }

    async function renderPdfPage(pageNum, scale = 1.2) {
        let canvas = document.getElementById('pdf-canvas');
        if (!canvas) {
            await new Promise(r => setTimeout(r, 60));
            canvas = document.getElementById('pdf-canvas');
        }
        if (!canvas) return;
        const loadingEl = document.getElementById('pdf-loading-indicator');

        const wrapper = document.getElementById('pdf-canvas-wrapper');
        const errorEl = document.getElementById('pdf-error-indicator');
        const errorMsgEl = document.getElementById('pdf-error-message');

        if (errorEl) errorEl.style.display = 'none';
        if (loadingEl) loadingEl.style.display = 'flex';

        if (currentRenderTask) {
            try {
                currentRenderTask.cancel();
            } catch (err) {}
            currentRenderTask = null;
        }

        try {
            const doc = await getPdfDocument();
            const page = await doc.getPage(pageNum);
            const ctx = canvas.getContext('2d');

            const pixelRatio = window.devicePixelRatio || 1;
            const viewport = page.getViewport({ scale: scale });

            canvas.width = Math.floor(viewport.width * pixelRatio);
            canvas.height = Math.floor(viewport.height * pixelRatio);
            canvas.style.width = Math.floor(viewport.width) + 'px';
            canvas.style.height = Math.floor(viewport.height) + 'px';

            ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            currentRenderTask = page.render(renderContext);
            await currentRenderTask.promise;
            currentRenderTask = null;

            if (wrapper) wrapper.style.display = 'block';
        } catch (err) {
            if (err && err.name !== 'RenderingCancelledException') {
                console.error('Gagal merender halaman PDF:', err);
                if (wrapper) wrapper.style.display = 'none';
                if (errorEl) {
                    errorEl.style.display = 'flex';
                    if (errorMsgEl && err.message) {
                        errorMsgEl.textContent = 'File PDF tidak dapat dimuat: ' + err.message;
                    }
                }
            }
        } finally {
            if (loadingEl) loadingEl.style.display = 'none';
        }
    }

    // Tab Switching
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-blue-600', 'text-blue-700');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        document.getElementById('tab-btn-' + tab).classList.add('border-blue-600', 'text-blue-700');
        document.getElementById('tab-btn-' + tab).classList.remove('border-transparent', 'text-slate-500');

        document.getElementById('tab-content-stodo').classList.add('hidden');
        document.getElementById('tab-content-stores').classList.add('hidden');
        document.getElementById('tab-content-waybills').classList.add('hidden');

        document.getElementById('tab-content-' + tab).classList.remove('hidden');
    }

    // Row Checkbox for StoDo input tracker
    function getCheckedRows() {
        return JSON.parse(localStorage.getItem(storageKey) || '[]');
    }

    function saveCheckedRows(arr) {
        localStorage.setItem(storageKey, JSON.stringify(arr));
        updateCounter();
    }

    function toggleRowCheck(checkbox, rowKey) {
        const row = document.getElementById('row-' + rowKey);
        let checked = getCheckedRows();

        if (checkbox.checked) {
            if (row) row.classList.add('checked-row');
            if (!checked.includes(rowKey)) checked.push(rowKey);
        } else {
            if (row) row.classList.remove('checked-row');
            checked = checked.filter(k => k !== rowKey);
        }
        saveCheckedRows(checked);
    }

    function resetCheckboxes() {
        if (confirm('Reset seluruh checklist input StoDo untuk batch ini?')) {
            localStorage.removeItem(storageKey);
            document.querySelectorAll('#tab-content-stodo input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
            });
            document.querySelectorAll('.stodo-row').forEach(r => r.classList.remove('checked-row'));
            updateCounter();
        }
    }

    function updateCounter() {
        const checked = getCheckedRows();
        const el = document.getElementById('checked-counter');
        if (el) el.innerText = checked.length;
    }

    // Restore checklist on load
    document.addEventListener('DOMContentLoaded', () => {
        const checked = getCheckedRows();
        checked.forEach(key => {
            const row = document.getElementById('row-' + key);
            if (row) {
                row.classList.add('checked-row');
                const cb = row.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = true;
            }
        });
        updateCounter();
    });

    // Search filter for StoDo Table (Hides empty store sections when filtering)
    function filterStodoTable() {
        const q = document.getElementById('search-stodo').value.toLowerCase().trim();
        document.querySelectorAll('.store-section').forEach(section => {
            let visibleCount = 0;
            section.querySelectorAll('.stodo-row').forEach(row => {
                const txt = (row.innerText + ' ' + (row.getAttribute('data-search') || '')).toLowerCase();
                const match = txt.includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            section.style.display = (visibleCount > 0 || q === '') ? '' : 'none';
        });
    }

    // Search filter for Waybill Table
    function filterWaybillTable() {
        const q = document.getElementById('search-waybill').value.toLowerCase().trim();
        document.querySelectorAll('.waybill-row').forEach(row => {
            const txt = row.innerText.toLowerCase();
            row.style.display = txt.includes(q) ? '' : 'none';
        });
    }

    // Copy to clipboard helper
    function copyText(txt) {
        if (!txt) return;
        navigator.clipboard.writeText(txt).then(() => {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-5 right-5 bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-2xl z-50 transition-all border border-slate-700 flex items-center gap-2';
            toast.innerHTML = '<span>📋</span> <span>Berhasil disalin: <strong>' + txt + '</strong></span>';
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }).catch(err => {
            console.error('Copy failed: ', err);
        });
    }
</script>
@endpush
@endsection

