@extends('layouts.app')

@section('title', 'Penggabungan File PDF - StockFlow')

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 mb-2">
                <img src="{{ asset('icons/pdf.svg') }}" class="w-3.5 h-3.5 object-contain" alt="PDF">
                <span>Fitur Merge Dokumen PDF</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Penggabungan File PDF Resi</h1>
            <p class="text-slate-500 text-sm mt-1">
                Gabungkan beberapa file PDF resi pengiriman menjadi satu dokumen terpadu. Urutan awal mengikuti urutan upload, dan Anda dapat mengatur ulang posisi file kapan saja secara fleksibel.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('resi.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm">
                ← Kembali ke Rekap Resi
            </a>
        </div>
    </div>

    <!-- Petunjuk Singkat -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-5 border border-blue-200/60 shadow-sm flex items-start space-x-4">
        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-lg flex-shrink-0 shadow-sm">
            💡
        </div>
        <div class="text-sm text-slate-700 space-y-1">
            <div class="font-bold text-slate-900">Cara Kerja Penggabungan File:</div>
            <ol class="list-decimal list-inside space-y-1 text-slate-600">
                <li><strong class="text-slate-800">Upload File:</strong> Pilih atau seret 2 atau lebih file PDF ke area upload di bawah. File akan disusun sesuai urutan saat Anda memilihnya.</li>
                <li><strong class="text-slate-800">Atur Posisi (Opsional):</strong> Gunakan tombol <span class="px-1.5 py-0.5 bg-white border border-slate-300 rounded text-xs font-mono font-bold">⬆️ Naik</span>, <span class="px-1.5 py-0.5 bg-white border border-slate-300 rounded text-xs font-mono font-bold">⬇️ Turun</span>, atau <strong class="text-indigo-700">ketik langsung nomor urutan</strong> pada kolom angka untuk menyusun urutan file secara instan.</li>
                <li><strong class="text-slate-800">Pilih Aksi:</strong> Download file gabungan untuk diarsipkan, atau langsung jalankan <em>"Gabung & Langsung Rekap"</em> untuk langsung menghitung stok keluar!</li>
            </ol>
        </div>
    </div>

    <!-- Main Merge Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <form id="mergeForm" action="{{ route('pdf.merge.action') }}" method="POST" enctype="multipart/form-data" class="p-6 md:p-8 space-y-6">
            @csrf
            <input type="hidden" name="action_type" id="action_type" value="download">
            <input type="hidden" name="file_order" id="file_order" value="">

            <!-- Hidden File Input that receives the combined DataTransfer -->
            <input type="file" id="pdf_files" name="pdf_files[]" multiple accept="application/pdf" class="hidden">
            <!-- Secondary input for adding more files iteratively -->
            <input type="file" id="pdf_picker_additional" multiple accept="application/pdf" class="hidden">

            <!-- Drag & Drop Zone -->
            <div id="dropzone" 
                 class="relative border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl p-8 text-center bg-slate-50/60 hover:bg-blue-50/40 transition cursor-pointer group">
                <div class="flex flex-col items-center justify-center space-y-3">
                    <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 flex items-center justify-center p-3.5 shadow-sm group-hover:scale-105 group-hover:border-blue-300 transition duration-200">
                        <img src="{{ asset('icons/pdf.svg') }}" class="w-full h-full object-contain" alt="PDF">
                    </div>
                    <div>
                        <div class="font-bold text-base text-slate-800 group-hover:text-blue-600 transition">
                            Klik di sini untuk memilih file PDF atau drag & drop ke area ini
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Pilih minimal 2 file PDF. Anda dapat memilih beberapa file sekaligus atau menambahkannya bertahap.
                        </div>
                    </div>
                    <div class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-600 shadow-xs">
                        📁 Format Didukung: .PDF (Maks. 100MB / file)
                    </div>
                </div>
            </div>

            <!-- File List Section (Dynamic) -->
            <div id="fileListWrapper" class="space-y-4 hidden">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-3">
                    <div class="flex items-center space-x-2">
                        <h3 class="font-bold text-slate-800 text-base">Daftar File & Urutan Penggabungan</h3>
                        <span id="badgeCount" class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                            0 File
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="btnReverseOrder" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition shadow-2xs" title="Balikkan urutan file (atas ke bawah / sebaliknya)">
                            🔄 Balikkan Urutan
                        </button>
                        <button type="button" id="btnSortAlpha" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg transition shadow-2xs" title="Urutkan nama file dari A ke Z">
                            🔤 Urut A-Z
                        </button>
                        <button type="button" id="btnAddMore" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition shadow-2xs">
                            + Tambah File Lagi
                        </button>
                        <button type="button" id="btnClearAll" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition shadow-2xs">
                            Hapus Semua
                        </button>
                    </div>
                </div>

                <p class="text-xs text-slate-500 italic">
                    💡 File di bawah akan digabung berurutan mulai dari posisi #1 ke bawah. Anda dapat <strong>ketik nomor urutan lalu Enter / tekan ✓</strong>, klik <strong>⬆️ / ⬇️</strong>, <strong>drag & drop</strong> kartu file, atau klik <strong>🔄 Balikkan Urutan</strong> jika file terbalik saat dipilih dari Windows.
                </p>

                <!-- List Container -->
                <div id="fileContainer" class="space-y-2.5">
                    <!-- Populated via JavaScript -->
                </div>
            </div>

            <!-- Output Filename Setting -->
            <div class="border-t border-slate-200 pt-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <label for="output_name" class="block text-sm font-bold text-slate-800">Nama File Hasil Gabungan</label>
                        <span class="text-xs text-slate-500">Opsional, otomatis berekstensi .pdf</span>
                    </div>
                    <div class="md:col-span-2">
                        <div class="relative">
                            <input type="text" 
                                   name="output_name" 
                                   id="output_name" 
                                   value="Resi_Gabungan_{{ date('Ymd_His') }}"
                                   placeholder="Contoh: Resi_Gabungan_Shopee_JNT_28Okt"
                                   class="w-full px-4 py-2.5 text-sm font-mono border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-slate-50 focus:bg-white transition">
                            <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400 font-mono">.pdf</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Standardize automatically behind the scenes (619x873 px standard resi, no crop) -->
            <input type="hidden" name="standardize_size" value="standard_a6">
            <input type="hidden" name="auto_rotate" value="1">

            <!-- Submit Buttons -->
            <div class="border-t border-slate-200 pt-6 flex flex-col sm:flex-row items-center justify-end gap-3">
                <button type="button" 
                        id="btnSubmitDownload"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-sm font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 border border-emerald-300 shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="mr-2 text-base">📥</span> Download PDF Gabungan Saja
                </button>

                <button type="button" 
                        id="btnSubmitRekap"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-md hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="mr-2 text-base">🚀</span> Gabung & Langsung Rekap Resi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for Interactive Dynamic File List & Reordering -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // State: array of File objects currently held in order
        let fileQueue = [];

        const dropzone = document.getElementById('dropzone');
        const pdfFilesInput = document.getElementById('pdf_files');
        const pdfAdditionalInput = document.getElementById('pdf_picker_additional');
        const fileListWrapper = document.getElementById('fileListWrapper');
        const fileContainer = document.getElementById('fileContainer');
        const badgeCount = document.getElementById('badgeCount');
        const btnAddMore = document.getElementById('btnAddMore');
        const btnClearAll = document.getElementById('btnClearAll');
        const btnSubmitDownload = document.getElementById('btnSubmitDownload');
        const btnSubmitRekap = document.getElementById('btnSubmitRekap');
        const mergeForm = document.getElementById('mergeForm');
        const actionTypeInput = document.getElementById('action_type');
        const fileOrderInput = document.getElementById('file_order');


        // Format bytes to human readable
        function formatBytes(bytes, decimals = 1) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        // Trigger picker when clicking dropzone
        dropzone.addEventListener('click', function () {
            pdfAdditionalInput.click();
        });

        btnAddMore.addEventListener('click', function () {
            pdfAdditionalInput.click();
        });

        // Drag & Drop visual feedbacks
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('border-blue-600', 'bg-blue-50/80');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('border-blue-600', 'bg-blue-50/80');
            }, false);
        });

        // Handle file drop
        dropzone.addEventListener('drop', function (e) {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                handleNewFiles(dt.files);
            }
        });

        // Handle file input selection
        pdfAdditionalInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                handleNewFiles(this.files);
                this.value = ''; // Reset so the same file can be picked again if desired
            }
        });

        // Append new files into the ordered queue
        function handleNewFiles(fileList) {
            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                if (file.name.toLowerCase().endsWith('.pdf') || file.type === 'application/pdf') {
                    fileQueue.push(file);
                } else {
                    alert(`File "${file.name}" dilewati karena bukan file format PDF.`);
                }
            }
            renderFileList();
        }

        // Render the file list UI
        function renderFileList() {
            fileContainer.innerHTML = '';

            if (fileQueue.length === 0) {
                fileListWrapper.classList.add('hidden');
                btnSubmitDownload.disabled = true;
                btnSubmitRekap.disabled = true;
                return;
            }

            fileListWrapper.classList.remove('hidden');
            badgeCount.textContent = `${fileQueue.length} File`;

            // Enable submit if at least 2 files
            const canSubmit = fileQueue.length >= 2;
            btnSubmitDownload.disabled = !canSubmit;
            btnSubmitRekap.disabled = !canSubmit;

            let totalBytes = 0;

            fileQueue.forEach((file, index) => {
                totalBytes += file.size;

                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3.5 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/90 rounded-xl transition duration-150 draggable-item';
                item.setAttribute('data-index', index);

                const isFirst = index === 0;
                const isLast = index === fileQueue.length - 1;

                item.innerHTML = `
                    <div class="flex items-center space-x-3 min-w-0 flex-1 mr-3">
                        <span class="drag-handle text-slate-400 hover:text-slate-700 cursor-grab active:cursor-grabbing text-xl select-none px-1.5 py-1 rounded hover:bg-slate-200/60 transition" draggable="true" title="Klik dan geser (drag) untuk ubah posisi urutan">⠿</span>
                        <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-blue-600 text-white font-extrabold text-xs flex items-center justify-center shadow-xs">
                            #${index + 1}
                        </span>
                        <div class="w-8 h-8 rounded-lg bg-rose-50 border border-rose-200 flex items-center justify-center p-1.5 flex-shrink-0">
                            <img src="{{ asset('icons/pdf.svg') }}" class="w-full h-full object-contain" alt="PDF">
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-slate-800 truncate" title="${file.name}">
                                ${file.name}
                            </div>
                            <div class="text-xs text-slate-500 flex items-center space-x-2 mt-0.5">
                                <span>${formatBytes(file.size)}</span>
                                <span>•</span>
                                <span class="text-emerald-700 font-medium">Urutan ke-${index + 1}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <div class="flex items-center bg-white border border-slate-300 rounded-lg px-2 py-1 shadow-xs hover:border-blue-400 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition" title="Ketik nomor urutan baru lalu tekan Enter atau klik ✓">
                            <span class="text-[11px] text-slate-500 font-bold mr-1">No:</span>
                            <input type="number" 
                                   min="1" 
                                   max="${fileQueue.length}" 
                                   value="${index + 1}" 
                                   data-index="${index}" 
                                   draggable="false"
                                   class="input-order-num w-12 text-center text-xs font-black text-blue-700 bg-slate-50 focus:bg-white focus:outline-none rounded py-0.5 border border-slate-200">
                            <button type="button" class="btn-apply-order ml-1.5 px-2 py-0.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded transition shadow-2xs cursor-pointer" title="Terapkan nomor urutan">✓</button>
                        </div>
                        <button type="button" 
                                class="btn-move-up p-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer" 
                                title="Pindahkan ke atas"
                                ${isFirst ? 'disabled' : ''}
                                data-index="${index}">
                            ⬆️
                        </button>
                        <button type="button" 
                                class="btn-move-down p-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer" 
                                title="Pindahkan ke bawah"
                                ${isLast ? 'disabled' : ''}
                                data-index="${index}">
                            ⬇️
                        </button>
                        <button type="button" 
                                class="btn-remove p-1.5 rounded-lg border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-600 transition cursor-pointer" 
                                title="Hapus file ini"
                                data-index="${index}">
                            ✕
                        </button>
                    </div>
                `;

                fileContainer.appendChild(item);
            });

            // Summary row
            const summaryRow = document.createElement('div');
            summaryRow.className = 'text-xs text-right text-slate-500 font-medium pt-1';
            summaryRow.textContent = `Total ukuran akumulasi: ${formatBytes(totalBytes)}`;
            fileContainer.appendChild(summaryRow);

            attachItemEventListeners();
        }

        // Attach listeners for Up, Down, Remove, Drag&Drop, and Direct Number Ordering
        function attachItemEventListeners() {
            function applyOrderChange(inputEl) {
                const currentIdx = parseInt(inputEl.getAttribute('data-index'), 10);
                let targetPos = parseInt(inputEl.value, 10);
                if (isNaN(targetPos)) {
                    inputEl.value = currentIdx + 1;
                    return;
                }
                targetPos = Math.max(1, Math.min(fileQueue.length, targetPos));
                const targetIdx = targetPos - 1;
                if (targetIdx !== currentIdx) {
                    const [movedItem] = fileQueue.splice(currentIdx, 1);
                    fileQueue.splice(targetIdx, 0, movedItem);
                    renderFileList();
                } else {
                    inputEl.value = currentIdx + 1;
                }
            }

            document.querySelectorAll('.input-order-num').forEach(input => {
                input.addEventListener('focus', function () {
                    this.select();
                });

                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        e.stopPropagation();
                        applyOrderChange(this);
                    }
                });

                const applyBtn = input.parentElement.querySelector('.btn-apply-order');
                if (applyBtn) {
                    applyBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        applyOrderChange(input);
                    });
                }
            });

            document.querySelectorAll('.btn-move-up').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const idx = parseInt(this.getAttribute('data-index'), 10);
                    if (idx > 0) {
                        const temp = fileQueue[idx];
                        fileQueue[idx] = fileQueue[idx - 1];
                        fileQueue[idx - 1] = temp;
                        renderFileList();
                    }
                });
            });

            document.querySelectorAll('.btn-move-down').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const idx = parseInt(this.getAttribute('data-index'), 10);
                    if (idx < fileQueue.length - 1) {
                        const temp = fileQueue[idx];
                        fileQueue[idx] = fileQueue[idx + 1];
                        fileQueue[idx + 1] = temp;
                        renderFileList();
                    }
                });
            });

            document.querySelectorAll('.btn-remove').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const idx = parseInt(this.getAttribute('data-index'), 10);
                    fileQueue.splice(idx, 1);
                    renderFileList();
                });
            });

            // HTML5 Drag & Drop reordering between file cards using drag-handle
            let draggedIndex = null;

            document.querySelectorAll('.drag-handle').forEach(handle => {
                handle.addEventListener('dragstart', function (e) {
                    const card = this.closest('.draggable-item');
                    draggedIndex = parseInt(card.getAttribute('data-index'), 10);
                    e.dataTransfer.effectAllowed = 'move';
                    card.classList.add('opacity-40');
                });

                handle.addEventListener('dragend', function () {
                    const card = this.closest('.draggable-item');
                    if (card) card.classList.remove('opacity-40');
                    document.querySelectorAll('.draggable-item').forEach(el => el.classList.remove('border-blue-500', 'bg-blue-50/70'));
                });
            });

            document.querySelectorAll('.draggable-item').forEach(item => {
                item.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    this.classList.add('border-blue-500', 'bg-blue-50/70');
                });

                item.addEventListener('dragleave', function () {
                    this.classList.remove('border-blue-500', 'bg-blue-50/70');
                });

                item.addEventListener('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.remove('border-blue-500', 'bg-blue-50/70');
                    const targetIdx = parseInt(this.getAttribute('data-index'), 10);
                    if (draggedIndex !== null && targetIdx !== draggedIndex) {
                        const [movedItem] = fileQueue.splice(draggedIndex, 1);
                        fileQueue.splice(targetIdx, 0, movedItem);
                        draggedIndex = null;
                        renderFileList();
                    }
                });
            });
        }

        // Reverse Order Button (Solves Windows Explorer reverse multiselect in 1 click)
        const btnReverseOrder = document.getElementById('btnReverseOrder');
        if (btnReverseOrder) {
            btnReverseOrder.addEventListener('click', function () {
                fileQueue.reverse();
                renderFileList();
            });
        }

        // Alphabetical Sort Button
        const btnSortAlpha = document.getElementById('btnSortAlpha');
        if (btnSortAlpha) {
            btnSortAlpha.addEventListener('click', function () {
                fileQueue.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' }));
                renderFileList();
            });
        }

        // Clear All
        btnClearAll.addEventListener('click', function () {
            if (confirm('Yakin ingin mengosongkan seluruh antrean file?')) {
                fileQueue = [];
                renderFileList();
            }
        });

        // Prevent accidental form submit when pressing Enter inside number/text inputs
        mergeForm.addEventListener('submit', function (e) {
            e.preventDefault();
        });

        // Form Submit Handler
        function submitWithAction(actionType) {
            if (fileQueue.length < 2) {
                alert('Silakan pilih minimal 2 file PDF untuk digabungkan!');
                return;
            }

            // Sync the ordered fileQueue into pdfFilesInput via DataTransfer
            const dt = new DataTransfer();
            const orderIndices = [];
            for (let i = 0; i < fileQueue.length; i++) {
                dt.items.add(fileQueue[i]);
                orderIndices.push(i);
            }
            pdfFilesInput.files = dt.files;
            actionTypeInput.value = actionType;
            fileOrderInput.value = orderIndices.join(',');

            // Provide visual feedback
            if (actionType === 'download') {
                const origDownloadHtml = '<span class="mr-2 text-base">📥</span> Download PDF Gabungan Saja';
                btnSubmitDownload.innerHTML = '<span class="animate-spin mr-2">⏳</span> Mengunduh PDF...';
                btnSubmitDownload.disabled = true;
                // Since download response does not unload the browser page, restore button after 3.5s
                setTimeout(() => {
                    btnSubmitDownload.innerHTML = origDownloadHtml;
                    btnSubmitDownload.disabled = false;
                }, 3500);
            } else {
                btnSubmitRekap.innerHTML = '<span class="animate-spin mr-2">⏳</span> Menggabung & Merekap...';
                btnSubmitDownload.disabled = true;
                btnSubmitRekap.disabled = true;
            }

            HTMLFormElement.prototype.submit.call(mergeForm);
        }

        btnSubmitDownload.addEventListener('click', function () {
            submitWithAction('download');
        });

        btnSubmitRekap.addEventListener('click', function () {
            submitWithAction('rekap');
        });

        // Initialize empty state
        renderFileList();
    });
</script>
@endsection
