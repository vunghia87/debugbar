<button id="btn-toggle" class="btn btn-outline-primary ml-auto" title="Play/Pause">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
        <path d="<?= debugbar()->isEnable() ? 'M5 4h3v12H5V4zm7 0h3v12h-3V4z' : 'M4 4l12 6-12 6z' ?>"/>
    </svg>
</button>

<button id="btn-clear" class="btn btn-outline-primary ml-3" title="Clear Entries">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
        <path d="M6 2l2-2h4l2 2h4v2H2V2h4zM3 6h14l-1 14H4L3 6zm5 2v10h1V8H8zm3 0v10h1V8h-1z"/>
    </svg>
</button>

<!--<button class="btn btn-outline-primary ml-3" title="Auto Load Entries">-->
<!--    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">-->
<!--        <path d="M10 3v2a5 5 0 0 0-3.54 8.54l-1.41 1.41A7 7 0 0 1 10 3zm4.95 2.05A7 7 0 0 1 10 17v-2a5 5 0 0 0 3.54-8.54l1.41-1.41zM10 20l-4-4 4-4v8zm0-12V0l4 4-4 4z"></path>-->
<!--    </svg>-->
<!--</button>-->

<a href="?openPhpDebugBar=true&op=monitor" class="btn btn-outline-primary ml-3 <?= debugbar()->monitorable() ? 'active' : '' ?>" title="Monitoring">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
        <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
    </svg>
</a>
