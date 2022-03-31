<div class="d-flex align-items-center py-4 header">
    <svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">
        <path class="fill-primary" d="M0 40a39.87 39.87 0 0 1 11.72-28.28A40 40 0 1 1 0 40zm34 10a4 4 0 0 1-4-4v-2a2 2 0 1 0-4 0v2a4 4 0 0 1-4 4h-2a2 2 0 1 0 0 4h2a4 4 0 0 1 4 4v2a2 2 0 1 0 4 0v-2a4 4 0 0 1 4-4h2a2 2 0 1 0 0-4h-2zm24-24a6 6 0 0 1-6-6v-3a3 3 0 0 0-6 0v3a6 6 0 0 1-6 6h-3a3 3 0 0 0 0 6h3a6 6 0 0 1 6 6v3a3 3 0 0 0 6 0v-3a6 6 0 0 1 6-6h3a3 3 0 0 0 0-6h-3zm-4 36a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 28a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
    </svg>

    <h4 class="mb-0 ml-3"><strong>Watcher</strong></h4>

    <button class="btn btn-outline-primary ml-auto mr-3" title="Play/Pause">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary" v-if="recording">
            <path d="M5 4h3v12H5V4zm7 0h3v12h-3V4z"/>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary" v-else>
            <path d="M4 4l12 6-12 6z"/>
        </svg>
    </button>

    <button class="btn btn-outline-primary mr-3" title="Auto Load Entries">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
            <path d="M10 3v2a5 5 0 0 0-3.54 8.54l-1.41 1.41A7 7 0 0 1 10 3zm4.95 2.05A7 7 0 0 1 10 17v-2a5 5 0 0 0 3.54-8.54l1.41-1.41zM10 20l-4-4 4-4v8zm0-12V0l4 4-4 4z"></path>
        </svg>
    </button>

    <div class="btn-group" role="group" aria-label="Basic example">
        <a href="/monitored-tags" class="btn btn-outline-primary" title="Monitoring">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>
        </a>
    </div>
</div>