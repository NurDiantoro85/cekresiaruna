@extends('layouts.app')

@section('title', 'Hasil Rekap Resi: ' . $batch->batch_code)

@section('content')
<div class="space-y-6">
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
                <span>Diproses pada: {{ $batch->created_at->format('d M Y, H:i') }}</span>
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
        <!-- Tab Headers -->
        <div class="border-b border-slate-200 bg-slate-50/70 px-6 pt-4 flex space-x-4">
            <button onclick="switchTab('stodo')" id="tab-btn-stodo" class="tab-btn pb-3.5 px-3 text-sm font-bold border-b-2 border-blue-600 text-blue-700 flex items-center space-x-2">
                <span>📋 Rekap Stok Keluar</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">{{ count($mappedSummary) }}</span>
            </button>
            <button onclick="switchTab('stores')" id="tab-btn-stores" class="tab-btn pb-3.5 px-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2">
                <span>🏪 Rincian per Toko</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-slate-200 text-slate-700">4 Toko</span>
            </button>
            <button onclick="switchTab('waybills')" id="tab-btn-waybills" class="tab-btn pb-3.5 px-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2">
                <span>🚚 Detail Resi ({{ $batch->total_waybills }})</span>
            </button>
        </div>

        <!-- TAB 1: REKAP STOK KELUAR (PRIMARY VIEW) -->
        <div id="tab-content-stodo" class="p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
                <div class="text-xs text-slate-600">
                    💡 <strong>Tips Efisiensi Admin:</strong> Centang checkbox saat selesai pencatatan. Angka & varian acuan bisa di-copy instan dengan tombol <span class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono">📋</span>.
                </div>
                <div class="flex items-center space-x-3">
                    <input type="text" id="search-stodo" onkeyup="filterStodoTable()" placeholder="Cari varian / acuan packing..." class="text-xs border border-slate-300 rounded-xl px-3 py-1.5 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none w-64">
                    <button onclick="resetCheckboxes()" class="text-xs text-slate-500 hover:text-slate-800 font-medium underline">
                        Reset Centang
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-700 border-collapse" id="table-stodo">
                    <thead class="bg-[#495098] text-white text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3 w-10 text-center border-r border-indigo-200/30">Status</th>
                            @if($isShopee)
                                <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">
                                    Variasi (Acuan Utama Shopee)
                                </th>
                            @elseif($isTiktok)
                                <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">
                                    Seller SKU (Acuan Utama TikTok)
                                </th>
                            @elseif($isAllStores)
                                <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">
                                    Kode Acuan Packing (Kolom ke-2 Kanan)
                                </th>
                            @else
                                <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Acuan Packing</th>
                            @endif
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Brand</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Total Qty Keluar</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Rincian Keluar per Toko</th>
                            <th class="py-3 px-3.5 text-center">Copy</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium bg-white text-slate-900">
                        @forelse($mappedSummary as $key => $item)
                            <tr class="hover:bg-slate-50/80 transition border-b border-slate-200 stodo-row text-slate-900" id="row-{{ $key }}" data-search="{{ $item['name'] }} {{ $item['sku'] }} {{ $item['variant'] }} {{ $item['seller_sku'] }}">
                                <td class="py-3 px-3 text-center border-r border-slate-200">
                                    <input type="checkbox" onchange="toggleRowCheck(this, '{{ $key }}')" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </td>
                                @if($isShopee)
                                    <!-- Variasi as Primary Column for Shopee -->
                                    <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                        @if(!empty($item['variant']) && $item['variant'] !== '-' && !in_array(strtolower($item['variant']), ['default', 'none', 'null', 'kosong']))
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-50 text-slate-900 text-xs font-bold border border-slate-200">
                                                {{ $item['variant'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-50 text-slate-900 text-xs font-bold border border-slate-200">
                                                {{ $item['sku'] ?: 'Produk Tunggal' }}
                                            </span>
                                        @endif
                                    </td>
                                @elseif($isTiktok)
                                    <!-- Seller SKU as Primary Column for TikTok -->
                                    <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-50 text-slate-900 text-xs font-bold border border-slate-200">
                                            {{ $item['seller_sku'] ?: ($item['sku'] ?: ($item['variant'] ?: '-')) }}
                                        </span>
                                    </td>
                                @elseif($isAllStores)
                                    <!-- Kolom ke-2 dari Kanan untuk Semua Toko -->
                                    <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                        <div class="flex flex-col gap-1.5 items-center">
                                            @if(!empty($item['packing_codes']))
                                                @foreach($item['packing_codes'] as $plat => $codes)
                                                    @foreach($codes as $c)
                                                        <div class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-slate-50 text-slate-900 border border-slate-200 w-fit">
                                                            <span class="text-[10px] opacity-75 mr-1 font-bold">{{ $plat === 'Shopee' ? 'Shopee' : 'TikTok' }}:</span> {{ $c }}
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                                    {{ $item['variant'] ?: $item['sku'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                @else
                                    <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-50 text-slate-900 text-xs font-bold border border-slate-200">
                                            {{ $item['variant'] ?: ($item['sku'] ?: $item['name']) }}
                                        </span>
                                    </td>
                                @endif
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                        {{ $item['brand'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-xl text-xs font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                        {{ $item['total_qty'] }} <span class="text-[10px] font-bold ml-1 text-slate-700">{{ $item['unit'] }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200">
                                    <div class="flex flex-wrap gap-1.5 text-xs">
                                        @foreach($item['stores'] as $stName => $stQty)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-50 border border-slate-200 text-slate-900">
                                                <strong class="mr-1 text-slate-900">{{ $stName }}:</strong> {{ $stQty }} pcs
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-3.5 text-center">
                                    @php
                                        if ($isShopee) {
                                            $val = (!empty($item['variant']) && $item['variant'] !== '-') ? $item['variant'] : $item['sku'];
                                            $copyVal = ($val ?: $item['name']) . " ({$item['total_qty']})";
                                        } elseif ($isTiktok) {
                                            $copyVal = ($item['seller_sku'] ?: ($item['sku'] ?: $item['name'])) . " ({$item['total_qty']})";
                                        } else {
                                            $codesFlat = [];
                                            if (!empty($item['packing_codes'])) {
                                                foreach ($item['packing_codes'] as $cList) {
                                                    $codesFlat = array_merge($codesFlat, $cList);
                                                }
                                            }
                                            $copyVal = (!empty($codesFlat) ? implode(', ', array_unique($codesFlat)) : ($item['variant'] ?: $item['sku'])) . " ({$item['total_qty']})";
                                        }
                                    @endphp
                                    <button onclick="copyText('{{ addslashes($copyVal) }}')" class="px-2.5 py-1 rounded-lg text-xs font-bold text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition" title="Copy Acuan Utama">
                                        Copy
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-900 font-medium">
                                    Tidak ada data produk yang terpetakan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Sticky Summary Bar -->
            <div class="p-4 bg-slate-900 text-white rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div class="flex items-center space-x-4">
                    <span>Total Produk: <strong class="text-blue-300 font-bold">{{ count($mappedSummary) }} Jenis</strong></span>
                    <span>&bull;</span>
                    <span>Total Keseluruhan Barang Keluar: <strong class="text-emerald-300 font-bold">{{ $batch->total_qty }} pcs</strong></span>
                </div>
                <div>
                    Progress Pengecekan: <span id="checked-counter" class="font-bold text-amber-300">0</span> / {{ count($mappedSummary) }} selesai dicek
                </div>
            </div>
        </div>

        <!-- TAB 2: RINCIAN PER TOKO -->
        <div id="tab-content-stores" class="p-6 hidden space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($storeBreakdown as $stName => $stData)
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">{{ $stName }}</h3>
                                <p class="text-xs text-slate-500">{{ $stData['store']?->platform ?? '-' }} &bull; {{ $stData['store']?->courier ?? '-' }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-black text-blue-600">{{ $stData['total_qty'] }} <span class="text-xs text-slate-400">pcs</span></div>
                                <div class="text-[11px] font-semibold text-slate-500">{{ $stData['total_waybills'] }} resi</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Daftar Produk Keluar Toko Ini:</div>
                            <div class="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                                @forelse($stData['items'] as $itName => $itQty)
                                    <div class="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-lg bg-white border border-slate-100">
                                        <span class="text-slate-800 font-medium truncate max-w-xs">{{ $itName }}</span>
                                        <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">{{ $itQty }} pcs</span>
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
                                <td class="py-3 px-3.5 border-r border-slate-200 text-slate-900 font-bold text-center">#{{ $wb->page_number }}</td>
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
</div>

@push('scripts')
<script>
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
    const batchId = "{{ $batch->id }}";
    const storageKey = 'stodo_checked_' + batchId;

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
            row.classList.add('checked-row');
            if (!checked.includes(rowKey)) checked.push(rowKey);
        } else {
            row.classList.remove('checked-row');
            checked = checked.filter(k => k !== rowKey);
        }
        saveCheckedRows(checked);
    }

    function resetCheckboxes() {
        if (confirm('Reset seluruh checklist input StoDo untuk batch ini?')) {
            localStorage.removeItem(storageKey);
            document.querySelectorAll('#table-stodo input[type="checkbox"]').forEach(cb => {
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

    // Search filter for StoDo Table
    function filterStodoTable() {
        const q = document.getElementById('search-stodo').value.toLowerCase();
        document.querySelectorAll('.stodo-row').forEach(row => {
            const txt = (row.innerText + ' ' + (row.getAttribute('data-search') || '')).toLowerCase();
            row.style.display = txt.includes(q) ? '' : 'none';
        });
    }

    // Search filter for Waybill Table
    function filterWaybillTable() {
        const q = document.getElementById('search-waybill').value.toLowerCase();
        document.querySelectorAll('.waybill-row').forEach(row => {
            const txt = row.innerText.toLowerCase();
            row.style.display = txt.includes(q) ? '' : 'none';
        });
    }
</script>
@endpush
@endsection

