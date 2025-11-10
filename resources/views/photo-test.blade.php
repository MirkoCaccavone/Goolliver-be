<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sistema Upload Foto - Goolliver</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background-color: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #007bff;
            background-color: #f0f8ff;
        }
        .upload-area.dragover {
            border-color: #28a745;
            background-color: #f0fff4;
        }
        .preview-image {
            max-width: 300px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .photo-gallery img {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }
        .status-badge {
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">🔬 Test Sistema Upload Foto</h1>
        
        <!-- Storage Info -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>📁 Informazioni Storage</h5>
                    </div>
                    <div class="card-body" id="storageInfo">
                        <div class="d-flex justify-content-center">
                            <div class="spinner-border" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="row mb-5">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5>📸 Upload Nuova Foto</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="contest_id" class="form-label">Contest</label>
                                    <select class="form-select" id="contest_id" name="contest_id" required>
                                        <option value="">Seleziona Contest</option>
                                        @foreach($contests as $contest)
                                            <option value="{{ $contest->id }}">{{ $contest->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="user_id" class="form-label">Utente Test</label>
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value="">Seleziona Utente</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label">Titolo Foto</label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       placeholder="Inserisci un titolo per la foto">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descrizione (opzionale)</label>
                                <textarea class="form-control" id="description" name="description" rows="2" 
                                          placeholder="Descrivi la foto..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Foto</label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                    <p class="mb-1"><strong>Clicca per selezionare</strong> o trascina qui la foto</p>
                                    <p class="text-muted small mb-0">JPG, PNG, WEBP - Max 10MB</p>
                                    <input type="file" id="photo" name="photo" accept="image/*" style="display: none;" required>
                                </div>
                                <div id="imagePreview" class="mt-3" style="display: none;">
                                    <img id="previewImg" class="preview-image" src="" alt="Preview">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeImage">
                                            Rimuovi Immagine
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                <span id="submitText">🚀 Carica Foto</span>
                                <span id="submitSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Results -->
        <div class="row mb-4" id="uploadResults" style="display: none;">
            <div class="col-12">
                <div class="alert" id="uploadAlert">
                    <div id="uploadMessage"></div>
                </div>
            </div>
        </div>

        <!-- Photo Gallery -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>🖼️ Foto Caricate</h5>
                        <button class="btn btn-sm btn-outline-primary" id="refreshGallery">
                            🔄 Aggiorna
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="photoGallery" class="row photo-gallery">
                            <div class="col-12 text-center">
                                <div class="spinner-border" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Elementi DOM
        const uploadForm = document.getElementById('uploadForm');
        const uploadArea = document.getElementById('uploadArea');
        const photoInput = document.getElementById('photo');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const removeImageBtn = document.getElementById('removeImage');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');

        // Upload area drag & drop
        uploadArea.addEventListener('click', () => photoInput.click());
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                photoInput.files = files;
                showImagePreview(files[0]);
            }
        });

        // File input change
        photoInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                showImagePreview(e.target.files[0]);
            }
        });

        // Remove image
        removeImageBtn.addEventListener('click', () => {
            photoInput.value = '';
            imagePreview.style.display = 'none';
        });

        // Show image preview
        function showImagePreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        // Form submit
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(uploadForm);
            
            // UI loading state
            submitBtn.disabled = true;
            submitText.textContent = 'Caricamento...';
            submitSpinner.classList.remove('d-none');

            try {
                const response = await fetch('/api/test/photos/upload', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    showAlert('success', '✅ ' + result.message, result.entry);
                    uploadForm.reset();
                    imagePreview.style.display = 'none';
                    loadGallery();
                } else {
                    showAlert('danger', '❌ Errore: ' + result.error, result.details);
                }
            } catch (error) {
                showAlert('danger', '❌ Errore di connessione: ' + error.message);
            } finally {
                // Reset UI
                submitBtn.disabled = false;
                submitText.textContent = '🚀 Carica Foto';
                submitSpinner.classList.add('d-none');
            }
        });

        // Show alert
        function showAlert(type, message, details = null) {
            const uploadResults = document.getElementById('uploadResults');
            const uploadAlert = document.getElementById('uploadAlert');
            const uploadMessage = document.getElementById('uploadMessage');

            uploadAlert.className = `alert alert-${type}`;
            let html = `<div>${message}</div>`;
            
            if (details) {
                html += `<pre class="mt-2 mb-0"><small>${JSON.stringify(details, null, 2)}</small></pre>`;
            }
            
            uploadMessage.innerHTML = html;
            uploadResults.style.display = 'block';

            // Auto hide after 10 seconds for success
            if (type === 'success') {
                setTimeout(() => {
                    uploadResults.style.display = 'none';
                }, 10000);
            }
        }

        // Load storage info
        async function loadStorageInfo() {
            try {
                const response = await fetch('/api/test/photos/storage-info');
                const data = await response.json();
                
                let html = '<div class="row">';
                
                // Directories
                html += '<div class="col-md-4"><h6>📁 Directory</h6><ul class="list-unstyled small">';
                Object.entries(data.directories_exist || {}).forEach(([key, exists]) => {
                    const icon = exists ? '✅' : '❌';
                    html += `<li>${icon} ${key}</li>`;
                });
                html += '</ul></div>';
                
                // Permissions
                html += '<div class="col-md-4"><h6>🔒 Permessi</h6><ul class="list-unstyled small">';
                Object.entries(data.permissions || {}).forEach(([key, writable]) => {
                    const icon = writable ? '✅' : '❌';
                    html += `<li>${icon} ${key} writable</li>`;
                });
                html += '</ul></div>';
                
                // Stats
                html += '<div class="col-md-4"><h6>📊 Statistiche</h6><ul class="list-unstyled small">';
                html += `<li>📸 Foto: ${data.photo_count || 0}</li>`;
                html += `<li>💾 Spazio: ${data.total_size || '0 MB'}</li>`;
                html += '</ul></div>';
                
                html += '</div>';
                
                document.getElementById('storageInfo').innerHTML = html;
            } catch (error) {
                document.getElementById('storageInfo').innerHTML = 
                    `<div class="text-danger">❌ Errore nel caricamento info storage: ${error.message}</div>`;
            }
        }

        // Load gallery
        async function loadGallery() {
            try {
                const response = await fetch('/api/test/photos/list');
                const data = await response.json();
                
                const gallery = document.getElementById('photoGallery');
                
                if (data.photos && data.photos.length > 0) {
                    let html = '';
                    data.photos.forEach(photo => {
                        const moderationBadge = getModerationBadge(photo.moderation_status);
                        const processingBadge = getProcessingBadge(photo.processing_status);
                        
                        html += `
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <img src="/storage/photos/${photo.thumbnail_url}" class="card-img-top" alt="${photo.title}">
                                    <div class="card-body p-2">
                                        <h6 class="card-title small mb-1">${photo.title}</h6>
                                        <p class="card-text small text-muted mb-1">${photo.user} - ${photo.contest}</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">${photo.file_size}</small>
                                            <div>
                                                ${moderationBadge}
                                                ${processingBadge}
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-1">${photo.created_at}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    gallery.innerHTML = html;
                } else {
                    gallery.innerHTML = '<div class="col-12 text-center text-muted">📭 Nessuna foto caricata</div>';
                }
            } catch (error) {
                document.getElementById('photoGallery').innerHTML = 
                    `<div class="col-12 text-center text-danger">❌ Errore nel caricamento galleria: ${error.message}</div>`;
            }
        }

        function getModerationBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning status-badge">⏳ Pending</span>',
                'approved': '<span class="badge bg-success status-badge">✅ Approved</span>',
                'rejected': '<span class="badge bg-danger status-badge">❌ Rejected</span>',
                'flagged': '<span class="badge bg-info status-badge">🚩 Flagged</span>'
            };
            return badges[status] || '<span class="badge bg-secondary status-badge">❓ Unknown</span>';
        }

        function getProcessingBadge(status) {
            const badges = {
                'uploading': '<span class="badge bg-primary status-badge">📤 Uploading</span>',
                'processing': '<span class="badge bg-info status-badge">⚙️ Processing</span>',
                'completed': '<span class="badge bg-success status-badge">✅ Done</span>',
                'failed': '<span class="badge bg-danger status-badge">💥 Failed</span>'
            };
            return badges[status] || '<span class="badge bg-secondary status-badge">❓ Unknown</span>';
        }

        // Refresh gallery button
        document.getElementById('refreshGallery').addEventListener('click', loadGallery);

        // Load data on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadStorageInfo();
            loadGallery();
        });
    </script>
</body>
</html>