<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cek Resi & Rekap Stok Keluar') - Aruna & Moveda</title>
    
    <!-- Favicon using Logo Hitam.png -->
    <link rel="icon" type="image/png" href="{{ asset('Logo Hitam.png') }}">
    <link rel="shortcut icon" href="{{ asset('Logo Hitam.png') }}">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js Plugins & Core for interactive dropdowns and mobile drawer -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .checked-row {
            background-color: #f0fdf4 !important;
            opacity: 0.7;
        }
        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 4px;
        }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased" 
      x-data="{ 
          sidebarCollapsed: false,
          mobileSidebarOpen: false, 
          scanResiOpen: {{ request()->routeIs('resi.*') ? 'true' : 'false' }}, 
          toolsOpen: {{ request()->routeIs('products.*') || request()->routeIs('pdf.merge.*') ? 'true' : 'false' }} 
      }">

    <div class="min-h-full flex flex-row">
        <!-- ================= BACKDROP FOR MOBILE SIDEBAR ================= -->
        <div x-cloak 
             x-show="mobileSidebarOpen" 
             @click="mobileSidebarOpen = false" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden">
        </div>

        <!-- ================= MAIN SIDEBAR (DESKTOP & MOBILE DRAWER) ================= -->
        <aside :class="{
                   'translate-x-0': mobileSidebarOpen, 
                   '-translate-x-full': !mobileSidebarOpen && !sidebarCollapsed,
                   'lg:translate-x-0 lg:w-64': !sidebarCollapsed,
                   'lg:-translate-x-full lg:w-0 lg:overflow-hidden lg:border-none': sidebarCollapsed
               }"
               class="fixed lg:sticky top-0 left-0 z-50 h-screen bg-white border-r border-slate-200/90 flex flex-col justify-between transition-all duration-300 ease-in-out shadow-lg lg:shadow-none flex-shrink-0">
            
            <!-- Top: Brand & Menu Links -->
            <div class="flex-1 flex flex-col min-h-0 overflow-y-auto sidebar-scroll">
                <!-- Brand / Logo Header -->
                <div class="h-20 px-6 border-b border-slate-100 flex items-center justify-between">
                    <a href="{{ route('resi.index') }}" class="flex items-center gap-3 group">
                        <!-- Official Brand Logo (Logo Hitam.png) -->
                        <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-200/90 flex items-center justify-center p-1 shadow-xs flex-shrink-0 group-hover:scale-105 transition-transform duration-200">
                            <img src="{{ asset('Logo Hitam.png') }}" alt="Logo Aruna" class="h-full w-full object-contain">
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-extrabold text-sm text-slate-900 tracking-tight leading-tight">StockFlow</span>
                            <span class="text-[11px] text-slate-400 font-medium leading-tight mt-0.5 truncate">Aruna &amp; Moveda</span>
                        </div>
                    </a>

                    <!-- Close button on mobile -->
                    <button @click="mobileSidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Navigation List -->
                <nav class="px-4 py-5 space-y-2">
                    
                    <!-- ================= 1. DROPDOWN SCAN RESI ================= -->
                    <div class="space-y-1">
                        <!-- Parent Toggle Button -->
                        <button type="button" 
                                @click="scanResiOpen = !scanResiOpen"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition duration-150 {{ request()->routeIs('resi.*') ? 'text-blue-700 bg-blue-50/70 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ request()->routeIs('resi.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM13.5 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z" />
                                </svg>
                                <span>Scan Resi</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" 
                                 :class="scanResiOpen ? 'rotate-180' : ''" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Children Menu Links with vertical connector line (Reference: Gambar 1) -->
                        <div x-show="scanResiOpen" 
                             x-collapse
                             class="ml-6 pl-3 border-l-2 border-slate-200/80 my-1 space-y-1">
                            <!-- Resi Gabungan (Semua Toko) -->
                            <a href="{{ route('resi.index') }}" 
                               class="flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition duration-150 {{ request()->routeIs('resi.index') && !request()->routeIs('resi.shopee') && !request()->routeIs('resi.tiktok') ? 'bg-blue-50 text-blue-600 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                <span>Resi Gabungan</span>
                                @if(request()->routeIs('resi.index') && !request()->routeIs('resi.shopee') && !request()->routeIs('resi.tiktok'))
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                @endif
                            </a>

                            <!-- Resi Shopee -->
                            <a href="{{ route('resi.shopee') }}" 
                               class="flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition duration-150 {{ request()->routeIs('resi.shopee') || (isset($batchPlatform) && $batchPlatform === 'Shopee') ? 'bg-blue-50 text-blue-600 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                <span>Resi Shopee</span>
                                @if(request()->routeIs('resi.shopee') || (isset($batchPlatform) && $batchPlatform === 'Shopee'))
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                @endif
                            </a>

                            <!-- Resi TikTok / Tokopedia -->
                            <a href="{{ route('resi.tiktok') }}" 
                               class="flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition duration-150 {{ request()->routeIs('resi.tiktok') || (isset($batchPlatform) && $batchPlatform === 'TikTok Shop') ? 'bg-blue-50 text-blue-600 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                <span>Resi TikTok / Tokopedia</span>
                                @if(request()->routeIs('resi.tiktok') || (isset($batchPlatform) && $batchPlatform === 'TikTok Shop'))
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                @endif
                            </a>
                        </div>
                    </div>

                    <!-- ================= 2. DROPDOWN TOOLS ================= -->
                    <div class="space-y-1 pt-1">
                        <!-- Parent Toggle Button -->
                        <button type="button" 
                                @click="toolsOpen = !toolsOpen"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition duration-150 {{ request()->routeIs('products.*') || request()->routeIs('pdf.merge.*') ? 'text-indigo-700 bg-indigo-50/70 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ request()->routeIs('products.*') || request()->routeIs('pdf.merge.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                </svg>
                                <span>Tools</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" 
                                 :class="toolsOpen ? 'rotate-180' : ''" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Children Menu Links with vertical connector line (Reference: Gambar 1) -->
                        <div x-show="toolsOpen" 
                             x-collapse
                             class="ml-6 pl-3 border-l-2 border-slate-200/80 my-1 space-y-1">
                            <!-- Master Data -->
                            <a href="{{ route('products.index') }}" 
                               class="flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition duration-150 {{ request()->routeIs('products.*') ? 'bg-blue-50 text-blue-600 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                <span>Master Data</span>
                                @if(request()->routeIs('products.*'))
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                @endif
                            </a>

                            <!-- Penggabungan PDF -->
                            <a href="{{ route('pdf.merge.index') }}" 
                               class="flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl transition duration-150 {{ request()->routeIs('pdf.merge.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                                <span>Penggabungan PDF</span>
                                @if(request()->routeIs('pdf.merge.*'))
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                @endif
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- ================= MAIN CONTENT WRAPPER ================= -->
        <div class="flex-1 flex flex-col min-w-0 min-h-screen">
            
            <!-- Topbar / Header with Breadcrumb (Clean - Without Localhost Aktif & Notification) -->
            <header class="h-16 bg-white border-b border-slate-200/80 sticky top-0 z-30 px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-2xs">
                
                <!-- Left: Sidebar Toggle + Breadcrumb Trail -->
                <div class="flex items-center gap-3">
                    <!-- Desktop Sidebar Toggle Button (Reference: Gambar 1) -->
                    <button type="button" 
                            @click="sidebarCollapsed = !sidebarCollapsed"
                            class="hidden lg:flex w-8 h-8 rounded-lg border border-slate-200 bg-white items-center justify-center text-slate-700 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50/50 shadow-2xs transition focus:outline-hidden"
                            title="Buka / Tutup Sidebar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M9 4v16" />
                            <path stroke-linecap="round" stroke-linejoin="round" :d="sidebarCollapsed ? 'm13 9 3 3-3 3' : 'm15 9-3 3 3 3'" />
                        </svg>
                    </button>

                    <!-- Mobile Sidebar Toggle Button -->
                    <button type="button" 
                            @click="mobileSidebarOpen = true"
                            class="lg:hidden w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-700 hover:text-blue-600 hover:bg-slate-50 shadow-2xs transition focus:outline-hidden"
                            title="Buka Sidebar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M9 4v16" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m13 9 3 3-3 3" />
                        </svg>
                    </button>

                    <!-- Breadcrumb Text Trail -->
                    <nav class="flex items-center text-xs md:text-sm font-medium">
                        @if(request()->routeIs('products.*'))
                            <span class="text-slate-500">Tools</span>
                            <span class="text-slate-400 mx-2">&gt;</span>
                            <span class="text-slate-900 font-semibold">Master Data</span>
                        @elseif(request()->routeIs('pdf.merge.*'))
                            <span class="text-slate-500">Tools</span>
                            <span class="text-slate-400 mx-2">&gt;</span>
                            <span class="text-slate-900 font-semibold">Penggabungan PDF</span>
                        @elseif(request()->routeIs('resi.shopee'))
                            <span class="text-slate-500">Scan Resi</span>
                            <span class="text-slate-400 mx-2">&gt;</span>
                            <span class="text-slate-900 font-semibold">Resi Shopee</span>
                        @elseif(request()->routeIs('resi.tiktok'))
                            <span class="text-slate-500">Scan Resi</span>
                            <span class="text-slate-400 mx-2">&gt;</span>
                            <span class="text-slate-900 font-semibold">Resi TikTok / Tokopedia</span>
                        @elseif(request()->routeIs('resi.batch'))
                            <span class="text-slate-500">Scan Resi</span>
                            <span class="text-slate-400 mx-2">&gt;</span>
                            <span class="text-slate-900 font-semibold">Hasil Rekapitulasi Resi</span>
                        @else
                            <span class="text-slate-500">Scan Resi</span>
                            <span class="text-slate-400 mx-2">&gt;</span>
                            <span class="text-slate-900 font-semibold">Resi Gabungan</span>
                        @endif
                    </nav>
                </div>

                <!-- Right: Clean Header (No Localhost Aktif & No Notification Bell) -->
                <div></div>
            </header>

            <!-- Main Page Content Body -->
            <main class="flex-1 p-5 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-transition.duration.200ms class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start justify-between space-x-3 shadow-xs">
                        <div class="flex items-start space-x-3 flex-1 min-w-0">
                            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="flex-1 text-sm font-medium">{{ session('success') }}</div>
                        </div>
                        <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-900 p-1 -mr-1 -mt-1 rounded-lg hover:bg-emerald-100 transition flex-shrink-0" title="Tutup pemberitahuan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" x-transition.duration.200ms class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 flex items-start justify-between space-x-3 shadow-xs">
                        <div class="flex items-start space-x-3 flex-1 min-w-0">
                            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="flex-1 text-sm font-medium">{{ session('error') }}</div>
                        </div>
                        <button type="button" @click="show = false" class="text-red-600 hover:text-red-900 p-1 -mr-1 -mt-1 rounded-lg hover:bg-red-100 transition flex-shrink-0" title="Tutup pemberitahuan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div x-data="{ show: true }" x-show="show" x-transition.duration.200ms class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start justify-between space-x-3 shadow-xs">
                        <div class="flex items-start space-x-3 flex-1 min-w-0">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div class="flex-1 text-sm font-medium">{{ session('warning') }}</div>
                        </div>
                        <button type="button" @click="show = false" class="text-amber-600 hover:text-amber-900 p-1 -mr-1 -mt-1 rounded-lg hover:bg-amber-100 transition flex-shrink-0" title="Tutup pemberitahuan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div x-data="{ show: true }" x-show="show" x-transition.duration.200ms class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start justify-between space-x-3 shadow-xs">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm mb-1">Perhatian:</div>
                            <ul class="list-disc list-inside text-xs space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" @click="show = false" class="text-amber-600 hover:text-amber-900 p-1 -mr-1 -mt-1 rounded-lg hover:bg-amber-100 transition flex-shrink-0" title="Tutup pemberitahuan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endif

                <!-- Yielded Content -->
                @yield('content')
            </main>

            <!-- Minimalist Footer -->
            <footer class="bg-white border-t border-slate-200/80 py-5 px-6 text-center text-xs text-slate-400 mt-auto">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p>StockFlow &bull; Aruna Sportswear Indonesia &amp; Moveda Activewear Indonesia</p>
                    <p class="text-slate-400 font-mono text-[11px]">v2.4 &bull; PyPDF Engine &amp; SKU StoDo Mapping</p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Toast Notification helper -->
    <div id="copy-toast" class="fixed bottom-6 right-6 bg-slate-900 text-white px-4 py-2.5 rounded-xl shadow-xl text-xs font-semibold transform translate-y-20 opacity-0 transition-all duration-300 z-50 pointer-events-none flex items-center gap-2">
        <span>📋</span>
        <span>Tersalin ke clipboard!</span>
    </div>

    <script>
        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.getElementById('copy-toast');
                toast.classList.remove('translate-y-20', 'opacity-0');
                setTimeout(() => {
                    toast.classList.add('translate-y-20', 'opacity-0');
                }, 1800);
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
