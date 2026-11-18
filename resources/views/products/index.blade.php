@extends('layouts.app')

@section('title', 'Master Produk & Kamus Variasi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Master Produk & Kamus Variasi</h1>
            <p class="text-slate-500 text-sm mt-1">
                Kelola data master produk dan kamus Variasi / Seller SKU acuan packing StoDo.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button onclick="toggleModal('modal-import-csv')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 shadow-xs transition">
                <img src="{{ asset('icons/excel.svg') }}" class="w-4 h-4 object-contain" alt="Excel">
                <span>Import CSV Variasi / Seller SKU</span>
            </button>
            <button onclick="toggleModal('modal-add-product')" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition">
                <span>+ Tambah Master Produk</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Master Produk</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $products->total() }} <span class="text-xs font-normal text-slate-400">item</span></div>
            <p class="text-[11px] text-slate-400 mt-1">Produk acuan siap input StoDo</p>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kamus Variasi / Seller SKU</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $variants->count() }} <span class="text-xs font-normal text-slate-400">varian</span></div>
            <p class="text-[11px] text-slate-400 mt-1">Acuan packing (kolom ke-2 kanan)</p>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Aturan Alias Resi</div>
            <div class="text-2xl font-extrabold text-blue-600 mt-1">{{ $totalMappings }} <span class="text-xs font-normal text-slate-400">alias</span></div>
            <p class="text-[11px] text-slate-400 mt-1">Pemetaan otomatis kode resi</p>
        </div>
    </div>

    <!-- Main Card with Navigation Tabs -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Tabs Header -->
        <div class="border-b border-slate-200 bg-slate-50/70 px-6 pt-4 flex space-x-4">
            <button onclick="switchProductTab('list')" id="ptab-btn-list" class="ptab-btn pb-3.5 px-3 text-sm font-bold border-b-2 border-blue-600 text-blue-700 flex items-center space-x-2">
                <span>📦 Daftar Master Produk</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">{{ $products->total() }}</span>
            </button>
            <button onclick="switchProductTab('filters')" id="ptab-btn-filters" class="ptab-btn pb-3.5 px-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2">
                <span>⚙️ Kamus Variasi / Seller SKU</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800">{{ $variants->count() }}</span>
            </button>
        </div>

        <!-- TAB 1: DAFTAR MASTER PRODUK (Referensi Desain Gambar 4) -->
        <div id="ptab-content-list" class="p-6 space-y-4">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-700 border-collapse">
                    <thead class="bg-[#495098] text-white text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Nama Produk</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Variasi / Seller SKU</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Brand</th>
                            <th class="py-3 px-3.5 border-r border-indigo-200/30 text-center">Aturan Alias Resi</th>
                            <th class="py-3 px-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium bg-white text-slate-900">
                        @forelse($products as $p)
                            <tr class="hover:bg-slate-50/80 transition border-b border-slate-200 text-slate-900">
                                <td class="py-3 px-3.5 border-r border-slate-200">
                                    <div class="font-bold text-slate-900">{{ $p->name }}</div>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-50 text-slate-900 text-xs font-bold border border-slate-200">
                                        {{ $p->variant ?: '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-50 text-slate-900 border border-slate-200">
                                        {{ $p->brand }}
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 border-r border-slate-200 text-slate-900">
                                    <div class="flex flex-wrap gap-1 items-center">
                                        @foreach($p->mappings as $m)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-50 text-slate-900 border border-slate-200">
                                                [{{ $m->platform ?: 'All' }}] {{ $m->raw_sku }} ({{ $m->raw_variant ?: '-' }})
                                                <form action="{{ route('mappings.destroy', $m->id) }}" method="POST" class="inline ml-1" onsubmit="return confirm('Hapus alias ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-slate-400 hover:text-red-600 font-sans">&times;</button>
                                                </form>
                                            </span>
                                        @endforeach
                                        <button onclick="openAddMappingModal({{ $p->id }}, '{{ addslashes($p->display_name) }}')" class="text-[10px] text-slate-900 hover:text-black font-bold px-1.5 py-0.5 rounded border border-slate-200 hover:bg-slate-100 transition">
                                            + Alias
                                        </button>
                                    </div>
                                </td>
                                <td class="py-3 px-3.5 text-center">
                                    <form action="{{ route('products.destroy', $p->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus master produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-slate-400 hover:text-red-600 rounded transition" title="Hapus Produk">
                                            ✕
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-900 font-medium">Belum ada master produk terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="pt-4 border-t border-slate-200">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

        <!-- TAB 2: KAMUS VARIASI / SELLER SKU -->
        <div id="ptab-content-filters" class="p-6 hidden space-y-6">
            <div class="bg-blue-50/60 p-4 rounded-2xl border border-blue-100 text-xs text-slate-700">
                💡 <strong>Kamus Variasi & Seller SKU:</strong> Berisi daftar resmi seluruh variasi dan Seller SKU produk Anda. Saat Anda mengupload PDF resi, sistem secara otomatis membaca kolom acuan dan mencocokkannya ke kamus ini serta otomatis menyimpan produk baru ke daftar master produk.
            </div>

            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-sm">Daftar Variasi & Seller SKU</h3>
                        <p class="text-[11px] text-slate-400">Variasi motif, warna, ukuran, atau Seller SKU ekspedisi</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">{{ $variants->count() }} Variasi</span>
                </div>

                <!-- Add Variant Form -->
                <form action="{{ route('variants.store') }}" method="POST" class="flex gap-2 max-w-md">
                    @csrf
                    <input type="text" name="name" required placeholder="+ Tambah Variasi / Seller SKU baru..." class="flex-1 text-xs border border-slate-300 rounded-xl px-3 py-2 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Tambah Variasi
                    </button>
                </form>

                <!-- Variant List Badges -->
                <div class="flex flex-wrap gap-2 max-h-96 overflow-y-auto pr-1">
                    @forelse($variants as $var)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-800 shadow-2xs">
                            <span>{{ $var->name }}</span>
                            <form action="{{ route('variants.destroy', $var->id) }}" method="POST" class="inline ml-1.5" onsubmit="return confirm('Hapus variasi {{ $var->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-red-600 font-bold ml-1">&times;</button>
                            </form>
                        </span>
                    @empty
                        <div class="text-xs text-slate-400 py-4 text-center w-full">Belum ada variasi / seller SKU terdaftar.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Master Produk Manual -->
<div id="modal-add-product" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Tambah Master Produk Baru</h3>
                <p class="text-xs text-slate-400">Pilih Variasi / Seller SKU acuan dari kamus</p>
            </div>
            <button onclick="toggleModal('modal-add-product')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('products.store') }}" method="POST" class="space-y-3.5 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Produk Acuan *</label>
                <input type="text" name="name" required placeholder="Contoh: Aruna Bandana Bundling Paket Hemat" class="w-full border border-slate-300 rounded-xl px-3 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Brand / Merek *</label>
                <select name="brand" required class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                    <option value="Aruna">Aruna</option>
                    <option value="Moveda">Moveda</option>
                    <option value="General">General</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Pilih Variasi / Seller SKU *</label>
                <select name="variant" required class="w-full border border-slate-300 rounded-xl px-2.5 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none bg-white">
                    <option value="">-- Pilih Variasi / Seller SKU --</option>
                    @foreach($variants as $var)
                        <option value="{{ $var->name }}">{{ $var->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Satuan</label>
                    <input type="text" name="unit" value="pcs" class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Opsional" class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="toggleModal('modal-add-product')" class="px-4 py-2 rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-white bg-blue-600 hover:bg-blue-700 font-bold transition shadow-sm">
                    Simpan Master Produk
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import CSV -->
<div id="modal-import-csv" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Import Variasi / Seller SKU dari CSV</h3>
                <p class="text-xs text-slate-400 mt-0.5">Upload file CSV yang berisi daftar Variasi atau Seller SKU</p>
            </div>
            <button onclick="toggleModal('modal-import-csv')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('products.importCsv') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Pilih File CSV (.csv)</label>
                <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" required class="w-full border border-slate-300 rounded-xl px-3 py-2 bg-slate-50 focus:bg-white focus:outline-none">
            </div>

            <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-100 text-blue-900 space-y-1">
                <span class="font-bold">Format File CSV:</span>
                <p class="text-[11px] text-blue-800">
                    File CSV 1 kolom berisi daftar nama Variasi / Seller SKU (misal: Gray Rain, Claw Blue, Arm Soil Black, dll).
                </p>
            </div>

            <div class="pt-2 flex justify-end space-x-2">
                <button type="button" onclick="toggleModal('modal-import-csv')" class="px-4 py-2 rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 font-bold transition shadow-sm">
                    Upload & Import Sekarang 🚀
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Alias Mapping -->
<div id="modal-add-mapping" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Tambah Alias Resi Khusus</h3>
                <p id="mapping-product-title" class="text-xs text-blue-600 font-semibold mt-0.5">-</p>
            </div>
            <button onclick="toggleModal('modal-add-mapping')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('mappings.store') }}" method="POST" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" id="mapping_master_product_id" name="master_product_id">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Platform Marketplace</label>
                <select name="platform" class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                    <option value="">Semua Platform (Shopee & TikTok)</option>
                    <option value="Shopee">Shopee</option>
                    <option value="TikTok Shop">TikTok Shop</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Teks SKU di Resi</label>
                <input type="text" name="raw_sku" placeholder="Contoh: HeadbandSe aweed" class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Teks Variasi di Resi</label>
                <input type="text" name="raw_variant" placeholder="Contoh: Hdbn Gray Rain" class="w-full border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="toggleModal('modal-add-mapping')" class="px-4 py-2 rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-white bg-blue-600 hover:bg-blue-700 font-bold transition shadow-sm">
                    Simpan Alias
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function toggleModal(id) {
        const m = document.getElementById(id);
        m.classList.toggle('hidden');
    }

    function openAddMappingModal(productId, productName) {
        document.getElementById('mapping_master_product_id').value = productId;
        document.getElementById('mapping-product-title').innerText = productName;
        toggleModal('modal-add-mapping');
    }

    function switchProductTab(tab) {
        document.querySelectorAll('.ptab-btn').forEach(btn => {
            btn.classList.remove('border-blue-600', 'text-blue-700');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        document.getElementById('ptab-btn-' + tab).classList.add('border-blue-600', 'text-blue-700');
        document.getElementById('ptab-btn-' + tab).classList.remove('border-transparent', 'text-slate-500');

        document.getElementById('ptab-content-list').classList.add('hidden');
        document.getElementById('ptab-content-filters').classList.add('hidden');

        document.getElementById('ptab-content-' + tab).classList.remove('hidden');
    }
</script>
@endpush
@endsection
