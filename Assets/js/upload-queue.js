/**
 * Sistema de Cola de Progreso para Subida de Archivos
 * Estilo Google Drive
 */

class UploadQueue {
    constructor() {
        this.queue = [];
        this.activeUploads = 0;
        this.maxConcurrentUploads = 3;
        this.container = null;
        this.init();
    }

    init() {
        this.createContainer();
        this.bindEvents();
    }

    createContainer() {
        // Crear el contenedor principal
        this.container = document.createElement('div');
        this.container.className = 'upload-queue-container';
        this.container.innerHTML = `
            <div class="upload-queue-header">
                <h3 class="upload-queue-title">Subiendo archivos</h3>
                <button class="upload-queue-close" onclick="uploadQueue.hide()">
                    <i class="material-icons" style="font-size: 18px;">close</i>
                </button>
            </div>
            <div class="upload-queue-body" id="upload-queue-body">
            </div>
        `;
        document.body.appendChild(this.container);
    }

    bindEvents() {
        // Cerrar con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.container.classList.contains('show')) {
                this.hide();
            }
        });
    }

    addFile(file, uploadFunction) {
        const fileId = this.generateId();
        const uploadItem = {
            id: fileId,
            file: file,
            name: file.name,
            size: file.size,
            status: 'pending',
            progress: 0,
            uploadFunction: uploadFunction,
            xhr: null
        };

        this.queue.push(uploadItem);
        this.renderItem(uploadItem);
        this.show();
        this.processQueue();

        return fileId;
    }

    renderItem(item) {
        const queueBody = document.getElementById('upload-queue-body');
        const itemElement = document.createElement('div');
        itemElement.className = `upload-item ${item.status}`;
        itemElement.id = `upload-item-${item.id}`;
        
        itemElement.innerHTML = `
            <div class="upload-item-header">
                <i class="material-icons upload-item-icon">description</i>
                <span class="upload-item-name" title="${item.name}">${item.name}</span>
                <span class="upload-item-status">${this.getStatusText(item.status)}</span>
            </div>
            <div class="upload-item-progress">
                <div class="upload-progress-bar">
                    <div class="upload-progress-fill" style="width: ${item.progress}%"></div>
                </div>
                <span class="upload-progress-percentage">${item.progress}%</span>
            </div>
            <div class="upload-item-actions">
                ${item.status === 'uploading' ? 
                    `<button class="upload-action-btn" onclick="uploadQueue.cancelUpload('${item.id}')">Cancelar</button>` :
                    item.status === 'error' ? 
                    `<button class="upload-action-btn" onclick="uploadQueue.retryUpload('${item.id}')">Reintentar</button>` :
                    ''
                }
            </div>
        `;

        queueBody.appendChild(itemElement);
    }

    updateItem(itemId, updates) {
        const item = this.queue.find(q => q.id === itemId);
        if (!item) return;

        Object.assign(item, updates);
        
        const element = document.getElementById(`upload-item-${itemId}`);
        if (!element) return;

        // Actualizar clase de estado
        element.className = `upload-item ${item.status}`;
        
        // Actualizar estado
        const statusElement = element.querySelector('.upload-item-status');
        statusElement.textContent = this.getStatusText(item.status);
        
        // Actualizar progreso
        const progressFill = element.querySelector('.upload-progress-fill');
        const progressText = element.querySelector('.upload-progress-percentage');
        progressFill.style.width = `${item.progress}%`;
        progressText.textContent = `${item.progress}%`;
        
        // Actualizar acciones
        const actionsContainer = element.querySelector('.upload-item-actions');
        if (item.status === 'uploading') {
            actionsContainer.innerHTML = `<button class="upload-action-btn" onclick="uploadQueue.cancelUpload('${item.id}')">Cancelar</button>`;
        } else if (item.status === 'error') {
            actionsContainer.innerHTML = `<button class="upload-action-btn" onclick="uploadQueue.retryUpload('${item.id}')">Reintentar</button>`;
        } else {
            actionsContainer.innerHTML = '';
        }
    }

    processQueue() {
        const pendingItems = this.queue.filter(item => item.status === 'pending');
        
        while (this.activeUploads < this.maxConcurrentUploads && pendingItems.length > 0) {
            const item = pendingItems.shift();
            this.startUpload(item);
        }
    }

    startUpload(item) {
        this.activeUploads++;
        this.updateItem(item.id, { status: 'uploading' });
        
        // Llamar a la función de subida personalizada
        item.uploadFunction(item, (progress) => {
            this.updateItem(item.id, { progress: Math.round(progress) });
        }, (success, error) => {
            this.activeUploads--;
            if (success) {
                this.updateItem(item.id, { status: 'completed', progress: 100 });
                setTimeout(() => this.removeItem(item.id), 3000);
            } else {
                this.updateItem(item.id, { status: 'error', progress: 0 });
            }
            this.processQueue();
        });
    }

    cancelUpload(itemId) {
        const item = this.queue.find(q => q.id === itemId);
        if (!item) return;

        if (item.xhr) {
            item.xhr.abort();
        }
        
        this.activeUploads--;
        this.removeItem(itemId);
        this.processQueue();
    }

    retryUpload(itemId) {
        const item = this.queue.find(q => q.id === itemId);
        if (!item) return;

        this.updateItem(itemId, { status: 'pending', progress: 0 });
        this.processQueue();
    }

    removeItem(itemId) {
        const element = document.getElementById(`upload-item-${itemId}`);
        if (element) {
            element.remove();
        }
        
        this.queue = this.queue.filter(q => q.id !== itemId);
        
        if (this.queue.length === 0) {
            setTimeout(() => this.hide(), 1000);
        }
    }

    show() {
        this.container.classList.add('show');
    }

    hide() {
        this.container.classList.remove('show');
    }

    getStatusText(status) {
        const statusTexts = {
            'pending': 'En cola',
            'uploading': 'Subiendo...',
            'completed': 'Completado',
            'error': 'Error'
        };
        return statusTexts[status] || status;
    }

    generateId() {
        return 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
}

// Inicializar la cola global
const uploadQueue = new UploadQueue();