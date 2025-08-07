import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF token setup for Laravel
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Optional: Configure axios for file uploads with progress
window.axios.defaults.onUploadProgress = function(progressEvent) {
    if (window.Livewire) {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        Livewire.emit('upload:progress', {
            percent: percentCompleted,
            loaded: progressEvent.loaded,
            total: progressEvent.total
        });
    }
};

// Optional: Global error handling for axios
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // Session expired
            console.error('Session expired. Reloading page...');
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
